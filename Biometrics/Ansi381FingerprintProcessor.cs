using System.Buffers.Binary;
using SourceAFIS;

namespace SFS_Access.Biometrics;

public static class Ansi381FingerprintProcessor
{
    private const int BirDescriptorSize = 8;
    private const int BirSize = 32;
    private const int Ansi381HeaderSize = 38;
    private const int Ansi381RecordHeaderSize = 14;
    private const uint FingerprintBiometricType = 0x00000008;
    private const ushort Ansi381Owner = 0x001B;
    private const ushort Ansi381Type = 0x0401;

    public static FingerprintTemplate CreateTemplate(byte[] birBytes)
    {
        ArgumentNullException.ThrowIfNull(birBytes);
        if (birBytes.Length < BirSize)
            throw new InvalidDataException("La muestra WinBio no contiene una estructura BIR completa.");

        uint headerSize = ReadUInt32LittleEndian(birBytes, 0);
        uint headerOffset = ReadUInt32LittleEndian(birBytes, 4);
        uint standardSize = ReadUInt32LittleEndian(birBytes, BirDescriptorSize);
        uint standardOffset = ReadUInt32LittleEndian(birBytes, BirDescriptorSize + 4);

        ValidateBlock(headerSize, headerOffset, birBytes.Length, "cabecera BIR");
        ValidateBlock(standardSize, standardOffset, birBytes.Length, "datos estándar BIR");
        if (headerSize < 48)
            throw new InvalidDataException("La cabecera BIR es demasiado pequeña para contener sus metadatos.");

        int headerDataOffset = checked((int)headerOffset);
        uint biometricType = ReadUInt32LittleEndian(birBytes, headerDataOffset + 8);
        ushort formatOwner = ReadUInt16LittleEndian(birBytes, headerDataOffset + 40);
        ushort formatType = ReadUInt16LittleEndian(birBytes, headerDataOffset + 42);

        if (biometricType != FingerprintBiometricType)
            throw new InvalidDataException($"La muestra no es una huella digital (tipo 0x{biometricType:X8}).");
        if (formatOwner != Ansi381Owner || formatType != Ansi381Type)
            throw new NotSupportedException($"Formato BIR no compatible: propietario 0x{formatOwner:X4}, tipo 0x{formatType:X4}.");

        ReadOnlySpan<byte> standardData = birBytes.AsSpan(checked((int)standardOffset), checked((int)standardSize));
        return CreateTemplateFromAnsi381(standardData);
    }

    private static FingerprintTemplate CreateTemplateFromAnsi381(ReadOnlySpan<byte> data)
    {
        if (data.Length < Ansi381HeaderSize)
            throw new InvalidDataException("El bloque ANSI 381 está truncado.");
        if (!data.Slice(8, 4).SequenceEqual("FIR\0"u8))
            throw new InvalidDataException("El identificador del bloque ANSI 381 no es FIR.");
        if (!data.Slice(12, 4).SequenceEqual("010\0"u8))
            throw new NotSupportedException("La versión del registro ANSI 381 no es compatible.");

        uint declaredLength = BinaryPrimitives.ReadUInt32BigEndian(data[..4]);
        ushort owner = BinaryPrimitives.ReadUInt16BigEndian(data[16..]);
        ushort type = BinaryPrimitives.ReadUInt16BigEndian(data[18..]);
        ushort horizontalResolution = BinaryPrimitives.ReadUInt16BigEndian(data[24..]);
        ushort verticalResolution = BinaryPrimitives.ReadUInt16BigEndian(data[26..]);
        ushort horizontalImageResolution = BinaryPrimitives.ReadUInt16BigEndian(data[28..]);
        ushort verticalImageResolution = BinaryPrimitives.ReadUInt16BigEndian(data[30..]);
        byte elementCount = data[32];
        byte scaleUnits = data[33];
        byte pixelDepth = data[34];
        byte compression = data[35];

        if (declaredLength < Ansi381HeaderSize || declaredLength > data.Length)
            throw new InvalidDataException("La longitud declarada del registro ANSI 381 no es válida.");
        if (owner != Ansi381Owner || type != Ansi381Type)
            throw new InvalidDataException($"El formato del bloque ANSI 381 no coincide: propietario 0x{owner:X4}, tipo 0x{type:X4}.");
        if (elementCount == 0)
            throw new InvalidDataException("El bloque ANSI 381 no contiene imágenes.");
        if (horizontalResolution == 0 || verticalResolution == 0 || horizontalImageResolution == 0 || verticalImageResolution == 0)
            throw new InvalidDataException("La resolución de la imagen ANSI 381 no está especificada.");

        double dpiFactor = scaleUnits switch
        {
            1 => 1.0,
            2 => 2.54,
            _ => throw new NotSupportedException($"Unidad de resolución ANSI 381 no compatible: {scaleUnits}.")
        };
        double dpi = (horizontalImageResolution + verticalImageResolution) * dpiFactor / 2.0;
        var options = new FingerprintImageOptions { Dpi = dpi };

        int recordOffset = Ansi381HeaderSize;
        if (recordOffset + Ansi381RecordHeaderSize > declaredLength)
            throw new InvalidDataException("El encabezado de imagen ANSI 381 está truncado.");

        uint recordLength = BinaryPrimitives.ReadUInt32BigEndian(data[recordOffset..]);
        ushort width = BinaryPrimitives.ReadUInt16BigEndian(data[(recordOffset + 4)..]);
        ushort height = BinaryPrimitives.ReadUInt16BigEndian(data[(recordOffset + 6)..]);
        if (recordLength < Ansi381RecordHeaderSize || recordLength > declaredLength - (uint)recordOffset)
            throw new InvalidDataException("La longitud de la imagen ANSI 381 no es válida.");
        if (width == 0 || height == 0)
            throw new InvalidDataException("La imagen ANSI 381 no tiene dimensiones válidas.");

        ReadOnlySpan<byte> imageData = data.Slice(
            recordOffset + Ansi381RecordHeaderSize,
            checked((int)recordLength - Ansi381RecordHeaderSize));

        FingerprintImage image = compression switch
        {
            0 => CreateUncompressedImage(imageData, width, height, pixelDepth, options),
            3 or 5 => new FingerprintImage(imageData.ToArray(), options),
            1 => throw new NotSupportedException("La compresión bit-packed ANSI 381 todavía no está implementada."),
            2 => throw new NotSupportedException("La compresión WSQ ANSI 381 no está soportada por SourceAFIS directamente."),
            4 => throw new NotSupportedException("La compresión JPEG 2000 ANSI 381 no está soportada por SourceAFIS directamente."),
            _ => throw new NotSupportedException($"Algoritmo de compresión ANSI 381 no compatible: {compression}.")
        };

        return new FingerprintTemplate(image);
    }

    private static FingerprintImage CreateUncompressedImage(
        ReadOnlySpan<byte> imageData,
        int width,
        int height,
        byte pixelDepth,
        FingerprintImageOptions options)
    {
        if (pixelDepth != 8)
            throw new NotSupportedException($"Profundidad de píxel ANSI 381 no compatible: {pixelDepth} bits.");

        int requiredLength = checked(width * height);
        if (imageData.Length != requiredLength)
            throw new InvalidDataException($"El tamaño de la imagen cruda ANSI 381 no coincide con sus dimensiones ({imageData.Length} != {requiredLength}).");

        return new FingerprintImage(width, height, imageData.ToArray(), options);
    }

    private static void ValidateBlock(uint size, uint offset, int totalLength, string blockName)
    {
        if (size == 0 || offset > totalLength || size > totalLength - offset)
            throw new InvalidDataException($"El bloque de {blockName} apunta fuera de la muestra.");
    }

    private static uint ReadUInt32LittleEndian(byte[] bytes, int offset)
    {
        return BinaryPrimitives.ReadUInt32LittleEndian(bytes.AsSpan(offset, sizeof(uint)));
    }

    private static ushort ReadUInt16LittleEndian(byte[] bytes, int offset)
    {
        return BinaryPrimitives.ReadUInt16LittleEndian(bytes.AsSpan(offset, sizeof(ushort)));
    }
}
