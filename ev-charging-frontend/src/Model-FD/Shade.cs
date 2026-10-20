namespace EvChargingQueue.ModelFD;

/// <summary>
/// A shade is a base color mixed with black. Shades are used for text,
/// borders and hover/pressed states, such as a "Join queue" button.
/// </summary>
public sealed class Shade
{
    public Shade(ThemeColor baseColor, double amount)
    {
        ThemeColor.ValidateAmount(amount);

        BaseColor = baseColor;
        Amount = amount;
        Color = baseColor.MixWith(ThemeColor.Black, amount);
    }

    public ThemeColor BaseColor { get; }

    /// <summary>How much black is mixed in: 0 is the base color, 1 is pure black.</summary>
    public double Amount { get; }

    public ThemeColor Color { get; }

    /// <summary>
    /// Builds <paramref name="steps"/> shades that get evenly darker, ending
    /// just short of black (amounts 1/(steps+1) ... steps/(steps+1)).
    /// </summary>
    public static IReadOnlyList<Shade> Scale(ThemeColor baseColor, int steps)
    {
        ArgumentOutOfRangeException.ThrowIfNegativeOrZero(steps);

        return Enumerable.Range(1, steps)
            .Select(i => new Shade(baseColor, (double)i / (steps + 1)))
            .ToList();
    }

    public override string ToString() => Color.ToHex();
}
