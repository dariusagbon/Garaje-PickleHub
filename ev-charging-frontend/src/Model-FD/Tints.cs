namespace EvChargingQueue.ModelFD;

/// <summary>
/// The ChargeQ primary green (#0B6E3A) mixed with white, from 10% to 95%.
/// Light colors for backgrounds and borders.
/// Colors are read-only from outside; setters are protected, so a theme
/// changes them by subclassing (e.g. <c>class DarkThemeTints : Tints</c>).
/// </summary>
public class Tints
{
    private string _fernGreen = "#237D4E";
    private string _jade = "#3C8B61";
    private string _eucalyptus = "#549A75";
    private string _seafoam = "#6DA889";
    private string _sage = "#85B79D";
    private string _celadon = "#9DC5B0";
    private string _pastelMint = "#B6D4C4";
    private string _honeydew = "#CEE2D8";
    private string _mintCream = "#E7F1EB";
    private string _mintMist = "#F3F8F5";

    // 10% white + 90% primary (#0B6E3A)
    public string FernGreen
    {
        get => _fernGreen;
        protected set => _fernGreen = HexColor.Normalize(value, nameof(FernGreen));
    }

    // 20% white + 80% primary (#0B6E3A)
    public string Jade
    {
        get => _jade;
        protected set => _jade = HexColor.Normalize(value, nameof(Jade));
    }

    // 30% white + 70% primary (#0B6E3A)
    public string Eucalyptus
    {
        get => _eucalyptus;
        protected set => _eucalyptus = HexColor.Normalize(value, nameof(Eucalyptus));
    }

    // 40% white + 60% primary (#0B6E3A)
    public string Seafoam
    {
        get => _seafoam;
        protected set => _seafoam = HexColor.Normalize(value, nameof(Seafoam));
    }

    // 50% white + 50% primary (#0B6E3A)
    public string Sage
    {
        get => _sage;
        protected set => _sage = HexColor.Normalize(value, nameof(Sage));
    }

    // 60% white + 40% primary (#0B6E3A)
    public string Celadon
    {
        get => _celadon;
        protected set => _celadon = HexColor.Normalize(value, nameof(Celadon));
    }

    // 70% white + 30% primary (#0B6E3A)
    public string PastelMint
    {
        get => _pastelMint;
        protected set => _pastelMint = HexColor.Normalize(value, nameof(PastelMint));
    }

    // 80% white + 20% primary (#0B6E3A)
    public string Honeydew
    {
        get => _honeydew;
        protected set => _honeydew = HexColor.Normalize(value, nameof(Honeydew));
    }

    // 90% white + 10% primary (#0B6E3A)
    public string MintCream
    {
        get => _mintCream;
        protected set => _mintCream = HexColor.Normalize(value, nameof(MintCream));
    }

    // 95% white + 5% primary (#0B6E3A)
    public string MintMist
    {
        get => _mintMist;
        protected set => _mintMist = HexColor.Normalize(value, nameof(MintMist));
    }

    /// <summary>All 10 colors in order of percent, for listing and CSS export.</summary>
    public IReadOnlyList<NamedColor> All =>
    [
        new(nameof(FernGreen), 10, FernGreen),
        new(nameof(Jade), 20, Jade),
        new(nameof(Eucalyptus), 30, Eucalyptus),
        new(nameof(Seafoam), 40, Seafoam),
        new(nameof(Sage), 50, Sage),
        new(nameof(Celadon), 60, Celadon),
        new(nameof(PastelMint), 70, PastelMint),
        new(nameof(Honeydew), 80, Honeydew),
        new(nameof(MintCream), 90, MintCream),
        new(nameof(MintMist), 95, MintMist)
    ];
}
