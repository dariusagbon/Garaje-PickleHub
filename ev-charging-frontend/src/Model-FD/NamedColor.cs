namespace EvChargingQueue.ModelFD;

/// <summary>One entry of Tints, Tones or Shades: its name, mix percentage and hex value.</summary>
public sealed record NamedColor(string Name, int Percent, string Hex);
