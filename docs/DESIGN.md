---
name: Symo
colors:
  surface: '#faf9f9'
  surface-dim: '#dadada'
  surface-bright: '#faf9f9'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f3f3'
  surface-container: '#efeeed'
  surface-container-high: '#e9e8e8'
  surface-container-highest: '#e3e2e2'
  on-surface: '#1a1c1c'
  on-surface-variant: '#4a4457'
  inverse-surface: '#2f3131'
  inverse-on-surface: '#f1f0f0'
  outline: '#7b7489'
  outline-variant: '#ccc3da'
  surface-tint: '#7114ff'
  primary: '#5300c4'
  on-primary: '#ffffff'
  primary-container: '#6f02ff'
  on-primary-container: '#ddcdff'
  inverse-primary: '#d0bcff'
  secondary: '#600de6'
  on-secondary: '#ffffff'
  secondary-container: '#793cfe'
  on-secondary-container: '#f1e8ff'
  tertiary: '#004d4d'
  on-tertiary: '#ffffff'
  tertiary-container: '#006767'
  on-tertiary-container: '#55e9e9'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e9ddff'
  primary-fixed-dim: '#d0bcff'
  on-primary-fixed: '#23005c'
  on-primary-fixed-variant: '#5600ca'
  secondary-fixed: '#e9ddff'
  secondary-fixed-dim: '#cfbdff'
  on-secondary-fixed: '#22005d'
  on-secondary-fixed-variant: '#5300cc'
  tertiary-fixed: '#66f7f7'
  tertiary-fixed-dim: '#41dbdb'
  on-tertiary-fixed: '#002020'
  on-tertiary-fixed-variant: '#004f50'
  background: '#faf9f9'
  on-background: '#1a1c1c'
  surface-variant: '#e3e2e2'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  title-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1440px
  gutter: 1.5rem
  margin-mobile: 1rem
  margin-desktop: 2rem
  stack-xs: 0.25rem
  stack-sm: 0.5rem
  stack-md: 1rem
  stack-lg: 1.5rem
---

## Brand & Style
The design system is engineered for high-stakes financial administration, where clarity, precision, and trust are paramount. The brand personality has evolved to be more tech-forward and sophisticated, utilizing a vibrant violet primary tone to signify modern innovation while removing visual friction to allow users to focus on complex data sets.

The aesthetic follows a **Modern Corporate** style with a focus on functional minimalism. It utilizes high-contrast navigation areas to provide clear structural anchoring, while the primary workspace remains light and expansive. The emotional response should be one of calm control and professional reliability.

## Colors
This design system utilizes a structured palette to denote hierarchy and functional meaning. The neutral palette has been adjusted to a more balanced grey to enhance universal legibility and interface neutrality.

- **Electric Violet (#6F02FF):** The primary brand color, used for primary actions, active states, and core branding.
- **Deep Lavender (#7A3DFF):** Used for secondary actions and supporting visual elements to create a cohesive monochromatic-adjacent feel.
- **Bright Teal (#3BD7D7):** Reserved for specialized data highlights, tertiary actions, or unique financial indicators, providing a crisp, high-visibility contrast.
- **Balanced Grey (#9F9F9F):** The updated neutral palette used for borders, secondary text, and background surfaces to maintain a clean, low-strain reading environment.

## Typography
The system uses **Inter** for its exceptional legibility in data-heavy environments across all text roles.

Key rules:
- **Tabular Numerals:** Use `tabular-nums` for all data tables and financial figures to ensure columns of numbers align perfectly.
- **Hierarchy:** Use on-surface (Neutral 900) for headlines and Neutral 600 (Balanced Grey) for secondary body text to maintain clear information architecture.
- **Scanning:** Use `label-caps` for table headers and small section titles to distinguish them from interactive content.

## Layout & Spacing
The layout employs a **Fluid Grid** system within a max-width container of 1440px. 

- **Sidebar:** Fixed at 280px for desktop. It collapses to a 64px icon-only rail or hides behind a hamburger menu on mobile.
- **Dashboard Grid:** Utilizes a 12-column system. Standard widgets should span 3, 4, or 6 columns. Large data tables should span 12.
- **Rhythm:** An 8px base unit drives all spacing. Use `stack-md` (16px) for most component internal spacing and `stack-lg` (24px) for spacing between distinct widgets or cards.

## Elevation & Depth
The design system uses **Tonal Layers** combined with **Ambient Shadows** to create a structured hierarchy:

- **Level 0 (Background):** Surface. No shadow.
- **Level 1 (Cards/Widgets):** Pure White. 1px Neutral 200 border and a "Soft Drop" shadow.
- **Level 2 (Dropdowns/Modals):** Pure White. 1px Neutral 200 border and a "Deep Ambient" shadow.

Avoid heavy blurs; depth is primarily suggested through thin, high-clarity borders using the Balanced Grey neutral palette.

## Shapes
The shape language is professional and modern, favoring **Rounded (8px)** corners for standard UI components.

- **Standard Elements:** Cards, input fields, and buttons use a 0.5rem (8px) radius.
- **Large Elements:** Modals and large feature banners use a 1rem (16px) radius.
- **Small Elements:** Tooltips and badges use a 0.25rem (4px) radius.
- **Interactive States:** Clickable areas should show a subtle background change within the defined radius to confirm focus.

## Components
- **Buttons:** Primary buttons use Electric Violet background with white text. Secondary buttons use a white background with a Neutral 300 border and Electric Violet text.
- **Data Tables:** High-density with 1px Neutral 100 horizontal dividers. Row hover states should use Surface-Container-Low. Headers use `label-caps` typography.
- **Input Fields:** 8px rounded corners, 1px Neutral 300 border. Focus state uses a 2px Electric Violet ring.
- **Chips/Badges:** Used for status. Positive indicators use a semantic green, while "Tertiary" or specialized status uses Bright Teal.
- **Charts:** Use a custom palette: Electric Violet (Primary), Deep Lavender (Secondary Data), and Bright Teal (Tertiary Data/Growth).
- **Sidebar Nav:** Surface-Container background. Active states use a vertical Electric Violet 4px pill indicator on the left edge.