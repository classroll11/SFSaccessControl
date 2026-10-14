using SourceAFIS;
using SecuGen.FDxSDKPro.Windows;

namespace SFS_Access.Biometrics;

public static class SecuGenFingerprintCapture
{
    private const int CaptureTimeout = 54;
    private static SGFingerPrintManager? _manager;
    private static SGFPMDeviceInfoParam? _deviceInfo;

    public static void TurnOn()
    {
        EnsureDeviceOpen();
        SetLed(true);
    }

    public static void TurnOff()
    {
        if (_manager is null)
            return;

        int ledResult = _manager.SetLedOn(false);
        int closeResult = _manager.CloseDevice();
        _manager = null;
        _deviceInfo = null;

        if (ledResult != (int)SGFPMError.ERROR_NONE)
            throw new InvalidOperationException($"No se pudo apagar el indicador del lector SecuGen (código {ledResult}).");
        if (closeResult != (int)SGFPMError.ERROR_NONE)
            throw new InvalidOperationException($"No se pudo cerrar el lector SecuGen (código {closeResult}).");
    }

    public static FingerprintTemplate CaptureTemplate(CancellationToken cancellationToken = default)
    {
        cancellationToken.ThrowIfCancellationRequested();
        EnsureDeviceOpen();
        SetLed(true);

        SGFPMDeviceInfoParam info = _deviceInfo!;
        byte[] image = new byte[checked(info.ImageWidth * info.ImageHeight)];

        while (true)
        {
            cancellationToken.ThrowIfCancellationRequested();

            int result = _manager!.GetImageEx(image, 1000, 0, 50);
            if (result == CaptureTimeout)
                continue;
            if (result != (int)SGFPMError.ERROR_NONE)
                throw new InvalidOperationException($"La captura del lector SecuGen falló (código {result}).");

            cancellationToken.ThrowIfCancellationRequested();
            int quality = 0;
            _manager.GetImageQuality(info.ImageWidth, info.ImageHeight, image, ref quality);
            Console.WriteLine($"[SECuGEN] Calidad de imagen: {quality}/100, resolución: {info.ImageWidth}x{info.ImageHeight} ({info.ImageDPI} DPI).");
            if (quality < 20)
                throw new InvalidOperationException($"La calidad de la huella fue demasiado baja ({quality}/100). Vuelve a colocar el dedo.");

            return new FingerprintTemplate(
                new FingerprintImage(
                    info.ImageWidth,
                    info.ImageHeight,
                    image,
                    new FingerprintImageOptions { Dpi = info.ImageDPI }));
        }
    }

    private static void EnsureDeviceOpen()
    {
        if (_manager is not null)
            return;

        SGFingerPrintManager manager = new();
        int result = manager.Init(SGFPMDeviceName.DEV_AUTO);
        if (result != (int)SGFPMError.ERROR_NONE)
            throw new InvalidOperationException($"No se pudo inicializar el lector SecuGen (código {result}).");

        result = manager.OpenDevice((int)SGFPMPortAddr.USB_AUTO_DETECT);
        if (result != (int)SGFPMError.ERROR_NONE)
            throw new InvalidOperationException($"No se pudo abrir el lector SecuGen Hamster Pro (código {result}).");

        SGFPMDeviceInfoParam info = new();
        result = manager.GetDeviceInfo(info);
        if (result != (int)SGFPMError.ERROR_NONE)
        {
            manager.CloseDevice();
            throw new InvalidOperationException($"No se pudo obtener la información del lector SecuGen (código {result}).");
        }

        _manager = manager;
        _deviceInfo = info;
        Console.WriteLine($"[SECuGEN] Lector listo: {info.ImageWidth}x{info.ImageHeight}, {info.ImageDPI} DPI.");
    }

    private static void SetLed(bool isOn)
    {
        int result = _manager!.SetLedOn(isOn);
        if (result != (int)SGFPMError.ERROR_NONE)
            throw new InvalidOperationException($"No se pudo {(isOn ? "encender" : "apagar")} el indicador del lector SecuGen (código {result}).");
    }
}
