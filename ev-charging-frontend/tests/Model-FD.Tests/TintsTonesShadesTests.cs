namespace EvChargingQueue.ModelFD.Tests;

public class TintsTonesShadesTests
{
    private static readonly ThemeColor Primary = ThemeColor.FromHex("#0B6E3A");

    public static TheoryData<string, IReadOnlyList<NamedColor>> Groups => new()
    {
        { "Tints", new Tints().All },
        { "Tones", new Tones().All },
        { "Shades", new Shades().All },
    };

    public static TheoryData<IReadOnlyList<NamedColor>, ThemeColor> GroupsWithMixColor => new()
    {
        { new Tints().All, ThemeColor.White },
        { new Tones().All, ThemeColor.MidGray },
        { new Shades().All, ThemeColor.Black },
    };

    [Theory]
    [MemberData(nameof(Groups))]
    public void EachGroupHasTenUniquelyNamedColors(string group, IReadOnlyList<NamedColor> colors)
    {
        Assert.Equal(10, colors.Count);
        Assert.Equal(colors.Count, colors.Select(c => c.Name).Distinct().Count());
        Assert.Equal([10, 20, 30, 40, 50, 60, 70, 80, 90, 95], colors.Select(c => c.Percent));
        Assert.All(colors, c => Assert.False(c.Name.Contains(group.TrimEnd('s'), StringComparison.OrdinalIgnoreCase)));
    }

    [Theory]
    [MemberData(nameof(GroupsWithMixColor))]
    public void EachHexIsThePrimaryMixedByItsPercent(IReadOnlyList<NamedColor> colors, ThemeColor mixedWith)
    {
        Assert.All(colors, c =>
            Assert.Equal(Primary.MixWith(mixedWith, c.Percent / 100.0).ToHex(), c.Hex));
    }

    [Fact]
    public void Setter_AcceptsValidHexAndNormalizesIt()
    {
        var tints = new Tints { MintMist = "#abc" };

        Assert.Equal("#AABBCC", tints.MintMist);
    }

    [Theory]
    [InlineData(null)]
    [InlineData("")]
    [InlineData("green")]
    [InlineData("#12345")]
    public void Setter_RejectsInvalidHexAndKeepsTheOldValue(string? value)
    {
        var shades = new Shades();

        var error = Assert.Throws<ArgumentException>(() => shades.Onyx = value!);

        Assert.Equal(nameof(Shades.Onyx), error.ParamName);
        Assert.Equal("#010603", shades.Onyx);
    }

    [Fact]
    public void Instances_DoNotShareState()
    {
        var first = new Tones { Ash = "#000000" };

        Assert.NotEqual(first.Ash, new Tones().Ash);
    }
}
