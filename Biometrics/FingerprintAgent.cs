using System.Text.Json;
using SourceAFIS;

namespace SFS_Access.Biometrics;

public static class FingerprintAgent
{
    private const string JobFileName = "fingerprint_agent_job.json";
    private const string ActiveFileName = "fingerprint_agent.active";
    private const string HeartbeatFileName = "fingerprint_agent_heartbeat.json";
    private const string StatusFileName = "fingerprint_status.json";
    private const string ReaderOffRequestFileName = "fingerprint_reader_off.request";
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true,
        WriteIndented = true
    };

    private sealed record FingerprintJob(
        string JobId,
        string Action,
        int UserId,
        int Slot,
        string Finger,
        string? CandidatesFile);

    public static void Run()
    {
        string apiDirectory = FindApiDirectory();
        string jobPath = Path.Combine(apiDirectory, JobFileName);
        string activePath = Path.Combine(apiDirectory, ActiveFileName);
        string heartbeatPath = Path.Combine(apiDirectory, HeartbeatFileName);
        string statusPath = Path.Combine(apiDirectory, StatusFileName);
        string readerOffRequestPath = Path.Combine(apiDirectory, ReaderOffRequestFileName);

        Console.Title = "SFS Access - Agente biométrico";
        Console.WriteLine("Agente biométrico SFS Access activo.");
        Console.WriteLine("No cierres esta ventana. Esperando solicitudes del sitio web...");

        using Timer heartbeatTimer = new(_ =>
        {
            try
            {
                WriteJsonAtomically(heartbeatPath, new
                {
                    updated_unix = DateTimeOffset.UtcNow.ToUnixTimeSeconds(),
                    priority_reader_off = true
                });
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[ERROR] No se pudo actualizar el estado del agente: {ex.Message}");
            }
        }, null, TimeSpan.Zero, TimeSpan.FromSeconds(3));

        while (true)
        {
            if (File.Exists(readerOffRequestPath) && !File.Exists(activePath))
            {
                try
                {
                    ProcessReaderOffRequest(
                        readerOffRequestPath, jobPath, apiDirectory, statusPath);
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[ERROR] No se pudo apagar el lector SecuGen: {ex.Message}");
                    WriteStatus(statusPath, new
                    {
                        status = "error",
                        codigo = "READER_OFF_ERROR",
                        mensaje = ex.Message
                    });
                }
            }

            if (File.Exists(jobPath))
            {
                string? claimedJobPath = null;
                bool ownsActiveFile = false;
                try
                {
                    FingerprintJob activeJob = JsonSerializer.Deserialize<FingerprintJob>(File.ReadAllText(jobPath), JsonOptions)
                        ?? throw new InvalidDataException("La solicitud biométrica está vacía o no es válida.");
                    using (new FileStream(activePath, FileMode.CreateNew, FileAccess.Write, FileShare.None))
                    {
                    }
                    ownsActiveFile = true;
                    File.WriteAllText(activePath, JsonSerializer.Serialize(new { job_id = activeJob.JobId }));

                    claimedJobPath = Path.Combine(apiDirectory, $"fingerprint_agent_job_{Guid.NewGuid():N}.processing");
                    File.Move(jobPath, claimedJobPath);
                    ProcessJob(claimedJobPath, apiDirectory, statusPath);
                }
                catch (IOException) when (File.Exists(activePath))
                {
                    Thread.Sleep(250);
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[ERROR] Agente biométrico: {ex.Message}");
                    WriteStatus(statusPath, new
                    {
                        status = "error",
                        codigo = "AGENT_ERROR",
                        mensaje = ex.Message
                    });
                }
                finally
                {
                    if (claimedJobPath is not null && File.Exists(claimedJobPath))
                        DeleteCandidateFile(claimedJobPath, apiDirectory);
                    if (claimedJobPath is not null && File.Exists(claimedJobPath))
                        File.Delete(claimedJobPath);
                    if (ownsActiveFile && File.Exists(activePath))
                        File.Delete(activePath);
                }
            }

            Thread.Sleep(250);
        }
    }

    private static void ProcessJob(string jobPath, string apiDirectory, string statusPath)
    {
        FingerprintJob job = JsonSerializer.Deserialize<FingerprintJob>(File.ReadAllText(jobPath), JsonOptions)
            ?? throw new InvalidDataException("La solicitud biométrica está vacía o no es válida.");
        if (job.Action is not ("enroll" or "identify" or "reader_on" or "reader_off"))
            throw new InvalidDataException($"Acción biométrica no admitida: {job.Action}.");

        if (job.Action == "reader_on")
        {
            SecuGenFingerprintCapture.TurnOn();
            WriteStatus(statusPath, new
            {
                status = "lector_encendido",
                codigo = "READER_ON",
                mensaje = "Huellero SecuGen encendido y listo para capturar."
            });
            Console.WriteLine("[SECUGEN] Huellero encendido.");
            return;
        }

        if (job.Action == "reader_off")
        {
            SecuGenFingerprintCapture.TurnOff();
            WriteStatus(statusPath, new
            {
                status = "lector_apagado",
                codigo = "READER_OFF",
                mensaje = "Huellero SecuGen apagado."
            });
            Console.WriteLine("[SECUGEN] Huellero apagado.");
            return;
        }

        Console.WriteLine();
        Console.WriteLine(job.Action == "enroll"
            ? $"Enrolamiento solicitado para usuario {job.UserId}, dedo {job.Finger} (slot {job.Slot})."
            : "Identificación biométrica solicitada.");

        string cancellationPath = Path.Combine(apiDirectory, $"fingerprint_cancel_{job.JobId}.flag");
        string readerOffRequestPath = Path.Combine(apiDirectory, ReaderOffRequestFileName);
        WriteStatus(statusPath, new
        {
            status = "capturando",
            codigo = "CAPTURANDO",
            mensaje = "Huellero encendido. Coloca el dedo firmemente sobre el lector..."
        });
        Console.WriteLine("[AGENTE] Captura iniciada. Coloca el dedo sobre el huellero SecuGen.");

        using CancellationTokenSource captureCancellation = new();
        using CancellationTokenSource watcherCancellation = new();
        Task cancellationWatcher = Task.Run(() =>
        {
            while (!watcherCancellation.IsCancellationRequested)
            {
                if (File.Exists(cancellationPath))
                {
                    captureCancellation.Cancel();
                    return;
                }
                if (File.Exists(readerOffRequestPath))
                {
                    captureCancellation.Cancel();
                    return;
                }
                Thread.Sleep(200);
            }
        });

        try
        {
            FingerprintTemplate probe = SecuGenFingerprintCapture.CaptureTemplate(captureCancellation.Token);
            captureCancellation.Token.ThrowIfCancellationRequested();
            ProcessCapturedTemplate(job, probe, apiDirectory, statusPath);
        }
        catch (OperationCanceledException)
        {
            if (!File.Exists(readerOffRequestPath))
                WriteCancelledStatus(statusPath);
        }
        finally
        {
            watcherCancellation.Cancel();
            cancellationWatcher.GetAwaiter().GetResult();
            if (File.Exists(cancellationPath))
                File.Delete(cancellationPath);
            if (File.Exists(readerOffRequestPath))
            {
                SecuGenFingerprintCapture.TurnOff();
                WriteStatus(statusPath, new
                {
                    status = "lector_apagado",
                    codigo = "READER_OFF",
                    mensaje = "Captura interrumpida y lector SecuGen apagado."
                });
                File.Delete(readerOffRequestPath);
            }
        }
    }

    private static void ProcessReaderOffRequest(
        string requestPath, string jobPath, string apiDirectory, string statusPath)
    {
        if (File.Exists(jobPath))
        {
            DeleteCandidateFile(jobPath, apiDirectory);
            File.Delete(jobPath);
        }

        SecuGenFingerprintCapture.TurnOff();
        WriteStatus(statusPath, new
        {
            status = "lector_apagado",
            codigo = "READER_OFF",
            mensaje = "Lector SecuGen apagado. La operación en curso fue cancelada."
        });
        File.Delete(requestPath);
    }

    private static void ProcessCapturedTemplate(
        FingerprintJob job,
        FingerprintTemplate probe,
        string apiDirectory,
        string statusPath)
    {
        if (job.Action == "enroll")
        {
            FingerprintCandidate[] registered = ReadCandidates(job.CandidatesFile, apiDirectory);
            FingerprintMatch? duplicate = SourceAfisMatcher.Identify(probe, registered, out _);
            if (duplicate is not null
                && (duplicate.Candidate.UserId != job.UserId || duplicate.Candidate.Slot != job.Slot))
            {
                WriteStatus(statusPath, new
                {
                    status = "error",
                    codigo = "HUELLA_DUPLICADA",
                    mensaje = $"Esta huella ya coincide con la registrada en el slot {duplicate.Candidate.Slot} del usuario {duplicate.Candidate.UserId}.",
                    usuario_id = duplicate.Candidate.UserId,
                    slot = duplicate.Candidate.Slot
                });
                Console.WriteLine("La huella capturada ya está registrada en otro usuario o slot.");
                return;
            }

            WriteStatus(statusPath, new
            {
                status = "enrolado",
                codigo = "ENROLADO",
                mensaje = "Huella capturada y plantilla generada. Guardando...",
                identity_hex = Convert.ToBase64String(probe.ToByteArray()),
                usuario_id = job.UserId,
                slot = job.Slot,
                dedo = job.Finger
            });
            Console.WriteLine("Huella convertida a plantilla SourceAFIS.");
            return;
        }

        FingerprintCandidate[] candidates = ReadCandidates(job.CandidatesFile, apiDirectory);
        FingerprintMatch? match = SourceAfisMatcher.Identify(probe, candidates, out int invalidCount);
        if (match is null)
        {
            string message = invalidCount == 0
                ? "No se encontró una huella registrada con suficiente similitud."
                : $"No hubo coincidencia. Se omitieron {invalidCount} plantillas antiguas o inválidas; vuelve a enrolarlas.";
            WriteStatus(statusPath, new
            {
                status = "no_registrado",
                codigo = "NO_MATCH",
                mensaje = message
            });
            Console.WriteLine(message);
            return;
        }

        WriteStatus(statusPath, new
        {
            status = "identificado",
            codigo = "IDENTIFICADO",
            mensaje = $"Coincidencia biométrica encontrada (puntaje {match.Score:F1}).",
            usuario_id = match.Candidate.UserId,
            slot = match.Candidate.Slot,
            dedo = match.Candidate.Finger,
            origen = match.Candidate.Source,
            score = match.Score
        });
        Console.WriteLine($"Coincidencia encontrada: usuario {match.Candidate.UserId}, puntaje {match.Score:F1}.");
    }

    private static void WriteCancelledStatus(string statusPath)
    {
        WriteStatus(statusPath, new
        {
            status = "cancelado",
            codigo = "CANCELADO",
            mensaje = "Operación biométrica cancelada."
        });
        Console.WriteLine("Operación biométrica cancelada.");
    }

    private static FingerprintCandidate[] ReadCandidates(string? candidatesFile, string apiDirectory)
    {
        if (string.IsNullOrWhiteSpace(candidatesFile))
            return [];

        string path = Path.Combine(apiDirectory, Path.GetFileName(candidatesFile));
        if (!File.Exists(path))
            throw new FileNotFoundException("No se encontró el archivo de plantillas para identificación.", path);

        return JsonSerializer.Deserialize<FingerprintCandidate[]>(File.ReadAllText(path), JsonOptions) ?? [];
    }

    private static void DeleteCandidateFile(string jobPath, string apiDirectory)
    {
        try
        {
            FingerprintJob? job = JsonSerializer.Deserialize<FingerprintJob>(File.ReadAllText(jobPath), JsonOptions);
            if (job?.CandidatesFile is null)
                return;

            string candidatePath = Path.Combine(apiDirectory, Path.GetFileName(job.CandidatesFile));
            if (File.Exists(candidatePath))
                File.Delete(candidatePath);
        }
        catch (JsonException ex)
        {
            Console.WriteLine($"[AVISO] No se pudo leer la solicitud para limpiar candidatos: {ex.Message}");
        }
        catch (IOException ex)
        {
            Console.WriteLine($"[AVISO] No se pudo limpiar el archivo de candidatos: {ex.Message}");
        }
    }

    private static void WriteStatus(string path, object status)
    {
        WriteJsonAtomically(path, status);
    }

    private static void WriteJsonAtomically(string path, object value)
    {
        string temporaryPath = Path.Combine(Path.GetDirectoryName(path)!, $".{Path.GetFileName(path)}.{Guid.NewGuid():N}.tmp");
        File.WriteAllText(temporaryPath, JsonSerializer.Serialize(value, JsonOptions));
        File.Move(temporaryPath, path, true);
    }

    private static string FindApiDirectory()
    {
        foreach (string start in new[] { Environment.CurrentDirectory, AppContext.BaseDirectory })
        {
            DirectoryInfo? directory = new(start);
            while (directory is not null)
            {
                string apiPath = Path.Combine(directory.FullName, "api");
                if (Directory.Exists(apiPath))
                    return apiPath;
                directory = directory.Parent;
            }
        }

        throw new DirectoryNotFoundException("No se encontró la carpeta api. Ejecuta el agente desde la carpeta SFS Access.");
    }
}
