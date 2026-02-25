# WP AGENT BUILD BIBLE
## Academisch Onderzoek: Perfecte WordPress/Divi Site Building via API
**Date:** 2026-02-25 | **Versie:** 1.0
**Doel:** Training document voor LOIQ WP Agent — elke regel hier wordt wet

---

## MINDSET: €100K+ AGENCY STANDAARD

> Denk altijd: "Hoe zou een agency die websites maakt die starten bij €100.000 dit bouwen?"

Dit betekent:
- **Elk detail telt.** Geen haastige shortcuts, geen "goed genoeg".
- **Alles bewerkbaar door de klant.** Als Claude ineens weg is, moet de klant ALLES kunnen aanpassen in Divi Visual Builder. Geen fragiele scripts, geen hardcoded hacks.
- **Design system denken.** Global Colors, Global Fonts, Presets — zodat kleur/font wijzigingen op 1 plek de hele site updaten.
- **Professionele afwerking.** Admin labels, responsive check op 3 breakpoints, performance optimalisatie, Divi Role Editor voor client handoff.
- **Icons via FontAwesome Pro of SVG** — altijd de hoogste kwaliteit. FA Pro is beschikbaar (licentie actief). Gebruik SVG inline waar performance kritiek is.

---

## DEEL 1: BUILD FLOW — DE JUISTE VOLGORDE

### De Gouden Volgorde (Fase 1-7)

```
FASE 1: FUNDAMENT (dag 1)
├── 1.1 Child theme scaffolden
├── 1.2 Global Colors instellen (Divi → Theme Customizer)
├── 1.3 Global Fonts instellen (heading + body)
├── 1.4 Logo uploaden naar Media Library
└── 1.5 Site Identity (titel, tagline, favicon)

FASE 2: NAVIGATIE (dag 1)
├── 2.1 Menu aanmaken in Appearance → Menus
├── 2.2 Placeholder pagina's aanmaken (draft/publish)
├── 2.3 Menu items koppelen aan pagina's
├── 2.4 Menu locatie toewijzen (Primary)
└── 2.5 Max Mega Menu activeren + configureren (als gebruikt)

FASE 3: THEME BUILDER (dag 1-2)
├── 3.1 Global Header bouwen
│   ├── Top bar (optioneel: telefoon, email, social)
│   ├── Main header (logo + menu)
│   └── Sticky/scroll behaviour
├── 3.2 Global Footer bouwen
│   ├── Footer columns (info, links, contact)
│   ├── Copyright bar
│   └── Social icons
├── 3.3 Blog Post template
├── 3.4 Archive/Category template
├── 3.5 404 template
└── 3.6 Search Results template

FASE 4: PAGINA'S BOUWEN (dag 2-4)
├── 4.1 Homepage
├── 4.2 Over Ons
├── 4.3 Diensten (overzicht + individueel)
├── 4.4 Contact
├── 4.5 Blog/Nieuws
└── 4.6 Overige pagina's

FASE 5: FORMULIEREN & FUNCTIONALITEIT (dag 4)
├── 5.1 Gravity Forms maken
├── 5.2 Formulieren embedden in pagina's
├── 5.3 Notifications + Confirmations instellen
├── 5.4 FacetWP configureren (als filter nodig)
└── 5.5 Overige plugins configureren

FASE 6: SEO & OPTIMALISATIE (dag 4-5)
├── 6.1 Ultrax SEO configureren (NAP, schema, templates)
├── 6.2 Meta titles + descriptions per pagina
├── 6.3 Afbeeldingen optimaliseren (alt tekst, compressie)
├── 6.4 Redirects instellen (oude site → nieuwe URLs)
├── 6.5 Sitemap verifiëren
└── 6.6 Performance check (Divi performance opties)

FASE 7: HANDOFF & HARDENING (dag 5)
├── 7.1 Divi Role Editor configureren (client = Editor role)
├── 7.2 Client user aanmaken
├── 7.3 Testrun door alle pagina's (desktop + mobile)
├── 7.4 Backup maken
├── 7.5 WP Agent timer uitzetten
└── 7.6 Documentatie voor klant
```

### Waarom deze volgorde

**Global Colors + Fonts EERST:** Alles wat je daarna bouwt pikt automatisch de juiste kleuren en fonts op. Als je dit later doet, moet je terug door elke pagina om te fixen.

**Menu + placeholder pagina's VOOR Theme Builder:** De header heeft een menu nodig om correct te renderen. Zonder menu zie je een lege header en kun je niet testen.

**Theme Builder VOOR pagina's:** Header en footer zijn op elke pagina zichtbaar. Als je pagina's bouwt zonder header/footer, bouw je blind — je weet niet hoe content eruitziet in context.

**Formulieren NA pagina's:** Je moet weten waar formulieren komen voordat je ze maakt. Contact pagina's bestaan al, je embedt het formulier erin.

**SEO LAATST:** SEO meta vereist dat content er is. Titles en descriptions schrijven voor lege pagina's is zinloos.

---

## DEEL 2: "ZO HOORT HET" REGELS

### 2.1 Logo Implementatie

**DE JUISTE MANIER:**
- Logo uploaden naar WordPress Media Library
- In Divi Theme Builder header: **Image Module** met het logo uit de media library
- Logo klikbaar maken → linkt naar homepage
- Alt tekst instellen op bedrijfsnaam
- Max Mega Menu logo: instellen via MMM settings → Logo → WP Media Library image
- Retina: upload logo op 2x grootte, display op 1x afmetingen

**FOUT:**
- Logo als achtergrondafbeelding via CSS
- Logo hardcoded in child theme functions.php
- Logo als base64 inline in een Code Module
- Logo via externe URL (CDN) zonder fallback

**Waarom:** Klant moet logo kunnen vervangen via Media Library of Divi Visual Builder. Geen code nodig.

---

### 2.2 Contact Informatie (Telefoon, Email, Adres)

**DE JUISTE MANIER:**
- NAP data centraal opslaan in Ultrax SEO settings (of Divi Theme Options)
- In header/footer: gebruik **shortcodes** `[ultrax_phone]`, `[ultrax_email]`, `[ultrax_address]`
- Of: Divi **Text Module** met de contactgegevens als tekst (niet als afbeelding)
- Telefoonnummer: altijd als `<a href="tel:+31612345678">` link
- Email: altijd als `<a href="mailto:">` link
- Adres: in `<address>` tag voor schema

**FOUT:**
- Contactinfo hardcoden in child theme PHP
- Contactinfo als afbeelding plaatsen
- Verschillende telefoonnummers op verschillende pagina's (tenzij multi-location)
- Contact info in een Code Module met inline HTML

**Waarom:** Bij verhuizing/nummer wijziging: 1 plek aanpassen, overal bijgewerkt. Shortcodes zijn de cleanste oplossing.

---

### 2.3 Social Media Icons

**DE JUISTE MANIER:**
- Divi **Social Media Follow Module** in header en/of footer
- Of: Max Mega Menu social icons via MMM settings
- Icons linken naar social profiles (niet hardcoded URLs in CSS)
- `target="_blank"` + `rel="noopener noreferrer"` op externe links
- Icon style consistent met site design (kleur of monochroom)

**FOUT:**
- Font Awesome icons via Code Module
- Social icons als losse afbeeldingen
- Inline SVGs in een Code Module
- Custom icon font laden voor 4 icoontjes

**Waarom:** Divi Social Media Follow Module is built-in, klant kan URLs wijzigen in Visual Builder, responsive out of the box, accessible (aria labels).

---

### 2.4 Icons (Algemeen)

**DE JUISTE MANIER:**
- **FontAwesome Pro** (licentie actief) — gebruik FA Pro classes in Divi modules die icons ondersteunen
- **SVG inline** via Code Module waar performance kritiek is (hero, above-the-fold)
- Divi's eigen icon picker voor modules die dat ondersteunen (Blurb, Toggle, etc.)
- Icon kleur via Divi module settings (niet CSS override)
- Consistent icon style door hele site (outlined OF filled, niet mix)

**FOUT:**
- Meerdere icon libraries laden (FA + Material + custom = bloat)
- Icons als PNG/JPG afbeeldingen (niet schaalbaar, niet kleurbaar)
- Icon fonts inline laden via Code Module `<link>` tag
- Icons zonder aria-label of sr-only tekst (accessibility)

**Waarom:** FA Pro is beschikbaar en biedt de hoogste kwaliteit + breedste dekking. SVG voor performance-kritieke situaties. Nooit meer laden dan nodig.

---

### 2.5 Formulier Plaatsing (Gravity Forms in Divi)

**DE JUISTE MANIER:**
- Gravity Form maken in Forms → New Form
- In Divi pagina: **Code Module** met shortcode: `[gravityform id="1" title="false" description="false" ajax="true"]`
- `ajax="true"` voor smooth submit zonder page reload
- `title="false"` als de pagina al een eigen heading heeft
- Styling via Gravity Forms global settings of dedicated CSS (niet inline op shortcode)
- Of: **WP Tools Gravity Forms Divi Module** plugin voor visuele styling in Divi Builder

**ACCEPTABEL ALTERNATIEF:**
- Shortcode in Text Module (werkt, maar Code Module is cleaner)

**FOUT:**
- Divi's eigen Contact Form Module gebruiken voor complexe formulieren (te beperkt)
- Gravity Forms shortcode in child theme PHP hardcoden
- Formulier styling met `!important` overrides in Divi Custom CSS
- Gravity Forms "Orbital" theme met Divi (incompatibel — gebruik "Gravity Forms 2.5 Theme")

**Waarom:** Gravity Forms is het formulier-systeem. Divi is het layout-systeem. Formulier logica (notifications, confirmations, conditional fields) hoort in GF, layout hoort in Divi.

---

### 2.6 Afbeeldingen

**DE JUISTE MANIER:**
- Upload via WordPress Media Library
- Alt tekst ALTIJD invullen (SEO + accessibility)
- WebP format waar mogelijk (of JPEG/PNG met compressie)
- Afbeeldingen op juiste formaat uploaden (niet 4000x3000 voor een thumbnail)
- Hero images: max 1920px breed, geoptimaliseerd onder 200KB
- Thumbnails: crop/resize via WordPress image sizes
- In Divi: Image Module of achtergrondafbeelding via section/row settings

**RICHTLIJNEN:**
| Gebruik | Max breedte | Max bestandsgrootte |
|---------|-------------|---------------------|
| Hero/banner | 1920px | 200KB |
| Content image | 1200px | 150KB |
| Thumbnail | 400px | 50KB |
| Logo | 400px | 30KB |
| OG image | 1200x630px | 100KB |

**FOUT:**
- Afbeeldingen zonder alt tekst
- Ongecomprimeerde PNG's van 5MB
- Afbeeldingen via externe URLs zonder caching
- Inline base64 afbeeldingen in CSS
- Decorative images met beschrijvende alt tekst (gebruik `alt=""` voor decoratief)

---

### 2.7 Blog/Archive Pagina

**DE JUISTE MANIER:**
- **Blog Post template** via Divi Theme Builder (niet een statische pagina)
- Template bevat: Post Title (dynamic), Post Content (dynamic), Author, Date, Categories, Featured Image
- **Archive template** via Theme Builder met Blog Module of Post Grid
- H1 = category/archive naam (dynamic)
- Pagination onderaan
- Sidebar optioneel (maar consistent: of overal sidebar, of nergens)

**FOUT:**
- Blog pagina als statische pagina met handmatig gekopieerde posts
- Geen featured image ondersteuning in template
- Archive zonder H1 heading
- Blog Module hardcoded op homepage in plaats van via Theme Builder template

---

### 2.8 Header Structuur

**DE JUISTE MANIER (Theme Builder Global Header):**
```
Section 1 (optioneel): Top Bar
├── Row: telefoon links | email midden | social rechts
├── Achtergrond: donkere kleur of accent
└── Klein font, compact

Section 2: Main Header
├── Row: 1/4 + 3/4 (of custom)
│   ├── Column 1: Logo (Image Module, link naar /)
│   └── Column 2: Menu Module (of MMM shortcode in Code Module)
├── Sticky positie (fixed on scroll)
└── Transparante achtergrond op homepage (optioneel)
```

**Max Mega Menu integratie:**
- Menu aanmaken in Appearance → Menus
- MMM activeren voor die menu locatie
- In Theme Builder: **Code Module** met `[maxmegamenu location=primary]`
- NIET: Divi Menu Module (die conflicteert met MMM)
- Divi dropdown animatie: zet op "Fade" (Customize → Header → Primary Menu Bar)
- CSS voor Divi mobile menu verbergen (MMM vervangt beide desktop en mobile)

**FOUT:**
- Logo + menu in dezelfde module (niet onafhankelijk aanpasbaar)
- Header zonder sticky functionaliteit
- Menu items hardcoded in Code Module HTML
- Meerdere menu locaties met dezelfde items

---

### 2.9 Footer Structuur

**DE JUISTE MANIER (Theme Builder Global Footer):**
```
Section 1: Main Footer
├── Row: 3 of 4 kolommen
│   ├── Kolom 1: Logo + korte beschrijving
│   ├── Kolom 2: Navigatie links
│   ├── Kolom 3: Contact info (shortcodes)
│   └── Kolom 4: Openingstijden / social (optioneel)

Section 2: Copyright Bar
├── Row: 1 kolom of 2 kolommen
│   ├── "© {jaar} {bedrijfsnaam}" (dynamisch jaar)
│   └── Privacy / Voorwaarden links
```

**Dynamisch jaar:**
Code Module: `© <script>document.write(new Date().getFullYear())</script> Bedrijfsnaam`
Of: PHP in child theme functions.php met shortcode `[ultrax_year]`

**FOUT:**
- Footer als content op elke pagina gekopieerd
- Hardcoded "© 2024" (veroudert elk jaar)
- Footer met te veel content (footer is afsluiter, niet homepage 2.0)

---

### 2.10 CSS Aanpassingen

**DE JUISTE MANIER (in volgorde van voorkeur):**
1. **Divi Module Settings** — gebruik Divi's eigen styling opties (spacing, colors, fonts)
2. **Divi Presets** — maak presets voor herhalende module styles
3. **Divi Custom CSS per module** — in Module → Advanced → Custom CSS
4. **Child theme style.css** — voor site-wide CSS overrides
5. **Divi Theme Options → Custom CSS** — als child theme niet beschikbaar

**FOUT:**
- CSS in Code Modules (niet beheerbaar, verspreid over pagina's)
- `!important` overal (specificity problemen)
- Inline styles op HTML elementen
- CSS in functions.php via `wp_enqueue_style` voor simpele overrides
- Media queries hardcoded in Code Module (gebruik Divi responsive instellingen)

**Principe:** Divi settings > Presets > Module CSS > Child theme > Custom CSS. Van meest specifiek/beheerbaar naar minst.

---

### 2.11 Divi Global Presets & Design System

**DE JUISTE MANIER:**
- Definieer **Global Colors** (primary, secondary, accent, dark, light, grijs)
- Definieer **Global Fonts** (heading font + body font)
- Maak **Presets** per module type:
  - Button preset (primary, secondary, outline)
  - Heading presets (H1, H2, H3)
  - Text preset (body text)
  - Image preset (rounded corners, shadow)
- Gebruik presets consequent op elke pagina
- Update preset = update overal

**Waarom:** Dit is het Divi-equivalent van een CSS design system. Klant kan kleuren wijzigen op 1 plek → hele site updatet. Presets zijn CSS classes in Divi-taal.

---

### 2.12 Divi Performance Instellingen

**ALTIJD inschakelen:**
- Divi → Theme Options → General → Performance:
  - Dynamic Module Framework
  - Dynamic CSS
  - Dynamic JavaScript Libraries
  - Critical CSS
  - Defer Gutenberg Block CSS
  - Dynamic Icons (v4.13+)

**Waarom:** Vermindert initial page load significant. Divi laadt alleen CSS/JS voor modules die op de pagina staan.

---

### 2.13 Client Handoff

**DE JUISTE MANIER:**
- Client krijgt **Editor** role (niet Administrator)
- Divi Role Editor configureren:
  - Page editing: aan
  - Module editing: aan
  - Theme Options: uit
  - Divi Library (export/import): uit
  - Split Testing: uit
  - Portability: uit
- Client kan tekst en afbeeldingen wijzigen in Visual Builder
- Client kan GEEN layout structuur breken
- Admin account voor agency (ultrax.agency email)

**FOUT:**
- Client als Administrator (kan plugins deactiveren, theme switchen, site breken)
- Geen Divi Role Editor configuratie (client ziet alle opties)
- Gedeeld admin account

---

## DEEL 3: DIVI-SPECIFIEKE PATRONEN

### 3.1 Section → Row → Column → Module Hierarchie

```
ALTIJD:
Section
└── Row (met column structuur)
    └── Column
        └── Module(s)

NOOIT:
- Module direct in Section (zonder Row)
- Geneste Rows meer dan 2 levels diep (Row Inner max 1 level)
- Specialty Section tenzij echt nodig (complexer te onderhouden)
```

### 3.2 Admin Labels

**ALTIJD** admin labels zetten op Sections:
```
Section: "Hero"
Section: "Features"
Section: "Testimonials"
Section: "CTA"
Section: "Contact Form"
```

Dit maakt navigatie in de builder en Layer View overzichtelijk. Zonder labels is het een anonieme lijst van grijze blokken.

### 3.3 Responsive Design

**Check volgorde:**
1. Desktop layout bouwen
2. Tablet aanpassingen (768px breakpoint)
3. Mobile aanpassingen (480px breakpoint)

**Per breakpoint checken:**
- Font sizes leesbaar?
- Spacing proportioneel?
- Kolommen gestackt? (Divi doet dit automatisch, maar check)
- Afbeeldingen niet cropped?
- Menu werkt op mobile?
- CTA buttons full-width op mobile?

### 3.4 Veelgebruikte Module Patronen

**Hero Section:**
```
Fullwidth Section
└── Fullwidth Header Module
    ├── Title: H1 (dynamisch of custom)
    ├── Subtitle: tagline
    ├── Button 1: CTA (primary)
    ├── Button 2: Secondary (optioneel)
    └── Background: image met overlay
```

**Features/Diensten Grid:**
```
Section
└── Row (3 kolommen)
    ├── Blurb Module (icon + title + text)
    ├── Blurb Module
    └── Blurb Module
```

**Testimonials:**
```
Section
└── Row (1 kolom)
    └── Testimonial Module (of Slider met Testimonials)
```

**CTA Banner:**
```
Section (achtergrondkleur of image)
└── Row (1 kolom, centered)
    ├── Text Module (heading)
    └── Button Module (CTA)
```

---

## DEEL 4: API-SPECIFIEKE REGELS VOOR WP AGENT

### 4.1 Volgorde bij API Calls

```python
# CORRECT volgorde voor site build via WP Agent API:

# 1. Read: inventariseer wat er is
GET /claude/v1/status
GET /claude/v1/plugins
GET /claude/v1/theme
GET /claude/v3/page/list
GET /claude/v3/menu/list
GET /claude/v3/taxonomy/list

# 2. Scaffold: basis neerzetten
POST /claude/v3/child-theme/create (als nog niet bestaat)
POST /claude/v3/media/upload (logo, OG image)

# 3. Pages: content structuur
POST /claude/v3/page/create (homepage, diensten, over-ons, contact, blog)

# 4. Navigation
POST /claude/v3/menu/create
POST /claude/v3/menu/items/add
POST /claude/v3/menu/assign (primary location)

# 5. Theme Builder
POST /claude/v3/theme-builder/create (global header)
POST /claude/v3/theme-builder/create (global footer)
POST /claude/v3/theme-builder/create (blog post template)
POST /claude/v3/theme-builder/assign

# 6. Content bouwen
POST /claude/v3/divi/build (page layouts met Divi shortcodes)

# 7. Forms
POST /claude/v3/forms/create (contact form)
# Embed via page update met [gravityform] shortcode

# 8. Facets (als nodig)
POST /claude/v3/facet/create
POST /claude/v3/facet/template

# 9. SEO
# Via Ultrax SEO admin of WP Agent option writes

# 10. Finalize
GET /claude/v3/page/list (verify alles bestaat)
```

### 4.2 Dry-Run First

**ALTIJD** dry_run=true bij eerste poging van een write operatie. Check het response. Dan pas dry_run=false.

### 4.3 Session Tracking

Elke build sessie krijgt een `X-Claude-Session` header. Dit maakt rollback per sessie mogelijk als iets misgaat.

### 4.4 Template-Based Building

Gebruik de JSON templates (`templates/homepage.json`, etc.) als startpunt. Templates bevatten Divi shortcode structuur met `{{placeholders}}`. Vervang placeholders met klantgegevens → deploy via API.

---

## DEEL 5: CSS ARCHITECTUUR — ONE SOURCE OF TRUTH

> Principe: elke CSS regel heeft precies 1 juiste plek. Nooit hetzelfde tweemaal schrijven.

### 5.1 CSS Cascade — Waar Wat Hoort

```
LAAG 1: CHILD THEME functions.php (enqueueing)
├── FontAwesome Pro laden via wp_enqueue_style()
├── Google Fonts / Variable Fonts laden
├── GSAP laden (als nodig)
└── Custom scripts/styles registreren

LAAG 2: CHILD THEME style.css (sitewide CSS)
├── CSS Custom Properties (:root design tokens)
├── Fluid typography scale (clamp)
├── Fluid spacing scale (clamp)
├── OKLCH kleurpaletten
├── Global component styles (buttons, cards, forms)
├── Glassmorphism utility classes
├── Bento grid utility classes
├── Animation keyframes
├── prefers-reduced-motion overrides
├── Dark mode overrides (@media prefers-color-scheme)
└── Responsive overrides die sitewide gelden

LAAG 3: DIVI GLOBAL PRESETS (design system in Divi)
├── Button presets (primary, secondary, outline, ghost)
├── Heading presets (H1, H2, H3 met fluid sizes)
├── Text presets (body, lead, small, caption)
├── Image presets (rounded, shadow, hover-zoom)
├── Blurb presets (icon-top, icon-left, card-style)
└── Section presets (light, dark, accent, glass)

LAAG 4: DIVI MODULE SETTINGS (per module instance)
├── Spacing (padding, margin)
├── Colors (background, text — via Global Colors)
├── Typography (via preset of per-module)
└── Transform, hover, scroll effects

LAAG 5: DIVI CUSTOM CSS PER MODULE (Module → Advanced → Custom CSS)
├── Specifieke overrides voor dit ene module instance
└── Pseudo-elements (::before, ::after) op module level

LAAG 6: DIVI PAGE-LEVEL CUSTOM CSS (Page Settings → Custom CSS)
├── CSS die ALLEEN voor deze specifieke pagina geldt
├── Layout tweaks specifiek voor deze pagina
└── Pagina-specifieke animaties of overrides

LAAG 7: THEME BUILDER TEMPLATE CSS (via child theme of Global Presets)
├── Blog post template styling (alle blog posts globaal)
├── Archive template styling
├── Header/footer specifieke styles
└── 404/search template styles
```

### 5.2 One Source of Truth Regels

| Wat | Waar | NOOIT hier |
|-----|------|-----------|
| **Kleurpaletten** | `:root` CSS vars in child theme | Hardcoded hex in modules |
| **Font sizes** | `clamp()` scale in child theme | Vaste px in Divi module settings |
| **Spacing scale** | `clamp()` vars in child theme | Willekeurige padding per module |
| **Button styles** | Divi Global Preset "Primary Button" | Per-button handmatig stylen |
| **FA Pro icons** | `wp_enqueue_style` in functions.php | `<link>` tag in Code Module |
| **Blog styling** | Theme Builder template + child theme CSS | Per-post handmatig |
| **Sitewide overrides** | Child theme style.css | Divi Theme Options Custom CSS |
| **Pagina-specifiek** | Divi Page Custom CSS | Child theme met page-ID selectors |

### 5.3 FontAwesome Pro Setup (Child Theme)

```php
// functions.php — FA Pro enqueue
function ultrax_enqueue_fontawesome_pro() {
    wp_enqueue_style(
        'font-awesome-pro',
        'https://kit.fontawesome.com/JOUW-KIT-ID.js', // of lokaal
        array(),
        '6.5.0'
    );
}
add_action('wp_enqueue_scripts', 'ultrax_enqueue_fontawesome_pro');
```

Gebruik in Divi: module_class `fa-solid fa-icon-name` of in Code Module HTML.
Divi Blurb Module: icon picker ondersteunt FA icons native als FA Pro geladen is.

### 5.4 Snel Werken met CSS Tokens

```css
/* child theme style.css — Design Tokens (ONE source) */
:root {
    /* Kleuren — wijzig hier, hele site updatet */
    --brand-primary: oklch(0.55 0.20 250);
    --brand-accent: oklch(0.65 0.25 25);
    --brand-dark: oklch(0.15 0.02 260);
    --brand-light: oklch(0.97 0.005 260);
    --brand-gray: oklch(0.55 0.01 260);

    /* Typography — fluid scale */
    --fs-display: clamp(3rem, 2rem + 4vw, 8rem);
    --fs-h1: clamp(2.5rem, 1.8rem + 2.5vw, 5rem);
    --fs-h2: clamp(1.75rem, 1.3rem + 1.5vw, 3rem);
    --fs-h3: clamp(1.25rem, 1rem + 0.8vw, 2rem);
    --fs-body: clamp(1rem, 0.95rem + 0.2vw, 1.125rem);

    /* Spacing — fluid scale */
    --space-xs: clamp(0.75rem, 0.6rem + 0.5vw, 1rem);
    --space-s: clamp(1rem, 0.8rem + 0.65vw, 1.5rem);
    --space-m: clamp(1.5rem, 1.2rem + 1vw, 2.25rem);
    --space-l: clamp(2rem, 1.5rem + 1.6vw, 3rem);
    --space-xl: clamp(3rem, 2rem + 3vw, 5rem);
    --space-2xl: clamp(4rem, 2.5rem + 5vw, 8rem);

    /* Radius */
    --radius-sm: clamp(8px, 1vw, 12px);
    --radius-md: clamp(12px, 1.5vw, 24px);
    --radius-lg: clamp(16px, 2vw, 32px);

    /* Transitions */
    --ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
    --ease-enter: cubic-bezier(0, 0, 0.2, 1);
    --ease-exit: cubic-bezier(0.4, 0, 1, 1);
    --duration-fast: 200ms;
    --duration-normal: 300ms;
    --duration-slow: 500ms;
}
```

**Voordeel:** Wijzig 1 variabele → hele site updatet. Klant wisselt van blauw naar groen? Verander `--brand-primary`, klaar. Jij werkt snel, klant is flexibel.

---

## DEEL 6: 2030-READY DESIGN STANDAARD

> Elke site die we bouwen moet eruit zien alsof hij in 2030 is ontworpen.
> ALTIJD research doen naar de nieuwste design trends VOOR je begint te bouwen.
> Eerste output = finale output. Research eerst, dan pas pixels.

### 6.1 Research-First Protocol (NIET ONDERHANDELBAAR)

Bij ELKE nieuwe site build:
1. **Branche-research:** WebSearch naar "beste [branche] websites 2025 2026" + Awwwards/CSDA winnaars in die niche
2. **Design trend check:** Wat zijn de cutting-edge patterns voor dit type site?
3. **Concurrentie-analyse:** Hoe zien de top 3 concurrenten eruit? Wat kunnen we beter?
4. **Klant design system:** Welke kleuren, fonts, tone past bij dit merk?
5. **Dan pas bouwen** — met al die kennis in je hoofd

### 6.2 Wat "2030 Styling" Concreet Betekent

| 2020 (VERMIJD) | 2030-Ready (DIT BOUWEN WE) |
|-----------------|--------------------------|
| Statische hero met centered text op stockfoto | Typography-as-hero: oversized kinetic type, geen image nodig |
| Symmetrische 12-column Bootstrap grid | Asymmetrische bento grids met organische whitespace |
| Hover = kleur verandering | Micro-interacties: translateY, shadow-shift, scale, 300ms smooth easing |
| Fade-in als enige animatie | Scroll-driven narrative: content onthult met scroll progress |
| RGB/hex kleuren | OKLCH kleurensysteem met wide-gamut P3 |
| Vaste font sizes (px) | Fluid clamp() typography: hero 80-200px desktop, 48px mobile |
| Vaste breakpoints | Fluid alles: clamp() voor type + spacing, container queries voor componenten |
| Lichte achtergrond, donker = afterthought | Dark mode als first-class citizen (of dark-first design) |
| Platte kaartjes | Glassmorphic depth layers met frosted panels |
| Template-look | Menselijke imperfectie: organische vormen, bewuste asymmetrie |

### 6.3 De 10 Geboden van 2030-Ready Design

1. **Typography is de hero.** Oversized, fluid (clamp()), variable fonts. Het lettertype DRAAGT het merk.
2. **Motion is meaning.** Elke animatie heeft een doel. 300-400ms, smooth easing, prefers-reduced-motion gerespecteerd.
3. **Bento grids, niet Bootstrap grids.** Asymmetrisch, organisch, rounded corners (12-24px), micro-interacties per cel.
4. **OKLCH boven hex.** Perceptueel uniform, wide-gamut, palette-generatie-friendly.
5. **Scroll vertelt een verhaal.** Content onthult met scroll progress. Scroll-driven CSS op compositor thread.
6. **Dark mode is niet optioneel.** prefers-color-scheme, proper token system, getest contrast in beide themes.
7. **Performance is design.** LCP <2.5s, INP <200ms, CLS <0.1. Geen uitzonderingen.
8. **Accessibility is architectuur.** WCAG 2.2 AA vanaf wireframe. Focus indicators, target sizes, semantic HTML, reduced motion.
9. **Fluid alles.** clamp() voor type, clamp() voor spacing, container queries voor componenten.
10. **Menselijke imperfectie verslaat AI-steriliteit.** Organische vormen, bewuste asymmetrie, editorial karakter.

### 6.4 Divi Implementatie Matrix

| Feature | Divi Native | Custom CSS Nodig | Custom JS Nodig |
|---------|-------------|-----------------|-----------------|
| Scroll effects (fade, scale, motion) | Ja | - | - |
| Sticky header met style transitions | Ja | - | - |
| Hover states op cards/buttons | Ja | - | - |
| Parallax backgrounds | Ja | - | - |
| Transform + hover states | Ja | - | - |
| Glassmorphism (backdrop-filter) | - | Ja | - |
| Bento grids (CSS Grid override) | - | Ja | - |
| Fluid typography (clamp) | - | Ja | - |
| OKLCH kleuren | - | Ja | - |
| Variable font transitions | - | Ja | - |
| Dark mode toggle | - | Ja | - |
| Aurora/mesh gradient backgrounds | - | Ja | - |
| Asymmetric broken grids | - | Ja | - |
| GSAP kinetic typography | - | - | Ja |
| Horizontal scroll sections | - | - | Ja |
| Number counter animaties | - | - | Ja |
| Scroll-linked video playback | - | - | Ja |

### 6.5 Micro-Interactie Specificaties

| Element | Hover State | Active State | Duur |
|---------|-------------|--------------|------|
| Button | translateY(-2px), shadow increase | scale(0.98) | 300ms |
| Card | translateY(-4px), shadow deepen | scale(0.99) | 350ms |
| Link | underline draw-in van links | - | 250ms |
| Image | scale(1.03) met overflow:hidden container | - | 400ms |
| Nav item | weight shift of underline slide | - | 250ms |
| Input | border-color transition, label float | - | 200ms |

**Easing:** NOOIT `linear`. Altijd `cubic-bezier(0.4, 0, 0.2, 1)` of soortgelijk.

### 6.6 Accessibility & Compliance (Wettelijk Verplicht)

**European Accessibility Act (EAA)** — van kracht sinds 28 juni 2025:
- Alle EU e-commerce sites met 10+ medewerkers of €2M+ omzet MOETEN WCAG 2.1 AA compliant zijn
- Deadline bestaande content: 28 juni 2030

**WCAG 2.2 checklist (minimum bij elke build):**
- Kleurcontrast: 4.5:1 body text, 3:1 large text, 3:1 UI componenten
- Focus indicators: zichtbaar, 2px+ dik, 3:1 contrast
- Touch targets: minimaal 44x44px (aanbevolen)
- Keyboard navigatie: volledige site bruikbaar via toetsenbord
- Skip links: "Skip to main content" als eerste focusbaar element
- Heading hierarchie: 1 h1 per pagina, sequentieel (geen h2 → h4 skip)
- Alt tekst: beschrijvend voor informatieve images, alt="" voor decoratief
- Reduced motion: `@media (prefers-reduced-motion: reduce)` schakelt animaties uit
- Lang attribute: `<html lang="nl">` (of passende taal)

### 6.7 Core Web Vitals Targets

| Metric | Target | Wat Het Betekent |
|--------|--------|-----------------|
| **LCP** | <2.5s | Grootste element op pagina laadt snel |
| **INP** | <200ms | Interacties reageren direct |
| **CLS** | <0.1 | Pagina springt niet rond tijdens laden |

**Divi-specifieke tips:** Dynamic CSS + Critical CSS AAN, lazy loading op images, preload display font, geen backdrop-filter op full-width sections.

---

## DEEL 7: ACADEMISCHE BRONNEN

### Design System Theory
- **Atomic Design** (Brad Frost, 2016): Atoms → Molecules → Organisms → Templates → Pages
- **Design Tokens** (Salesforce Lightning, 2019): Global Colors/Fonts als abstracte waarden

### 2030-Ready Design Research
- [Figma: Top Web Design Trends for 2026](https://www.figma.com/resource-library/web-design-trends/)
- [Digital Silk: Future of Web Design — Predictions Till 2030](https://www.digitalsilk.com/digital-trends/future-of-web-design/)
- [Evil Martians: OKLCH in CSS](https://evilmartians.com/chronicles/oklch-in-css-why-quit-rgb-hsl)
- [Smashing Magazine: Modern Fluid Typography](https://www.smashingmagazine.com/2022/01/modern-fluid-typography-css-clamp/)
- [MDN: Scroll-Driven Animations](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Scroll-driven_animations)
- [Design Monks: Typography Trends 2026](https://www.designmonks.co/blog/typography-trends-2026)
- [Fontfabric: Top 10 Typography Trends 2026](https://www.fontfabric.com/blog/10-design-trends-shaping-the-visual-typographic-landscape-in-2026/)
- [Creative Bloq: Top Typography Trends 2026](https://www.creativebloq.com/design/fonts-typography/breaking-rules-and-bringing-joy-top-typography-trends-for-2026)

### Accessibility & Compliance
- [W3C: What's New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/)
- [AccessibleEU: EAA June 2025](https://accessible-eu-centre.ec.europa.eu/content-corner/news/eaa-comes-effect-june-2025-are-you-ready-2025-01-31_en)
- [Level Access: WCAG 2.2 Checklist](https://www.levelaccess.com/blog/wcag-2-2-aa-summary-and-checklist-for-website-owners/)

### Performance
- [web.dev: Core Web Vitals Thresholds](https://web.dev/articles/defining-core-web-vitals-thresholds)
- [NitroPack: Core Web Vitals 2026](https://nitropack.io/blog/most-important-core-web-vitals-metrics/)

### WordPress & Divi
- **WordPress Coding Standards** (WordPress.org)
- **Theme Review Handbook** (WordPress.org)
- **Divi Theme Builder** (Elegant Themes docs)
- **Divi Global Presets** (ET blog, 2022)
- **Divi 5 Interactions** (ET, 2025)
- **OWASP WordPress Security** (2024)
- **NIST SP 800-207** (Zero Trust Architecture)

---

## SAMENVATTING: 30 GOUDEN REGELS

### Mindset (1-3)
1. **Denk als €100K+ agency** — elk detail telt, geen shortcuts
2. **2030 styling of niets** — research trends VOOR je bouwt, loop altijd vooruit
3. **Research first, pixels last** — WebSearch branche + trends + concurrentie, dan pas bouwen

### Build Volgorde (4-8)
4. **Global Colors + Fonts eerst** — alles daarna erft automatisch
5. **Menu + pagina's voor Theme Builder** — header heeft menu nodig
6. **Theme Builder voor content** — bouw niet blind zonder header/footer
7. **SEO laatst** — content moet er zijn voor meta
8. **Dry-run eerst** — preview voor uitvoering

### Content & Componenten (9-16)
9. **Logo via Media Library** — nooit hardcoded
10. **Contact info centraal** — shortcodes of 1 plek, nooit verspreid
11. **Social icons via Divi module** — niet via Font Awesome/CSS
12. **Icons via FA Pro of SVG** — hoogste kwaliteit, consistent style
13. **Gravity Forms via shortcode in Code Module** — ajax=true, title=false
14. **Alt tekst op elke afbeelding** — geen uitzonderingen
15. **Admin labels op sections** — navigatie in builder
16. **Responsive check: desktop → tablet → mobile** — in die volgorde

### CSS Architectuur (17-22)
17. **One source of truth** — elke CSS regel heeft precies 1 juiste plek
18. **CSS tokens in :root** — kleuren, fonts, spacing, radius, easing
19. **Sitewide CSS in child theme** — niet Divi Custom CSS box of Code Modules
20. **Page-specifiek in Divi Page CSS** — niet child theme met page-ID selectors
21. **Blog/archive styling via Theme Builder** — globaal voor alle posts
22. **Presets voor alles** — buttons, headings, text, images

### Design & Performance (23-27)
23. **Typography is de hero** — oversized, fluid, variable fonts
24. **Micro-interacties op elk interactief element** — 300ms, smooth easing
25. **Performance = design** — LCP <2.5s, INP <200ms, CLS <0.1
26. **Accessibility = architectuur** — WCAG 2.2 AA, EAA compliant
27. **prefers-reduced-motion respecteren** — animaties uit voor wie dat wil

### Operations (28-30)
28. **Client = Editor role** — Divi Role Editor configureren
29. **Performance opties aan** — Dynamic CSS, Critical CSS, Defer
30. **Session tracking** — rollback per sessie

---

*Dit document is het trainingsmateriaal voor de LOIQ WP Agent.*
*Elke regel is gebaseerd op productie-ervaring, academisch onderzoek, en community best practices.*
*Versie 2.0 — 2026-02-25*
