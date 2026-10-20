namespace EvChargingQueue.ModelFD.Tests;

public class TintShadeToneTests
{
    private static readonly ThemeColor Base = new(0, 100, 200);

    [Fact]
    public void Tint_MixesTowardWhite()
    {
        Assert.Equal(Base, new Tint(Base, 0).Color);
        Assert.Equal(new ThemeColor(128, 178, 228), new Tint(Base, 0.5).Color);
        Assert.Equal(ThemeColor.White, new Tint(Base, 1).Color);
    }

    [Fact]
    public void Shade_MixesTowardBlack()
    {
        Assert.Equal(Base, new Shade(Base, 0).Color);
        Assert.Equal(new ThemeColor(0, 50, 100), new Shade(Base, 0.5).Color);
        Assert.Equal(ThemeColor.Black, new Shade(Base, 1).Color);
    }

    [Fact]
    public void Tone_MixesTowardGray()
    {
        Assert.Equal(Base, new Tone(Base, 0).Color);
        Assert.Equal(new ThemeColor(64, 114, 164), new Tone(Base, 0.5).Color);
        Assert.Equal(ThemeColor.MidGray, new Tone(Base, 1).Color);
    }

    [Fact]
    public void Tone_UsesCustomGray()
    {
        var warmGray = new ThemeColor(140, 130, 120);

        Assert.Equal(warmGray, new Tone(Base, 1, warmGray).Color);
    }

    [Fact]
    public void Scales_AreEvenlySpacedAndExcludeEndpoints()
    {
        var tints = Tint.Scale(Base, 3);
        var shades = Shade.Scale(Base, 3);
        var tones = Tone.Scale(Base, 3);

        Assert.Equal([0.25, 0.5, 0.75], tints.Select(t => t.Amount));
        Assert.Equal([0.25, 0.5, 0.75], shades.Select(s => s.Amount));
        Assert.Equal([0.25, 0.5, 0.75], tones.Select(t => t.Amount));
    }

    [Fact]
    public void Scales_GetLighterOrDarkerInOrder()
    {
        var tints = Tint.Scale(Base, 4).Select(t => t.Color.RelativeLuminance).ToList();
        var shades = Shade.Scale(Base, 4).Select(s => s.Color.RelativeLuminance).ToList();

        Assert.Equal(tints.Order(), tints);
        Assert.Equal(shades.OrderDescending(), shades);
    }

    [Fact]
    public void Scale_RejectsNonPositiveSteps()
    {
        Assert.Throws<ArgumentOutOfRangeException>(() => Tint.Scale(Base, 0));
        Assert.Throws<ArgumentOutOfRangeException>(() => Shade.Scale(Base, -1));
        Assert.Throws<ArgumentOutOfRangeException>(() => Tone.Scale(Base, 0));
    }

    [Fact]
    public void Constructors_RejectAmountOutsideZeroToOne()
    {
        Assert.Throws<ArgumentOutOfRangeException>(() => new Tint(Base, 1.5));
        Assert.Throws<ArgumentOutOfRangeException>(() => new Shade(Base, -0.5));
        Assert.Throws<ArgumentOutOfRangeException>(() => new Tone(Base, 2));
    }
}
