namespace EvChargingQueue.ModelFD;

/// <summary>
/// The ChargeQ primary green (#0B6E3A) mixed with white, from 10% to 95%.
/// Light colors for backgrounds and borders.
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

    // 10% white
    public string FernGreen
    {
        get => _fernGreen;
        set => _fernGreen = HexColor.Normalize(value, nameof(FernGreen));
    }

    // 20% white
    public string Jade
    {
        get => _jade;
        set => _jade = HexColor.Normalize(value, nameof(Jade));
    }

    // 30% white
    public string Eucalyptus
    {
        get => _eucalyptus;
        set => _eucalyptus = HexColor.Normalize(value, nameof(Eucalyptus));
    }

    // 40% white
    public string Seafoam
    {
        get => _seafoam;
        set => _seafoam = HexColor.Normalize(value, nameof(Seafoam));
    }

    // 50% white
    public string Sage
    {
        get => _sage;
        set => _sage = HexColor.Normalize(value, nameof(Sage));
    }

    // 60% white
    public string Celadon
    {
        get => _celadon;
        set => _celadon = HexColor.Normalize(value, nameof(Celadon));
    }

    // 70% white
    public string PastelMint
    {
        get => _pastelMint;
        set => _pastelMint = HexColor.Normalize(value, nameof(PastelMint));
    }

    // 80% white
    public string Honeydew
    {
        get => _honeydew;
        set => _honeydew = HexColor.Normalize(value, nameof(Honeydew));
    }

    // 90% white
    public string MintCream
    {
        get => _mintCream;
        set => _mintCream = HexColor.Normalize(value, nameof(MintCream));
    }

    // 95% white
    public string MintMist
    {
        get => _mintMist;
        set => _mintMist = HexColor.Normalize(value, nameof(MintMist));
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
