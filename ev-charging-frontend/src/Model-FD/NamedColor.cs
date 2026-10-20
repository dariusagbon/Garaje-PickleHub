namespace EvChargingQueue.ModelFD;

/// <summary>
/// One entry of Tints, Tones or Shades.
/// <see cref="Percent"/> is how much white, gray or black is mixed in;
/// <see cref="PrimaryPercent"/> is how much of the primary green (#0B6E3A) is used.
/// The two always add up to 100.
/// </summary>
public sealed record NamedColor(string Name, int Percent, string Hex)
{
    public int PrimaryPercent => 100 - Percent;
}
