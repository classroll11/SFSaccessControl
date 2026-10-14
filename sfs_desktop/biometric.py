import json
import msvcrt
import os
import subprocess
import time
import uuid
from pathlib import Path

from .repository import Repository


class BiometricBridge:
    def __init__(self, root: Path, repository: Repository):
        self.api = root / "api"
        self.repository = repository
        self._agent_process: subprocess.Popen | None = None
        self._last_handled_status: tuple[str, str] | None = None
        self._pending_enrollment: tuple[int, int, str] | None = None
        self._pending_attendance: tuple[int, str, str, str] | None = None
        self._pending_legacy_reader_off = False
        self._legacy_reader_off_cancel_path: Path | None = None
        self._pending_access = False
        self._pending_access_event = "ENTRADA"
        self._continuous_attendance: tuple[int, str, str, str] | None = None
        self._continuous_access: str | None = None
        self._last_attendance_scanned_times: dict[int, float] = {}
        self._last_access_scanned_times: dict[int, float] = {}
        self._start_agent_if_needed(root)

    def _agent_is_online(self) -> bool:
        heartbeat = self.api / "fingerprint_agent_heartbeat.json"
        try:
            data = json.loads(heartbeat.read_text(encoding="utf-8"))
            return time.time() - int(data["updated_unix"]) < 12
        except (OSError, ValueError, KeyError, TypeError, json.JSONDecodeError):
            return False

    def _agent_supports_priority_off(self) -> bool:
        heartbeat = self.api / "fingerprint_agent_heartbeat.json"
        try:
            data = json.loads(heartbeat.read_text(encoding="utf-8"))
            return data.get("priority_reader_off") is True
        except (OSError, ValueError, TypeError, json.JSONDecodeError):
            return False

    def _signal_active_job_cancel(self) -> bool:
        active_path = self.api / "fingerprint_agent.active"
        job_path = self.api / "fingerprint_agent_job.json"
        source_path = active_path if active_path.exists() else job_path
        if not source_path.exists():
            return False
        try:
            job = json.loads(source_path.read_text(encoding="utf-8"))
            job_id = uuid.UUID(str(job.get("job_id") or job.get("jobId"))).hex
        except (OSError, json.JSONDecodeError, ValueError, AttributeError) as exc:
            raise RuntimeError("No se pudo identificar la operación biométrica activa.") from exc
        (self.api / f"fingerprint_cancel_{job_id}.flag").write_text(
            "reader_off", encoding="utf-8"
        )
        self._legacy_reader_off_cancel_path = self.api / f"fingerprint_cancel_{job_id}.flag"
        return True

    def _dispatch_legacy_reader_off(self) -> None:
        if not self._pending_legacy_reader_off:
            return
        if self._signal_active_job_cancel():
            return
        if self._legacy_reader_off_cancel_path:
            self._legacy_reader_off_cancel_path.unlink(missing_ok=True)
            self._legacy_reader_off_cancel_path = None
        self._queue("reader_off")
        self._pending_legacy_reader_off = False

    def _start_agent_if_needed(self, root: Path) -> None:
        if self._agent_is_online():
            return
        project = root / "SFS Access.csproj"
        if not project.is_file():
            return
        log_path = self.api / "fingerprint_agent_desktop.log"
        try:
            with log_path.open("a", encoding="utf-8") as log:
                self._agent_process = subprocess.Popen(
                    ["dotnet", "run", "--no-build", "--project", str(project), "--", "--agent"],
                    cwd=root,
                    stdin=subprocess.DEVNULL,
                    stdout=log,
                    stderr=subprocess.STDOUT,
                    creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
                )
        except OSError:
            return

    def _queue(self, action: str, user_id: int = 0, slot: int = 0,
               finger: str = "", candidates: list[dict] | None = None) -> str:
        if not self._agent_is_online():
            raise RuntimeError(
                "El agente SecuGen no está activo. Verifique .NET y ejecute: "
                "dotnet run --no-build -- --agent"
            )
        job_path = self.api / "fingerprint_agent_job.json"
        active_path = self.api / "fingerprint_agent.active"
        lock_path = self.api / "fingerprint_agent_queue.lock"
        job_id = uuid.uuid4().hex
        candidate_name = None
        lock_handle = None
        locked = False
        try:
            lock_handle = lock_path.open("a+b")
            if lock_handle.seek(0, os.SEEK_END) == 0:
                lock_handle.write(b"\0")
                lock_handle.flush()
            lock_handle.seek(0)
            try:
                msvcrt.locking(lock_handle.fileno(), msvcrt.LK_NBLCK, 1)
            except OSError as exc:
                raise RuntimeError("El agente está procesando otra solicitud biométrica.") from exc
            locked = True
            if job_path.exists() or active_path.exists():
                raise RuntimeError("El huellero está ocupado con otra operación.")
            job = {
                "jobId": job_id,
                "action": action,
                "userId": user_id,
                "slot": slot,
                "finger": finger,
            }
            if candidates is not None:
                candidate_name = f"fingerprint_agent_candidates_{job_id}.json"
                candidate_path = self.api / candidate_name
                candidate_path.write_text(
                    json.dumps(candidates, ensure_ascii=False), encoding="utf-8"
                )
                job["candidatesFile"] = candidate_name
            status = {
                "status": "iniciando",
                "codigo": f"{action.upper()}_STARTING",
                "job_id": job_id,
                "mensaje": "Solicitud biométrica enviada al SecuGen.",
            }
            if action == "enroll":
                status.update({"usuario_id": user_id, "slot": slot, "dedo": finger})
            temp_path = self.api / f"fingerprint_job_{job_id}.tmp"
            temp_path.write_text(json.dumps(job, ensure_ascii=False), encoding="utf-8")
            status_path = self.api / "fingerprint_status.json"
            temp_status = self.api / f"fingerprint_status_{job_id}.tmp"
            temp_status.write_text(json.dumps(status, ensure_ascii=False), encoding="utf-8")
            os.replace(temp_status, status_path)
            os.replace(temp_path, job_path)
            return job_id
        except FileExistsError as exc:
            raise RuntimeError("El agente está procesando otra solicitud biométrica.") from exc
        except Exception:
            if candidate_name:
                (self.api / candidate_name).unlink(missing_ok=True)
            raise
        finally:
            if lock_handle is not None:
                if locked:
                    lock_handle.seek(0)
                    msvcrt.locking(lock_handle.fileno(), msvcrt.LK_UNLCK, 1)
                lock_handle.close()

    def turn_on(self) -> str:
        return self._queue("reader_on")

    def turn_off(self) -> str:
        if not self._agent_is_online():
            raise RuntimeError(
                "El agente SecuGen no responde; no se puede enviar la orden de apagado."
            )
        self._pending_enrollment = None
        self._pending_attendance = None
        self._continuous_attendance = None
        self._pending_access = False
        self._continuous_access = None
        self._last_handled_status = None
        if self._agent_supports_priority_off():
            request_path = self.api / "fingerprint_reader_off.request"
            temporary_path = self.api / f"fingerprint_reader_off_{uuid.uuid4().hex}.tmp"
            temporary_path.write_text(
                json.dumps({"requested_unix": int(time.time())}), encoding="utf-8"
            )
            os.replace(temporary_path, request_path)
            return "Orden prioritaria enviada: se detendrá cualquier captura y se apagará el lector."

        self._pending_legacy_reader_off = True
        self._legacy_reader_off_cancel_path = None
        self._dispatch_legacy_reader_off()
        return "Se solicitó apagar el lector y detener cualquier captura activa."

    def is_continuous_active(self) -> bool:
        return (
            getattr(self, "_continuous_attendance", None) is not None
            or getattr(self, "_continuous_access", None) is not None
        )

    def start_access_scan(self, event: str, continuous: bool = True) -> str:
        self._pending_attendance = None
        self._continuous_attendance = None
        self._pending_access_event = event
        self._last_handled_status = None
        self._pending_access = True
        self._continuous_access = event if continuous else None
        try:
            return self._queue("identify", candidates=self.repository.fingerprint_candidates())
        except Exception:
            self._pending_access = False
            self._continuous_access = None
            raise

    def start_attendance_scan(
        self, teacher_id: int, grade: str, attendance_date: str, subject: str,
    ) -> str:
        self._continuous_access = None
        self._continuous_attendance = (teacher_id, grade, attendance_date, subject)
        self._pending_attendance = self._continuous_attendance
        self._pending_access = False
        self._last_handled_status = None
        try:
            return self._queue("identify", candidates=self.repository.fingerprint_candidates())
        except Exception:
            self._pending_attendance = None
            self._continuous_attendance = None
            raise

    def _continue_scan(self) -> None:
        if not self.is_continuous_active():
            return
        if (self.api / "fingerprint_agent_job.json").exists() or (
            self.api / "fingerprint_agent.active"
        ).exists():
            return

        continuous_att = getattr(self, "_continuous_attendance", None)
        if continuous_att is not None:
            if self._pending_attendance is not None:
                return
            self._pending_attendance = continuous_att
            try:
                self._queue("identify", candidates=self.repository.fingerprint_candidates())
            except RuntimeError as error:
                self._pending_attendance = None
                if "ocupado" in str(error).lower() or "procesando" in str(error).lower():
                    return
                self._continuous_attendance = None
                raise
            except Exception:
                self._pending_attendance = None
                self._continuous_attendance = None
                raise
            return

        continuous_acc = getattr(self, "_continuous_access", None)
        if continuous_acc is not None:
            if self._pending_access:
                return
            self._pending_access = True
            self._pending_access_event = continuous_acc
            try:
                self._queue("identify", candidates=self.repository.fingerprint_candidates())
            except RuntimeError as error:
                self._pending_access = False
                if "ocupado" in str(error).lower() or "procesando" in str(error).lower():
                    return
                self._continuous_access = None
                raise
            except Exception:
                self._pending_access = False
                self._continuous_access = None
                raise
            return

    def _continue_attendance_scan(self) -> None:
        self._continue_scan()

    def start_enrollment(self, user_id: int, slot: int, finger: str) -> str:
        if not 1 <= slot <= 6:
            raise ValueError("El número de huella debe estar entre 1 y 6.")
        self._pending_enrollment = (user_id, slot, finger)
        self._last_handled_status = None
        try:
            return self._queue(
                "enroll", user_id, slot, finger,
                candidates=self.repository.fingerprint_candidates(),
            )
        except Exception:
            self._pending_enrollment = None
            raise

    def cancel(self) -> None:
        job_path = self.api / "fingerprint_agent_job.json"
        if job_path.exists():
            job = json.loads(job_path.read_text(encoding="utf-8"))
            (self.api / f"fingerprint_cancel_{job['jobId']}.flag").write_text(
                "cancel", encoding="utf-8"
            )
        else:
            active_path = self.api / "fingerprint_agent.active"
            if active_path.exists():
                active = json.loads(active_path.read_text(encoding="utf-8"))
                job_id = active.get("job_id")
                if job_id:
                    (self.api / f"fingerprint_cancel_{job_id}.flag").write_text(
                        "cancel", encoding="utf-8"
                    )
            else:
                raise RuntimeError("No hay una operación biométrica en curso.")

    def poll(self) -> dict | None:
        self._dispatch_legacy_reader_off()
        status_path = self.api / "fingerprint_status.json"
        try:
            status = json.loads(status_path.read_text(encoding="utf-8"))
        except FileNotFoundError:
            self._continue_scan()
            return None
        except json.JSONDecodeError as exc:
            raise RuntimeError("El archivo de estado biométrico está dañado.") from exc

        state = str(status.get("status", ""))
        job_id = str(status.get("job_id") or status.get("jobId") or "")
        marker = (job_id, state) if job_id else (state, str(status.get("identity_hex", status.get("usuario_id", ""))))

        if marker == self._last_handled_status:
            if not (self.api / "fingerprint_agent_job.json").exists() and not (
                self.api / "fingerprint_agent.active"
            ).exists():
                if getattr(self, "_continuous_attendance", None) is not None and self._pending_attendance is not None:
                    self._pending_attendance = None
                if getattr(self, "_continuous_access", None) is not None and self._pending_access:
                    self._pending_access = False
            self._continue_scan()
            return status

        if state == "enrolado" and self._pending_enrollment:
            user_id, slot, finger = self._pending_enrollment
            try:
                self.repository.save_fingerprint(
                    user_id, slot, finger, str(status["identity_hex"])
                )
            except Exception as error:
                self._pending_enrollment = None
                raise RuntimeError(f"La captura terminó, pero no se pudo guardar la huella: {error}") from error
            else:
                self._pending_enrollment = None
            status["mensaje"] = "Huella guardada correctamente en la base de datos."
        elif state in {"identificado", "no_registrado"}:
            if self._pending_attendance or getattr(self, "_continuous_attendance", None):
                self._register_class_attendance(status)
            elif self._pending_access or getattr(self, "_continuous_access", None):
                self._register_access(status)
            self._pending_attendance = None
            self._pending_access = False
        elif state in {"error", "cancelado", "lector_apagado"}:
            self._pending_enrollment = None
            self._pending_attendance = None
            self._continuous_attendance = None
            self._pending_access = False
            self._continuous_access = None

        if state in {
            "enrolado", "identificado", "no_registrado", "error", "cancelado",
            "lector_apagado",
        }:
            self._last_handled_status = marker

        self._continue_scan()
        return status

    def _register_class_attendance(self, status: dict) -> None:
        attendance = self._pending_attendance or self._continuous_attendance
        if attendance is None:
            return
        teacher_id, grade, attendance_date, subject = attendance
        user_id = int(status.get("usuario_id", 0))
        user = self.repository.get_access_user(user_id) if user_id else None
        if not user:
            self._pending_attendance = None
            status["mensaje"] = "Huella no reconocida; no se marcó asistencia."
            status["asistencia_estado"] = "rechazada"
            if self._continuous_attendance:
                status["asistencia_estado"] = "no_registrada"
            return
        if user["rol"] != "ESTUDIANTE" or user["estado"] != "ACTIVO" or user["grado"] != grade:
            self._pending_attendance = None
            status["mensaje"] = (
                f"{user['nombre']} no pertenece al grado seleccionado o su cuenta no está activa."
            )
            status["asistencia_estado"] = "rechazada"
            return

        now = time.time()
        last_time = getattr(self, "_last_attendance_scanned_times", {}).get(user_id, 0)
        if now - last_time < 3.0:
            status["usuario"] = user
            status["asistencia_estado"] = "registrada"
            status["mensaje"] = f"Asistencia ya registrada para {user['nombre']} ({grade})."
            self._pending_attendance = None
            return

        self.repository.record_attendance(
            user_id, teacher_id, grade, subject, "PRESENTE", "", attendance_date,
        )
        if not hasattr(self, "_last_attendance_scanned_times"):
            self._last_attendance_scanned_times = {}
        self._last_attendance_scanned_times[user_id] = now
        self._pending_attendance = None
        status["usuario"] = user
        status["asistencia_estado"] = "registrada"
        status["mensaje"] = f"Asistencia registrada para {user['nombre']} ({grade})."

    def _register_access(self, status: dict) -> None:
        self._pending_access = False
        user_id = int(status.get("usuario_id", 0))
        event = self._pending_access_event or self._continuous_access or "ENTRADA"
        if not user_id:
            self.repository.record_access(
                None, event, False, "Huella no reconocida por el lector SecuGen."
            )
            status["mensaje"] = "Huella no reconocida. Se registró el intento rechazado."
            return
        user = self.repository.get_access_user(user_id)
        if not user:
            self.repository.record_access(
                None, event, False, "La huella coincide con un usuario que ya no existe."
            )
            status["status"] = "error"
            status["mensaje"] = "El usuario asociado a la huella ya no existe."
            return

        now = time.time()
        last_time = getattr(self, "_last_access_scanned_times", {}).get(user_id, 0)
        if now - last_time < 3.0:
            status["usuario"] = user
            status["mensaje"] = f"{event} ya registrada para {user['nombre']}."
            return

        approved = user["estado"] == "ACTIVO"
        details = f"SecuGen - {event} - {status.get('dedo') or 'Huella'}"
        if not approved:
            details += f" - Usuario {user['estado']}"
        self.repository.record_access(user_id, event, approved, details)
        if not hasattr(self, "_last_access_scanned_times"):
            self._last_access_scanned_times = {}
        self._last_access_scanned_times[user_id] = now
        status["usuario"] = user
        status["mensaje"] = (
            f"{'Acceso aprobado' if approved else 'Acceso rechazado'}: "
            f"{user['nombre']} ({user['grado'] or user['rol']})."
        )
