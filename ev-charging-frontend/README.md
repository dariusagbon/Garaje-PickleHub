# EV Charging Queue — Front End

Blazor WebAssembly (.NET 8) front end for an EV charging queue. Colors come from the
**Model-FD** project (front-end models, namespace `EvChargingQueue.ModelFD`), where tints,
tones, shades and the palette each live in their own class.

## Layout

```
src/Model-FD/                     Front-end color models (no UI dependencies)
  ThemeColor.cs                   sRGB value type: hex parsing, mixing, WCAG contrast
  Tint.cs                         base color mixed with white  -> light surfaces
  Shade.cs                        base color mixed with black  -> text, borders, hover/pressed
  Tone.cs                         base color mixed with gray   -> muted / disabled states
  ColorScale.cs                   one color as a 50–900 scale (tints, base at 500, shades) + tones
  Palette.cs                      brand + charger-status scales, exported as CSS variables
src/EvChargingQueue.Web/          Blazor WASM app
  Components/ThemeStyles.razor    writes Palette.ToCssVariables() into <head>
  Pages/Home.razor                queue screen (station status cards)
  Pages/PalettePreview.razor      /palette — every tint, shade and tone as swatches
tests/Model-FD.Tests/             xUnit tests for the color models
```

## Usage

```csharp
var green = ThemeColor.FromHex("#00A878");

new Tint(green, 0.4).Color;     // 40% white
new Shade(green, 0.4).Color;    // 40% black
new Tone(green, 0.5).Color;     // 50% gray
Tint.Scale(green, 5);           // 5 evenly spaced tints

Palette.Default.Primary[700];   // a step from the scale
Palette.Default.ToCssVariables();
```

Palette roles: `primary`, `secondary`, `neutral`, and one per charger state —
`available`, `charging`, `waiting`, `fault`. The CSS variables follow
`--color-<role>-<step>` (50–900), `--color-<role>-<step>-on` (readable text color
on that step), `--color-<role>-on` and `--color-<role>-tone-<n>`:

```css
.station--charging { background: var(--color-charging-50); border-color: var(--color-charging-500); }
.btn-primary       { background: var(--color-primary-700); color: var(--color-primary-700-on); }
```

To change the brand, register a different `Palette` in `Program.cs` instead of `Palette.Default`.

## Run

```bash
dotnet run --project src/EvChargingQueue.Web
dotnet test
```
