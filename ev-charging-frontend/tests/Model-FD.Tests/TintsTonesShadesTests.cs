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
    public void Subclass_CanSetAColorAndItIsNormalized()
    {
        Assert.Equal("#AABBCC", new CustomTints("#abc").MintMist);
    }

    [Theory]
    [InlineData(null)]
    [InlineData("")]
    [InlineData("green")]
    [InlineData("#12345")]
    public void Subclass_SettingInvalidHexThrows(string? value)
    {
        var error = Assert.Throws<ArgumentException>(() => new CustomShades(value!));

        Assert.Equal(nameof(Shades.Onyx), error.ParamName);
    }

    [Theory]
    [InlineData(typeof(Tints))]
    [InlineData(typeof(Tones))]
    [InlineData(typeof(Shades))]
    public void EveryColorHasAPublicGetterAndAProtectedSetter(Type type)
    {
        var colors = type.GetProperties().Where(p => p.PropertyType == typeof(string)).ToList();

        Assert.Equal(10, colors.Count);
        Assert.All(colors, p =>
        {
            Assert.True(p.GetMethod!.IsPublic, $"{type.Name}.{p.Name} getter should be public");
            Assert.True(p.SetMethod!.IsFamily, $"{type.Name}.{p.Name} setter should be protected");
        });
    }

    private sealed class CustomTints : Tints
    {
        public CustomTints(string mintMist) => MintMist = mintMist;
    }

    private sealed class CustomShades : Shades
    {
        public CustomShades(string onyx) => Onyx = onyx;
    }
}
