using System;
using SFS_Access.Biometrics;

class Program
{
    static void Main(string[] args)
    {
        if (args.Contains("--agent", StringComparer.OrdinalIgnoreCase))
        {
            FingerprintAgent.Run();
            return;
        }

        Console.WriteLine("=== Prueba de Captura SecuGen Hamster Pro ===");

        try
        {
            var template = SecuGenFingerprintCapture.CaptureTemplate();
            Console.WriteLine($"Huella capturada correctamente. Plantilla SourceAFIS generada ({template.ToByteArray().Length} bytes).");
        }
        catch (Exception ex)
        {
            Console.WriteLine($"[ERROR] No se pudo capturar la huella con SecuGen: {ex.Message}");
        }

        Console.WriteLine("\nPrueba finalizada.");
    }
}