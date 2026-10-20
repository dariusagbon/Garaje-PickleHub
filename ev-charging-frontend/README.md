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
a comment giving its percentages, and a getter/setter. Each comment gives both halves
of the mix, e.g. `// 95% white + 5% primary (#0B6E3A)`. In code, every entry of a class's
`All` list has `Percent` (white/gray/black mixed in) and `PrimaryPercent` (primary green
used); the two always add up to 100. Two layers of protection:

- **`protected set`**: anyone can read a color, but only the class itself or a subclass can
  change it. `tints.MintMist = "#FFF"` from outside is a compile error.
- **Validation**: the setter only accepts a valid hex color (stored as `#RRGGBB`); anything
  else throws `ArgumentException`.

```csharp
// 95% white + 5% primary (#0B6E3A)
public string MintMist
{
    get => _mintMist;
    protected set => _mintMist = HexColor.Normalize(value, nameof(MintMist));
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

To customize, subclass. Change a variable, then pass it to the palette, or reassign a
role in a `Palette` subclass:

```csharp
public class WhiteTints : Tints
{
    public WhiteTints() => MintMist = "#FFFFFF";
}

public class HighContrastPalette : Palette
{
    public HighContrastPalette() : base(new WhiteTints(), new Tones(), new Shades())
    {
        TextSecondary = Tones.Spruce;
    }
}
```

Register it in `src/EvChargingQueue.Web/Program.cs` with
`builder.Services.AddSingleton<Palette>(new HighContrastPalette());`.

## Open in VS Code

1. Install the [.NET 8 SDK](https://dotnet.microsoft.com/download/dotnet/8.0) (newer SDKs work too) and [VS Code](https://code.visualstudio.com/).
2. In VS Code choose **File → Open Folder…** and pick this `ev-charging-frontend` folder.
3. Accept the prompt to install the recommended extensions (C# Dev Kit).
4. Press **F5** to build and run the app in the browser with the debugger attached,
   or run a task from **Terminal → Run Task…**:
   - `build` (default build task, Ctrl+Shift+B)
   - `test` (runs the xUnit tests)
   - `watch` (runs the app at http://localhost:5148 and reloads on save)

## Run

```bash
dotnet run --project src/EvChargingQueue.Web
dotnet test
```
