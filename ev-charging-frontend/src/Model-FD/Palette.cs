using System.Text;

namespace EvChargingQueue.ModelFD;

/// <summary>
/// The full color palette for the EV charging queue UI: brand colors plus
/// one status color per charger/queue state, each expanded into a scale.
/// </summary>
public sealed class Palette
{
    public Palette(
        ThemeColor primary,
        ThemeColor secondary,
        ThemeColor neutral,
        ThemeColor available,
        ThemeColor charging,
        ThemeColor waiting,
        ThemeColor fault)
    {
        Primary = new ColorScale("primary", primary);
        Secondary = new ColorScale("secondary", secondary);
        Neutral = new ColorScale("neutral", neutral);
        Available = new ColorScale("available", available);
        Charging = new ColorScale("charging", charging);
        Waiting = new ColorScale("waiting", waiting);
        Fault = new ColorScale("fault", fault);
    }

    /// <summary>Default EV brand: electric green with a cool blue accent.</summary>
    public static Palette Default { get; } = new(
        primary: ThemeColor.FromHex("#00A878"),
        secondary: ThemeColor.FromHex("#2563EB"),
        neutral: ThemeColor.FromHex("#64748B"),
        available: ThemeColor.FromHex("#22C55E"),
        charging: ThemeColor.FromHex("#0EA5E9"),
        waiting: ThemeColor.FromHex("#F59E0B"),
        fault: ThemeColor.FromHex("#EF4444"));

    /// <summary>Main brand color: app bar, primary buttons, links.</summary>
    public ColorScale Primary { get; }

    /// <summary>Accent color: secondary actions, highlights, charts.</summary>
    public ColorScale Secondary { get; }

    /// <summary>Text, borders, backgrounds and disabled states.</summary>
    public ColorScale Neutral { get; }

    /// <summary>A charger is free to use.</summary>
    public ColorScale Available { get; }

    /// <summary>A vehicle is plugged in and charging.</summary>
    public ColorScale Charging { get; }

    /// <summary>A driver is in the queue waiting for a charger.</summary>
    public ColorScale Waiting { get; }

    /// <summary>A charger is out of service or a session failed.</summary>
    public ColorScale Fault { get; }

    public IReadOnlyList<ColorScale> All =>
        [Primary, Secondary, Neutral, Available, Charging, Waiting, Fault];

    /// <summary>
    /// Renders every scale as CSS custom properties, e.g.
    /// <c>--color-primary-500</c>, <c>--color-primary-500-on</c> (readable text on that step),
    /// <c>--color-primary-on</c> (text on the base color) and <c>--color-primary-tone-1</c>.
    /// </summary>
    public string ToCssVariables(string selector = ":root")
    {
        var css = new StringBuilder();
        css.Append(selector).AppendLine(" {");

        foreach (var scale in All)
        {
            foreach (var (step, color) in scale.Steps)
            {
                css.AppendLine($"  --color-{scale.Name}-{step}: {color.ToHex()};");
                css.AppendLine($"  --color-{scale.Name}-{step}-on: {color.ReadableForeground().ToHex()};");
            }

            css.AppendLine($"  --color-{scale.Name}-on: {scale.Base.ReadableForeground().ToHex()};");

            for (var i = 0; i < scale.Tones.Count; i++)
            {
                css.AppendLine($"  --color-{scale.Name}-tone-{i + 1}: {scale.Tones[i].Color.ToHex()};");
            }
        }

        css.AppendLine("}");
        return css.ToString();
    }
}
