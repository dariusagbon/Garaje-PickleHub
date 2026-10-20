namespace EvChargingQueue.Theme;

/// <summary>
/// A tone is a base color mixed with gray, which lowers its saturation
/// without making it much lighter or darker. Tones are used for muted UI,
/// such as stations that are offline or queue entries that have expired.
/// </summary>
public sealed class Tone
{
    public Tone(ThemeColor baseColor, double amount)
        : this(baseColor, amount, ThemeColor.MidGray)
    {
    }

    public Tone(ThemeColor baseColor, double amount, ThemeColor gray)
    {
        ThemeColor.ValidateAmount(amount);

        BaseColor = baseColor;
        Amount = amount;
        Gray = gray;
        Color = baseColor.MixWith(gray, amount);
    }

    public ThemeColor BaseColor { get; }

    /// <summary>How much gray is mixed in: 0 is the base color, 1 is the gray itself.</summary>
    public double Amount { get; }

    /// <summary>The gray mixed in. Defaults to <see cref="ThemeColor.MidGray"/>.</summary>
    public ThemeColor Gray { get; }

    public ThemeColor Color { get; }

    /// <summary>
    /// Builds <paramref name="steps"/> tones that get evenly more muted, ending
    /// just short of the gray (amounts 1/(steps+1) ... steps/(steps+1)).
    /// </summary>
    public static IReadOnlyList<Tone> Scale(ThemeColor baseColor, int steps) =>
        Scale(baseColor, steps, ThemeColor.MidGray);

    public static IReadOnlyList<Tone> Scale(ThemeColor baseColor, int steps, ThemeColor gray)
    {
        ArgumentOutOfRangeException.ThrowIfNegativeOrZero(steps);

        return Enumerable.Range(1, steps)
            .Select(i => new Tone(baseColor, (double)i / (steps + 1), gray))
            .ToList();
    }

    public override string ToString() => Color.ToHex();
}
