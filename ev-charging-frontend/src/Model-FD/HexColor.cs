namespace EvChargingQueue.ModelFD;

/// <summary>
/// The protection behind every color setter: only valid hex colors get in,
/// and they are stored in one format ("#RRGGBB", upper case).
/// </summary>
public static class HexColor
{
    public static string Normalize(string? value, string propertyName)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            throw new ArgumentException($"{propertyName} needs a hex color such as \"#0B6E3A\".", propertyName);
        }

        try
        {
            return ThemeColor.FromHex(value).ToHex();
        }
        catch (FormatException)
        {
            throw new ArgumentException($"{propertyName} got \"{value}\", which is not a hex color such as \"#0B6E3A\".", propertyName);
        }
    }
}
