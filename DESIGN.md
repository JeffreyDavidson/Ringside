---
name: Ringside
description: A sharp, editorial wrestling operations system built for independent promoters.
colors:
  signal: "#ff4b50"
  red: "#cb2028"
  red-deep: "#a61920"
  surface: "#101112"
  surface-header: "#09090a"
  surface-hero: "#060607"
  surface-hover: "#272729"
  text: "#f7f7f5"
  muted: "#bfc0c3"
  line: "#363638"
  signal-soft: "#ff8588"
typography:
  display:
    fontFamily: "Anton, Arial Narrow, Arial, sans-serif"
    fontSize: "clamp(3.2rem, 7.5vw, 7rem)"
    fontWeight: 400
    lineHeight: 1.13
    letterSpacing: "-0.015em"
  headline:
    fontFamily: "Anton, Arial Narrow, Arial, sans-serif"
    fontSize: "clamp(2.6rem, 4.5vw, 4.5rem)"
    fontWeight: 400
    lineHeight: 1.1
    letterSpacing: "-0.015em"
  body:
    fontFamily: "Arial, Helvetica, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.75
  label:
    fontFamily: "Arial, Helvetica, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.08em"
rounded:
  none: "0"
  pill: "999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
  section: "clamp(4rem, 7vw, 7rem)"
components:
  button-primary:
    backgroundColor: "{colors.red}"
    textColor: "{colors.text}"
    rounded: "{rounded.none}"
    padding: "0.875rem 1.75rem"
    height: "56px"
  button-outline:
    backgroundColor: "transparent"
    textColor: "{colors.text}"
    rounded: "{rounded.none}"
    padding: "0.875rem 1.75rem"
    height: "56px"
  feature-badge:
    backgroundColor: "transparent"
    textColor: "{colors.signal-soft}"
    rounded: "{rounded.pill}"
    padding: "0.45rem 0.9rem"
---

# Design System: Ringside

## Overview

**Creative North Star: “The Backstage Ledger.”**

Ringside should feel like the system that keeps a live wrestling promotion in control: direct, physical, editorial, and built around the work behind the bell. The interface uses a dark arena surface, compressed display type, hard horizontal rules, and a single hot red signal. It should have the confidence of a fight poster and the clarity of a well-run production sheet.

The standalone marketing site and the app’s public marketing surface share this system. The landing project implements it with static HTML and CSS; the app implements it with Blade, Tailwind, and the same semantic tokens. Content and calls to action may differ by context, but the visual decisions should stay aligned.

**Key Characteristics:**

- Dark, near-black surfaces with restrained tonal contrast.
- Anton uppercase display type paired with plain Arial body copy.
- Signal red used sparingly for action, emphasis, and the wordmark.
- Sharp rectangular controls and thin rules; pills are reserved for badges.
- Documentary wrestling imagery with strong crops and a readable scrim.

## Colors

The palette is deliberately narrow. Red carries action and emphasis; neutral gray carries structure and reading.

### Primary

- **Signal red** (#ff4b50): Small emphasis, highlighted display text, hover states, and the feature badge.
- **Ringside red** (#cb2028): Primary buttons, selection, and the main action color.
- **Deep red** (#a61920): Hover state for primary actions.

### Neutral

- **Canvas** (#101112): Default page background.
- **Header black** (#09090a): Header and navigation surface.
- **Hero black** (#060607): Image-backed hero fallback.
- **Text white** (#f7f7f5): Primary text and button labels.
- **Muted gray** (#bfc0c3): Supporting copy and secondary metadata.
- **Rule gray** (#363638): Dividers, borders, and structural lines.
- **Hover charcoal** (#272729): Outline-button and feature-row hover surface.

### Named Rules

**The Signal Rule.** Red is an event marker, not a background color. Use it for the action, the selected emphasis, or a small brand cue; do not flood a section with it.

## Typography

**Display Font:** Anton (with Arial Narrow and Arial fallbacks)

**Body Font:** Arial (with Helvetica fallback)

**Character:** Anton is compressed, uppercase, and physical. Arial keeps descriptions and controls neutral and readable. Do not introduce a second display face or a decorative script.

### Hierarchy

- **Display** (400, `clamp(3.2rem, 7.5vw, 7rem)`, 1.13): Hero statements and the strongest brand moments.
- **Headline** (400, `clamp(2.6rem, 4.5vw, 4.5rem)`, 1.1): Section titles and major marketing claims.
- **Title** (400, 1.5–2.5rem, 1.1): Feature names, workflow steps, and card titles.
- **Body** (400, 1.125rem, 1.75): Explanations and supporting marketing copy; keep paragraphs readable and short.
- **Label** (700, 0.75rem, 1.2, `0.08em`): Badges, metadata, and small uppercase system labels.

**The Type Contrast Rule.** Let Anton carry the voice and Arial carry the explanation. Never use Anton for long-form copy.

## Layout

Use a centered content rail: `width: min(100% - 6rem, 1280px)`. Full-bleed hero imagery and section backgrounds may extend beyond the rail, while text and controls stay aligned to it.

Sections use generous vertical padding, normally `clamp(4rem, 7vw, 7rem)`. Desktop layouts favor two-column editorial compositions such as `1.25fr 1fr`, with a `6rem` gap when space allows. Feature indexes and metadata rows use thin vertical rules. At the mobile breakpoint around `760px`, collapse columns, reduce the page gutter to about `1rem`, and preserve the display hierarchy rather than shrinking every element proportionally.

Keep a clear reading path: one dominant statement, one supporting paragraph, and one obvious action per decision area. Use `text-wrap: balance` for display headings and `text-wrap: pretty` for supporting copy.

## Elevation & Depth

The system is flat by default. Depth comes from tonal layering, photography, scrims, rules, and spacing rather than floating cards. The only routine shadow is a restrained red glow under the primary hero action (`0 12px 30px rgb(203 32 40 / 24%)`). Avoid generic gray drop shadows and glass effects.

## Shapes

Controls are square and decisive: buttons, rows, and panels use no radius. The feature badge is the one recurring pill shape (`999px`) because it behaves as a compact label. Use thin `1px` rules for structure. Focus rings are high-contrast white, `3px`, with a `6px` offset.

## Components

### Wordmark

- Anton, uppercase, tight tracking (`-0.035em`), approximately `2.75rem`.
- “RING” uses the primary text color; “SIDE” uses Signal red.
- Keep the wordmark compact and avoid adding an icon beside it.

### Buttons

- **Primary:** Ringside red background, white text, square shape, minimum height `56px`.
- **Outline:** Transparent background, muted-white `1px` border, square shape.
- **Hover:** Deep red for primary; charcoal fill and white border for outline.
- **Icon:** A simple 20px line icon aligned after the label with about `1rem` gap.
- **Focus:** Always preserve the global high-contrast focus ring.

### Hero

- Use a strong wrestling or arena photograph as a full-bleed background.
- Apply a dark left-to-right scrim so the headline remains readable.
- Keep the title to two short lines; highlight only the second line with Signal red.
- Place the badge, title, description, and actions in one vertical reading path.

### Feature index and rows

- Use thin rule-gray dividers and generous row padding.
- Put the label or title on the left and explanatory text on the right on desktop.
- Use a subtle charcoal hover state; do not turn rows into rounded cards.

### Cards and containers

- Prefer open editorial panels, bordered matrices, and tonal blocks over card piles.
- Use `#101112` or `#0b0c0d` surfaces with `#363638` rules.
- Keep internal padding at the `1.5rem`–`2rem` scale.

### Navigation and forms

- Navigation is compact, text-led, and action-oriented.
- Maintain at least a `44px` hit target for links and compact header actions.
- Inputs use the same dark canvas, thin rules, square shape, and white focus ring.
- Errors should use clear text and the red signal without relying on color alone.

## Do's and Don'ts

### Do:

- **Do** reuse these tokens in both repositories instead of inventing project-specific colors.
- **Do** keep public marketing pages sparse, confident, and easy to scan.
- **Do** use real wrestling imagery with deliberate crops and accessible alternative text.
- **Do** preserve keyboard focus, readable contrast, and 44px minimum interaction targets.
- **Do** let the app dashboard use the same palette and type pairing while increasing density for operational work.

### Don't:

- **Don't** add gradients, glassmorphism, excessive rounded cards, or generic SaaS blue.
- **Don't** use Anton for paragraphs, form values, or dense tables.
- **Don't** make every heading red; red marks action and emphasis.
- **Don't** create a second marketing design system in either repository.
- **Don't** copy marketing-only layout patterns into dense authenticated workflows without adapting their density.
