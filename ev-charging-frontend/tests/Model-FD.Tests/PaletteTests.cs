namespace EvChargingQueue.ModelFD.Tests;

public class PaletteTests
{
    [Fact]
    public void ColorScale_HasStepsFrom50To900WithBaseAt500()
    {
        var scale = new ColorScale("primary", ThemeColor.FromHex("#00A878"));

        Assert.Equal([50, 100, 200, 300, 400, 500, 600, 700, 800, 900], scale.Steps.Keys);
        Assert.Equal(scale.Base, scale[500]);
        Assert.Equal(scale.Tints[0].Color, scale[50]);
        Assert.Equal(scale.Shades[^1].Color, scale[900]);
    }

    [Fact]
    public void ColorScale_StepsGetDarkerFrom50To900()
    {
        var luminance = new ColorScale("fault", ThemeColor.FromHex("#EF4444"))
            .Steps.Values.Select(c => c.RelativeLuminance).ToList();

        Assert.Equal(luminance.OrderDescending(), luminance);
    }

    [Fact]
    public void ColorScale_UnknownStepThrows()
    {
        var scale = new ColorScale("primary", ThemeColor.Black);

        Assert.Throws<KeyNotFoundException>(() => scale[550]);
    }

    [Fact]
    public void Default_HasOneScalePerRoleWithUniqueNames()
    {
        var names = Palette.Default.All.Select(s => s.Name).ToList();

        Assert.Equal(["primary", "secondary", "neutral", "available", "charging", "waiting", "fault"], names);
    }

    [Fact]
    public void ToCssVariables_EmitsStepsForegroundAndTones()
    {
        var css = Palette.Default.ToCssVariables();

        Assert.StartsWith(":root {", css);
        Assert.Contains("--color-primary-500: #00A878;", css);
        Assert.Contains("--color-primary-50: ", css);
        Assert.Contains("--color-fault-900: ", css);
        Assert.Contains("--color-primary-900-on: #FFFFFF;", css);
        Assert.Contains("--color-waiting-on: #000000;", css);
        Assert.Contains("--color-neutral-tone-3: ", css);
        Assert.EndsWith("}" + Environment.NewLine, css);
    }
}
