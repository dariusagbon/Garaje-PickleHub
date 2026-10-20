namespace EvChargingQueue.Theme;

/// <summary>
/// One named color expanded into a 50–900 scale: tints above 500,
/// the base color at 500, shades below it, plus muted tones.
/// </summary>
public sealed class ColorScale
{
    // Step -> (how much white or black to mix in). 500 is the untouched base.
    private static readonly (int Step, double Amount)[] TintSteps =
        [(50, 0.90), (100, 0.80), (200, 0.60), (300, 0.40), (400, 0.20)];

    private static readonly (int Step, double Amount)[] ShadeSteps =
        [(600, 0.20), (700, 0.40), (800, 0.60), (900, 0.80)];

    public const int BaseStep = 500;

    public ColorScale(string name, ThemeColor baseColor, int toneCount = 3)
    {
        ArgumentException.ThrowIfNullOrWhiteSpace(name);

        Name = name;
        Base = baseColor;
        Tints = TintSteps.Select(s => new Tint(baseColor, s.Amount)).ToList();
        Shades = ShadeSteps.Select(s => new Shade(baseColor, s.Amount)).ToList();
        Tones = Tone.Scale(baseColor, toneCount);

        var steps = new SortedDictionary<int, ThemeColor> { [BaseStep] = baseColor };
        for (var i = 0; i < TintSteps.Length; i++)
        {
            steps[TintSteps[i].Step] = Tints[i].Color;
        }
        for (var i = 0; i < ShadeSteps.Length; i++)
        {
            steps[ShadeSteps[i].Step] = Shades[i].Color;
        }
        Steps = steps;
    }

    /// <summary>Lower-case identifier used for CSS variable names, e.g. "primary".</summary>
    public string Name { get; }

    public ThemeColor Base { get; }

    /// <summary>Lightest first (50, 100, 200, 300, 400).</summary>
    public IReadOnlyList<Tint> Tints { get; }

    /// <summary>Lightest first (600, 700, 800, 900).</summary>
    public IReadOnlyList<Shade> Shades { get; }

    /// <summary>Least muted first.</summary>
    public IReadOnlyList<Tone> Tones { get; }

    /// <summary>All steps from 50 to 900, in order.</summary>
    public IReadOnlyDictionary<int, ThemeColor> Steps { get; }

    public ThemeColor this[int step] =>
        Steps.TryGetValue(step, out var color)
            ? color
            : throw new KeyNotFoundException($"'{Name}' has no step {step}. Valid steps: {string.Join(", ", Steps.Keys)}.");
}
