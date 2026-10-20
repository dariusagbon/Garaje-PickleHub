namespace EvChargingQueue.Theme.Tests;

public class ThemeColorTests
{
    [Theory]
    [InlineData("#00A878", 0x00, 0xA8, 0x78)]
    [InlineData("00a878", 0x00, 0xA8, 0x78)]
    [InlineData("#fff", 0xFF, 0xFF, 0xFF)]
    [InlineData(" #0F8 ", 0x00, 0xFF, 0x88)]
    public void FromHex_ParsesSupportedFormats(string hex, byte r, byte g, byte b)
    {
        Assert.Equal(new ThemeColor(r, g, b), ThemeColor.FromHex(hex));
    }

    [Theory]
    [InlineData("")]
    [InlineData("#12345")]
    [InlineData("#GGGGGG")]
    public void FromHex_RejectsInvalidInput(string hex)
    {
        Assert.Throws<FormatException>(() => ThemeColor.FromHex(hex));
    }

    [Fact]
    public void ToHex_RoundTrips()
    {
        Assert.Equal("#00A878", ThemeColor.FromHex("#00a878").ToHex());
    }

    [Fact]
    public void MixWith_InterpolatesEachChannel()
    {
        var mixed = new ThemeColor(0, 100, 200).MixWith(ThemeColor.White, 0.5);

        Assert.Equal(new ThemeColor(128, 178, 228), mixed);
    }

    [Theory]
    [InlineData(-0.1)]
    [InlineData(1.1)]
    [InlineData(double.NaN)]
    public void MixWith_RejectsAmountOutsideZeroToOne(double amount)
    {
        Assert.Throws<ArgumentOutOfRangeException>(() => ThemeColor.Black.MixWith(ThemeColor.White, amount));
    }

    [Fact]
    public void ContrastRatio_BlackOnWhiteIs21()
    {
        Assert.Equal(21, ThemeColor.Black.ContrastRatio(ThemeColor.White), precision: 5);
    }

    [Fact]
    public void ReadableForeground_PicksTheHigherContrastColor()
    {
        Assert.Equal(ThemeColor.Black, ThemeColor.FromHex("#FDE68A").ReadableForeground());
        Assert.Equal(ThemeColor.White, ThemeColor.FromHex("#1E3A8A").ReadableForeground());
    }
}
