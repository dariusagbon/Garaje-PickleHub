namespace EvChargingQueue.Theme;

/// <summary>
/// A tint is a base color mixed with white. Tints are used for light
/// surfaces, such as the background of a station card that is available.
/// </summary>
public sealed class Tint
{
    public Tint(ThemeColor baseColor, double amount)
    {
        ThemeColor.ValidateAmount(amount);

        BaseColor = baseColor;
        Amount = amount;
        Color = baseColor.MixWith(ThemeColor.White, amount);
    }

    public ThemeColor BaseColor { get; }

    /// <summary>How much white is mixed in: 0 is the base color, 1 is pure white.</summary>
    public double Amount { get; }

    public ThemeColor Color { get; }

    /// <summary>
    /// Builds <paramref name="steps"/> tints that get evenly lighter, ending
    /// just short of white (amounts 1/(steps+1) ... steps/(steps+1)).
    /// </summary>
    public static IReadOnlyList<Tint> Scale(ThemeColor baseColor, int steps)
    {
        ArgumentOutOfRangeException.ThrowIfNegativeOrZero(steps);

        return Enumerable.Range(1, steps)
            .Select(i => new Tint(baseColor, (double)i / (steps + 1)))
            .ToList();
    }

    public override string ToString() => Color.ToHex();
}
