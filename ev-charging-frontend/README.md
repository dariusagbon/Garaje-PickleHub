# EV Charging Queue — Front End

Blazor WebAssembly (.NET 8) front end for an EV charging queue. Colors come from the
**Model-FD** project (front-end models, namespace `EvChargingQueue.ModelFD`), where tints,
tones, shades and the palette each live in their own class.

## Layout

```
src/Model-FD/                     Front-end color models (namespace EvChargingQueue.ModelFD)
  Tints.cs                        10 named colors: primary green + white (10%–95%)
  Tones.cs                        10 named colors: primary green + gray  (10%–95%)
  Shades.cs                       10 named colors: primary green + black (10%–95%)
  Palette.cs                      Primary/Secondary hex + text/border/background roles
  HexColor.cs                     setter protection: rejects invalid hex, stores "#RRGGBB"
  NamedColor.cs                   (Name, Percent, Hex) entry used to list a class's colors
  ThemeColor.cs                   color math: hex parsing, mixing, WCAG contrast
src/EvChargingQueue.Web/          Blazor WASM app
  Components/ThemeStyles.razor    writes Palette.ToCssVariables() into <head>
  Pages/Home.razor                queue screen
  Pages/PalettePreview.razor      /palette — roles plus all 30 named colors
tests/Model-FD.Tests/             xUnit tests for the color models
```

## Model-FD

Each of `Tints`, `Tones` and `Shades` has 10 color variables with a descriptive name,
a comment giving its percentage, and a getter/setter. The setter is protected: invalid
hex throws `ArgumentException` and the old value is kept.

```csharp
// 95% white
public string MintMist
{
    get => _mintMist;
    set => _mintMist = HexColor.Normalize(value, nameof(MintMist));
}
```

All 30 are mixes of the ChargeQ primary green `#0B6E3A` (from the Figma logo); the tests
check every hex against that math.

`Palette` holds the two brand hex values and points each role at one of those variables:

| Role | Value |
|---|---|
| `Primary` | `#0B6E3A` (logo car outline, tagline) |
| `Secondary` | `#3A9A2E` (logo wordmark) |
| `TextPrimary` | `Shades.DarkJungle` (70% black) |
| `TextSecondary` | `Tones.Juniper` (40% gray) |
| `BorderPrimary` | `Tints.Jade` (20% white) |
| `BorderSecondary` | `Tints.Honeydew` (80% white) |
| `BackgroundPrimary` | `Tints.MintMist` (95% white) |
| `BackgroundSecondary` | `Tints.MintCream` (90% white) |

Both text colors meet WCAG AA (4.5:1) on both backgrounds; a test enforces it.

In CSS the roles are `--color-<role>` (e.g. `--color-text-primary`) and the named colors
are `--tint-<name>`, `--tone-<name>`, `--shade-<name>` (e.g. `--shade-deep-forest`).
`--color-on-primary` is the readable text color on `Primary`.

To customize, change a variable before building the palette, or set a role directly:

```csharp
var tints = new Tints { MintMist = "#FFFFFF" };
var palette = new Palette(tints, new Tones(), new Shades());
palette.TextSecondary = palette.Tones.Spruce;
```

## Run

```bash
dotnet run --project src/EvChargingQueue.Web
dotnet test
```
