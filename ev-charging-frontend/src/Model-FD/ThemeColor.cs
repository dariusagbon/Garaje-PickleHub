using System.Globalization;

namespace EvChargingQueue.ModelFD;

/// <summary>
/// An immutable sRGB color. Tints, tones and shades are all produced by
/// mixing a base color toward another color, so the shared math lives here.
/// </summary>
public readonly record struct ThemeColor(byte R, byte G, byte B)
{
    public static readonly ThemeColor White = new(255, 255, 255);
    public static readonly ThemeColor Black = new(0, 0, 0);
    public static readonly ThemeColor MidGray = new(128, 128, 128);

    /// <summary>Parses "#RRGGBB", "RRGGBB", "#RGB" or "RGB".</summary>
    public static ThemeColor FromHex(string hex)
    {
        ArgumentNullException.ThrowIfNull(hex);

        var value = hex.Trim().TrimStart('#');
        if (value.Length == 3)
        {
            value = string.Concat(value.Select(c => new string(c, 2)));
        }

        if (value.Length != 6 || !int.TryParse(value, NumberStyles.HexNumber, CultureInfo.InvariantCulture, out var rgb))
        {
            throw new FormatException($"'{hex}' is not a valid hex color.");
        }

        return new ThemeColor((byte)(rgb >> 16), (byte)(rgb >> 8), (byte)rgb);
    }

    public string ToHex() => $"#{R:X2}{G:X2}{B:X2}";

    public string ToCssRgb() => $"rgb({R}, {G}, {B})";

    /// <summary>
    /// Linearly interpolates toward <paramref name="target"/>.
    /// An amount of 0 returns this color, 1 returns the target.
    /// </summary>
    public ThemeColor MixWith(ThemeColor target, double amount)
    {
        ValidateAmount(amount);

        return new ThemeColor(
            MixChannel(R, target.R, amount),
            MixChannel(G, target.G, amount),
            MixChannel(B, target.B, amount));
    }

    /// <summary>WCAG 2.x relative luminance, 0 (black) to 1 (white).</summary>
    public double RelativeLuminance =>
        0.2126 * Linearize(R) + 0.7152 * Linearize(G) + 0.0722 * Linearize(B);

    /// <summary>WCAG contrast ratio between two colors, from 1 to 21.</summary>
    public double ContrastRatio(ThemeColor other)
    {
        var lighter = Math.Max(RelativeLuminance, other.RelativeLuminance);
        var darker = Math.Min(RelativeLuminance, other.RelativeLuminance);
        return (lighter + 0.05) / (darker + 0.05);
    }

    /// <summary>Black or white, whichever reads better on top of this color.</summary>
    public ThemeColor ReadableForeground() =>
        ContrastRatio(Black) >= ContrastRatio(White) ? Black : White;

    public override string ToString() => ToHex();

    internal static void ValidateAmount(double amount)
    {
        if (double.IsNaN(amount) || amount < 0 || amount > 1)
        {
            throw new ArgumentOutOfRangeException(nameof(amount), amount, "Amount must be between 0 and 1.");
        }
    }

    private static byte MixChannel(byte from, byte to, double amount) =>
        (byte)Math.Round(from + (to - from) * amount, MidpointRounding.AwayFromZero);

    private static double Linearize(byte channel)
    {
        var c = channel / 255.0;
        return c <= 0.04045 ? c / 12.92 : Math.Pow((c + 0.055) / 1.055, 2.4);
    }
}
