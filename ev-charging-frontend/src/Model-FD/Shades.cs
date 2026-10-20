namespace EvChargingQueue.ModelFD;

/// <summary>
/// The ChargeQ primary green (#0B6E3A) mixed with black, from 10% to 95%.
/// Dark colors for text and hover/pressed states.
/// </summary>
public class Shades
{
    private string _forestGreen = "#0A6334";
    private string _deepForest = "#09582E";
    private string _hunterGreen = "#084D29";
    private string _woodland = "#074223";
    private string _bottleGreen = "#06371D";
    private string _midnightGreen = "#042C17";
    private string _darkJungle = "#032111";
    private string _ebony = "#02160C";
    private string _blackForest = "#010B06";
    private string _onyx = "#010603";

    // 10% black
    public string ForestGreen
    {
        get => _forestGreen;
        set => _forestGreen = HexColor.Normalize(value, nameof(ForestGreen));
    }

    // 20% black
    public string DeepForest
    {
        get => _deepForest;
        set => _deepForest = HexColor.Normalize(value, nameof(DeepForest));
    }

    // 30% black
    public string HunterGreen
    {
        get => _hunterGreen;
        set => _hunterGreen = HexColor.Normalize(value, nameof(HunterGreen));
    }

    // 40% black
    public string Woodland
    {
        get => _woodland;
        set => _woodland = HexColor.Normalize(value, nameof(Woodland));
    }

    // 50% black
    public string BottleGreen
    {
        get => _bottleGreen;
        set => _bottleGreen = HexColor.Normalize(value, nameof(BottleGreen));
    }

    // 60% black
    public string MidnightGreen
    {
        get => _midnightGreen;
        set => _midnightGreen = HexColor.Normalize(value, nameof(MidnightGreen));
    }

    // 70% black
    public string DarkJungle
    {
        get => _darkJungle;
        set => _darkJungle = HexColor.Normalize(value, nameof(DarkJungle));
    }

    // 80% black
    public string Ebony
    {
        get => _ebony;
        set => _ebony = HexColor.Normalize(value, nameof(Ebony));
    }

    // 90% black
    public string BlackForest
    {
        get => _blackForest;
        set => _blackForest = HexColor.Normalize(value, nameof(BlackForest));
    }

    // 95% black
    public string Onyx
    {
        get => _onyx;
        set => _onyx = HexColor.Normalize(value, nameof(Onyx));
    }

    /// <summary>All 10 colors in order of percent, for listing and CSS export.</summary>
    public IReadOnlyList<NamedColor> All =>
    [
        new(nameof(ForestGreen), 10, ForestGreen),
        new(nameof(DeepForest), 20, DeepForest),
        new(nameof(HunterGreen), 30, HunterGreen),
        new(nameof(Woodland), 40, Woodland),
        new(nameof(BottleGreen), 50, BottleGreen),
        new(nameof(MidnightGreen), 60, MidnightGreen),
        new(nameof(DarkJungle), 70, DarkJungle),
        new(nameof(Ebony), 80, Ebony),
        new(nameof(BlackForest), 90, BlackForest),
        new(nameof(Onyx), 95, Onyx)
    ];
}
