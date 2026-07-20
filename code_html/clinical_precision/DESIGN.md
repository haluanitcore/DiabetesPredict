---
name: Clinical Precision
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#424752'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#727783'
  outline-variant: '#c2c6d4'
  surface-tint: '#005db6'
  primary: '#00478d'
  on-primary: '#ffffff'
  primary-container: '#005eb8'
  on-primary-container: '#c8daff'
  inverse-primary: '#a9c7ff'
  secondary: '#006e06'
  on-secondary: '#ffffff'
  secondary-container: '#91f77e'
  on-secondary-container: '#007306'
  tertiary: '#244877'
  on-tertiary: '#ffffff'
  tertiary-container: '#3e6091'
  on-tertiary-container: '#c6dbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d6e3ff'
  primary-fixed-dim: '#a9c7ff'
  on-primary-fixed: '#001b3d'
  on-primary-fixed-variant: '#00468c'
  secondary-fixed: '#94fa81'
  secondary-fixed-dim: '#79dd68'
  on-secondary-fixed: '#002200'
  on-secondary-fixed-variant: '#005303'
  tertiary-fixed: '#d5e3ff'
  tertiary-fixed-dim: '#a6c8ff'
  on-tertiary-fixed: '#001c3b'
  on-tertiary-fixed-variant: '#234776'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  headline-lg:
    fontFamily: Public Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Public Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-sm:
    fontFamily: Public Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Public Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Public Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-md:
    fontFamily: Public Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Public Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.4'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 8px
  container-max: 1200px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 40px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style
The design system is engineered to balance clinical authority with patient-centric accessibility. Its primary goal is to foster a sense of security and reliability, essential for a platform delivering sensitive health predictions. The brand personality is professional, calm, and meticulous. 

Drawing from **Modern Corporate** and **Minimalist** movements, the UI utilizes generous whitespace to reduce cognitive load, allowing users to focus on health data without distraction. The aesthetic avoids cold, sterile visuals in favor of a "human-centered clinical" approach—where precision meets empathy.

## Colors
The palette is rooted in "Medical Blues" to establish immediate industry credibility. The primary blue is authoritative yet vibrant enough to feel modern. The secondary "Health Green" is reserved for success states, health-positive indicators, and "good" prediction ranges, providing a calming psychological cue.

Backgrounds utilize "Soft Whites"—off-white tones that reduce screen glare compared to pure hex white (#FFFFFF), making the interface more comfortable for long-form data entry. Neutrals are cool-toned grays, maintaining a cohesive clinical feel across borders and secondary text.

## Typography
This design system utilizes **Public Sans** for its institutional clarity and neutral, trustworthy character. It was chosen for its high x-height and distinct letterforms, which remain legible even for users with visual impairments—a critical consideration in healthcare.

Hierarchy is established primarily through weight and scale. Headlines use a bold weight to anchor sections, while body copy follows a generous 1.6 line-height ratio to ensure maximum readability for educational content. Labels are slightly tracked out and set in a semi-bold weight to differentiate them clearly from input data.

## Layout & Spacing
The design system employs a **Fixed Grid** model for core tool flows (prediction forms) to maintain focus and a **Fluid Grid** for educational dashboards. A 12-column system is used with a 24px gutter to ensure breathing room between data points.

Spacing follows an 8px linear scale. Generous vertical padding (stack-lg) is applied between distinct sections of the prediction form to prevent "form fatigue." Information density should be kept low to medium to ensure the user feels guided rather than overwhelmed.

## Elevation & Depth
Depth is conveyed through **Tonal Layers** and **Low-Contrast Outlines** rather than heavy shadows. This keeps the interface feeling "flat" and clinical. 

Surface tiers are used to organize content:
- **Level 0 (Background):** Soft white (#F8FAFC).
- **Level 1 (Cards/Containers):** Pure white (#FFFFFF) with a 1px border (#E2E8F0).
- **Level 2 (Modals/Active Popovers):** Pure white with a soft, diffused 10% opacity blue-tinted shadow to indicate temporary elevation.

This approach ensures that the prediction results stand out as the highest point in the visual hierarchy without breaking the clean, professional aesthetic.

## Shapes
The design system uses a **Rounded** (Level 2) shape language. A base border-radius of 0.5rem (8px) is applied to buttons, input fields, and small containers. Larger components, such as educational cards or history panels, use 1rem (16px) for a softer, more approachable feel.

This roundedness serves to "soften" the clinical data, making the experience feel more like a modern health companion and less like a legacy medical record system.

## Components

### Data Input Forms
Input fields must feature large hit areas (minimum 48px height) and clear, persistent labels. Focused states use a 2px primary blue stroke. Error states must use a high-contrast red accompanied by an icon to ensure accessibility for color-blind users.

### Educational Cards
Cards are designed for high-readiness reading. They feature a Level 1 elevation (border only) and use the secondary green for accents or "Read More" links. Images or icons within cards should have the same rounded corners as the container.

### List-Based History Views
History views use a clean, row-based layout with subtle 1px dividers. Each row should clearly display the date, prediction result, and a "Trend Indicator" (e.g., a small arrow or color chip). Ensure generous horizontal padding within list items to prevent a cramped appearance.

### Buttons
- **Primary:** Solid medical blue with white text.
- **Secondary:** Outlined medical blue with 1px stroke.
- **Success:** Solid health green for "Save" or "Complete" actions.
All buttons utilize the base 0.5rem roundedness.

### Progress Indicators
For multi-step prediction flows, use a linear progress bar at the top of the container. The bar should use a light-blue track with a primary-blue fill to show advancement without adding visual clutter.