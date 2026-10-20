namespace EvChargingQueue.ModelFD.Tests;

public class PaletteTests
{
    [Fact]
    public void BrandColors_MatchTheChargeQLogo()
    {
        var palette = new Palette();

        Assert.Equal("#0B6E3A", palette.Primary);
        Assert.Equal("#3A9A2E", palette.Secondary);
    }

    [Fact]
    public void Roles_PointAtTheirTintToneOrShadeVariables()
    {
        var palette = new Palette();

        Assert.Equal(palette.Shades.DarkJungle, palette.TextPrimary);
        Assert.Equal(palette.Tones.Juniper, palette.TextSecondary);
        Assert.Equal(palette.Tints.Jade, palette.BorderPrimary);
        Assert.Equal(palette.Tints.Honeydew, palette.BorderSecondary);
        Assert.Equal(palette.Tints.MintMist, palette.BackgroundPrimary);
        Assert.Equal(palette.Tints.MintCream, palette.BackgroundSecondary);
    }

    [Fact]
    public void Roles_FollowCustomizedVariablesPassedIn()
    {
        var tints = new Tints { MintMist = "#FFFFFF" };

        Assert.Equal("#FFFFFF", new Palette(tints, new Tones(), new Shades()).BackgroundPrimary);
    }

    [Theory]
    [InlineData(nameof(Palette.TextPrimary))]
    [InlineData(nameof(Palette.TextSecondary))]
    public void TextColors_MeetWcagAaOnBothBackgrounds(string role)
    {
        var palette = new Palette();
        var text = ThemeColor.FromHex(palette.Roles.Single(r => r.Name == role).Hex);

        Assert.True(text.ContrastRatio(ThemeColor.FromHex(palette.BackgroundPrimary)) >= 4.5);
        Assert.True(text.ContrastRatio(ThemeColor.FromHex(palette.BackgroundSecondary)) >= 4.5);
    }

    [Fact]
    public void Setter_RejectsInvalidHex()
    {
        var palette = new Palette();

        Assert.Throws<ArgumentException>(() => palette.TextPrimary = "not-a-color");
        Assert.Equal("#032111", palette.TextPrimary);
    }

    [Fact]
    public void ToCssVariables_EmitsRolesAndEveryNamedColor()
    {
        var css = new Palette().ToCssVariables();

        Assert.StartsWith(":root {", css);
        Assert.Contains("--color-primary: #0B6E3A;", css);
        Assert.Contains("--color-text-primary: #032111;", css);
        Assert.Contains("--color-background-secondary: #E7F1EB;", css);
        Assert.Contains("--color-on-primary: #FFFFFF;", css);
        Assert.Contains("--tint-mint-mist: #F3F8F5;", css);
        Assert.Contains("--tone-lichen: #46775D;", css);
        Assert.Contains("--shade-dark-jungle: #032111;", css);
        Assert.Equal(8 + 1 + 30, css.Split('\n').Count(l => l.TrimStart().StartsWith("--")));
    }
}
