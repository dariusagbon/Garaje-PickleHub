namespace EvChargingQueue.ModelFD;

/// <summary>
/// The ChargeQ primary green (#0B6E3A) mixed with gray (#808080), from 10% to 95%.
/// Muted colors for secondary text and disabled states.
/// Colors are read-only from outside; setters are protected, so a theme
/// changes them by subclassing (e.g. <c>class DarkThemeTones : Tones</c>).
/// </summary>
public class Tones
{
    private string _evergreen = "#177041";
    private string _pine = "#227248";
    private string _spruce = "#2E734F";
    private string _juniper = "#3A7556";
    private string _lichen = "#46775D";
    private string _thyme = "#517964";
    private string _rosemary = "#5D7B6B";
    private string _greyMoss = "#697C72";
    private string _pewter = "#747E79";
    private string _ash = "#7A7F7D";

    // 10% gray + 90% primary (#0B6E3A)
    public string Evergreen
    {
        get => _evergreen;
        protected set => _evergreen = HexColor.Normalize(value, nameof(Evergreen));
    }

    // 20% gray + 80% primary (#0B6E3A)
    public string Pine
    {
        get => _pine;
        protected set => _pine = HexColor.Normalize(value, nameof(Pine));
    }

    // 30% gray + 70% primary (#0B6E3A)
    public string Spruce
    {
        get => _spruce;
        protected set => _spruce = HexColor.Normalize(value, nameof(Spruce));
    }

    // 40% gray + 60% primary (#0B6E3A)
    public string Juniper
    {
        get => _juniper;
        protected set => _juniper = HexColor.Normalize(value, nameof(Juniper));
    }

    // 50% gray + 50% primary (#0B6E3A)
    public string Lichen
    {
        get => _lichen;
        protected set => _lichen = HexColor.Normalize(value, nameof(Lichen));
    }

    // 60% gray + 40% primary (#0B6E3A)
    public string Thyme
    {
        get => _thyme;
        protected set => _thyme = HexColor.Normalize(value, nameof(Thyme));
    }

    // 70% gray + 30% primary (#0B6E3A)
    public string Rosemary
    {
        get => _rosemary;
        protected set => _rosemary = HexColor.Normalize(value, nameof(Rosemary));
    }

    // 80% gray + 20% primary (#0B6E3A)
    public string GreyMoss
    {
        get => _greyMoss;
        protected set => _greyMoss = HexColor.Normalize(value, nameof(GreyMoss));
    }

    // 90% gray + 10% primary (#0B6E3A)
    public string Pewter
    {
        get => _pewter;
        protected set => _pewter = HexColor.Normalize(value, nameof(Pewter));
    }

    // 95% gray + 5% primary (#0B6E3A)
    public string Ash
    {
        get => _ash;
        protected set => _ash = HexColor.Normalize(value, nameof(Ash));
    }

    /// <summary>All 10 colors in order of percent, for listing and CSS export.</summary>
    public IReadOnlyList<NamedColor> All =>
    [
        new(nameof(Evergreen), 10, Evergreen),
        new(nameof(Pine), 20, Pine),
        new(nameof(Spruce), 30, Spruce),
        new(nameof(Juniper), 40, Juniper),
        new(nameof(Lichen), 50, Lichen),
        new(nameof(Thyme), 60, Thyme),
        new(nameof(Rosemary), 70, Rosemary),
        new(nameof(GreyMoss), 80, GreyMoss),
        new(nameof(Pewter), 90, Pewter),
        new(nameof(Ash), 95, Ash)
    ];
}
