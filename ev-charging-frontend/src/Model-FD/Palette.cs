using System.Text;
using System.Text.RegularExpressions;

namespace EvChargingQueue.ModelFD;

/// <summary>
/// The colors the UI actually uses. Primary and secondary are the ChargeQ
/// brand greens; every other role points at a variable from
/// <see cref="Tints"/>, <see cref="Tones"/> or <see cref="Shades"/>.
/// Roles are read-only from outside; setters are protected, so a theme
/// reassigns them by subclassing <see cref="Palette"/>.
/// </summary>
public partial class Palette
{
    private string _primary = "#0B6E3A";
    private string _secondary = "#3A9A2E";
    private string _textPrimary;
    private string _textSecondary;
    private string _borderPrimary;
    private string _borderSecondary;
    private string _backgroundPrimary;
    private string _backgroundSecondary;

    public Palette()
        : this(new Tints(), new Tones(), new Shades())
    {
    }

    public Palette(Tints tints, Tones tones, Shades shades)
    {
        Tints = tints ?? throw new ArgumentNullException(nameof(tints));
        Tones = tones ?? throw new ArgumentNullException(nameof(tones));
        Shades = shades ?? throw new ArgumentNullException(nameof(shades));

        _textPrimary = shades.DarkJungle;
        _textSecondary = tones.Juniper;
        _borderPrimary = tints.Jade;
        _borderSecondary = tints.Honeydew;
        _backgroundPrimary = tints.MintMist;
        _backgroundSecondary = tints.MintCream;
    }

    public Tints Tints { get; }

    public Tones Tones { get; }

    public Shades Shades { get; }

    // ChargeQ deep green: car outline and tagline in the logo
    public string Primary
    {
        get => _primary;
        protected set => _primary = HexColor.Normalize(value, nameof(Primary));
    }

    // ChargeQ mid green: "CHARGEQ" wordmark in the logo
    public string Secondary
    {
        get => _secondary;
        protected set => _secondary = HexColor.Normalize(value, nameof(Secondary));
    }

    // Shades.DarkJungle (70% black)
    public string TextPrimary
    {
        get => _textPrimary;
        protected set => _textPrimary = HexColor.Normalize(value, nameof(TextPrimary));
    }

    // Tones.Juniper (40% gray)
    public string TextSecondary
    {
        get => _textSecondary;
        protected set => _textSecondary = HexColor.Normalize(value, nameof(TextSecondary));
    }

    // Tints.Jade (20% white)
    public string BorderPrimary
    {
        get => _borderPrimary;
        protected set => _borderPrimary = HexColor.Normalize(value, nameof(BorderPrimary));
    }

    // Tints.Honeydew (80% white)
    public string BorderSecondary
    {
        get => _borderSecondary;
        protected set => _borderSecondary = HexColor.Normalize(value, nameof(BorderSecondary));
    }

    // Tints.MintMist (95% white)
    public string BackgroundPrimary
    {
        get => _backgroundPrimary;
        protected set => _backgroundPrimary = HexColor.Normalize(value, nameof(BackgroundPrimary));
    }

    // Tints.MintCream (90% white)
    public string BackgroundSecondary
    {
        get => _backgroundSecondary;
        protected set => _backgroundSecondary = HexColor.Normalize(value, nameof(BackgroundSecondary));
    }

    /// <summary>The palette roles in display order.</summary>
    public IReadOnlyList<(string Name, string Hex)> Roles =>
    [
        (nameof(Primary), Primary),
        (nameof(Secondary), Secondary),
        (nameof(TextPrimary), TextPrimary),
        (nameof(TextSecondary), TextSecondary),
        (nameof(BorderPrimary), BorderPrimary),
        (nameof(BorderSecondary), BorderSecondary),
        (nameof(BackgroundPrimary), BackgroundPrimary),
        (nameof(BackgroundSecondary), BackgroundSecondary),
    ];

    /// <summary>
    /// Renders the palette as CSS custom properties: roles as
    /// <c>--color-text-primary</c>, and every named color as
    /// <c>--tint-mint-mist</c>, <c>--tone-lichen</c>, <c>--shade-dark-jungle</c>.
    /// <c>--color-on-primary</c> is black or white, whichever reads better on Primary.
    /// </summary>
    public string ToCssVariables(string selector = ":root")
    {
        var css = new StringBuilder();
        css.Append(selector).AppendLine(" {");

        foreach (var (name, hex) in Roles)
        {
            css.AppendLine($"  --color-{ToKebab(name)}: {hex};");
        }
        css.AppendLine($"  --color-on-primary: {ThemeColor.FromHex(Primary).ReadableForeground().ToHex()};");

        AppendGroup(css, "tint", Tints.All);
        AppendGroup(css, "tone", Tones.All);
        AppendGroup(css, "shade", Shades.All);

        css.AppendLine("}");
        return css.ToString();
    }

    private static void AppendGroup(StringBuilder css, string prefix, IEnumerable<NamedColor> colors)
    {
        foreach (var color in colors)
        {
            css.AppendLine($"  --{prefix}-{ToKebab(color.Name)}: {color.Hex};");
        }
    }

    private static string ToKebab(string pascalCase) =>
        WordBoundary().Replace(pascalCase, "-$1").ToLowerInvariant();

    [GeneratedRegex("(?<!^)([A-Z])")]
    private static partial Regex WordBoundary();
}
