# Divi Theme Builder — Training Documentatie

> Hoe de Divi Theme Builder werkt en hoe je deze programmatisch configureert via de LOIQ WP Agent API.

## Architectuur

De Theme Builder vervangt WordPress' standaard template hiërarchie met Divi Builder-gestuurde layouts.

### Database Structuur

| Post Type | Doel | Relatie |
|-----------|------|---------|
| `et_template` | Template container met condities | Parent van layouts |
| `et_header_layout` | Header area Divi content | `post_parent` = template ID |
| `et_body_layout` | Body area Divi content | `post_parent` = template ID |
| `et_footer_layout` | Footer area Divi content | `post_parent` = template ID |

**Master Option:**
```php
// wp_options key: 'et_theme_builder'
['template_id_default' => 123]  // ID van Default Website Template
```

**Post Meta op et_template:**

| Meta Key | Type | Doel |
|----------|------|------|
| `_et_use_on` | array | Condities waar template geldt |
| `_et_exclude_from` | array | Uitsluitingen |
| `_et_header_layout_id` | int | Header layout post ID |
| `_et_body_layout_id` | int | Body layout post ID |
| `_et_footer_layout_id` | int | Footer layout post ID |

---

## Default Website Template

- Kan NIET verwijderd worden
- Dient als fallback voor alle pagina's zonder specifiek template
- ID opgeslagen in `et_theme_builder` option
- `use_on: []` is CORRECT — impliciet globale fallback
- **NOOIT** handmatig condities zetten op de Default Template

### Overerving

Custom templates erven automatisch de Global Header en Footer van de Default Template, tenzij ze eigen layouts definiëren.

| Area | Eigen layout | Geen eigen layout | Verborgen (eye icon) |
|------|-------------|-------------------|---------------------|
| Header | Gebruikt eigen | Erft van Default | Volledig verwijderd |
| Body | Gebruikt eigen | Standaard `the_content()` | Volledig verwijderd |
| Footer | Gebruikt eigen | Erft van Default | Volledig verwijderd |

---

## Template Condities

### Condition Strings

| Conditie | Geldt voor |
|----------|-----------|
| `homepage` | Voorpagina |
| `404` | 404 error pagina |
| `search` | Zoekresultaten |
| `all:post` | Alle blogposts (singular) |
| `all:page` | Alle pagina's (singular) |
| `all:project` | Alle Divi projects |
| `all:product` | Alle WooCommerce producten |
| `singular:post:{id}` | Specifieke post op ID |
| `singular:page:{id}` | Specifieke pagina op ID |
| `archive:post:category` | Alle categorie archieven |
| `archive:post:post_tag` | Alle tag archieven |
| `archive:post:category:{term_id}` | Specifiek categorie archief |
| `archive:post:author` | Auteur archieven |
| `archive:post:date` | Datum archieven |
| `all:{cpt_slug}` | Alle CPT singles |
| `archive:{cpt_slug}` | CPT archief pagina |

### Prioriteit Regels

1. `exclude_from` wint ALTIJD van `use_on`
2. Laatst opgeslagen template wint bij conflicten
3. Custom template wint van Default Website Template

### KRITIEKE WAARSCHUWING

Verkeerde condities format veroorzaakt `TypeError: Illegal offset type` → 500 errors op de HELE site. **ALTIJD** `dry_run: true` gebruiken bij condition changes.

---

## Blog Templates

### Single Post Template

Een body layout voor individuele blogposts. MOET deze modules bevatten:

| Module | Shortcode | Doel |
|--------|-----------|------|
| Post Title | `[et_pb_post_title]` | Dynamische post titel |
| Post Content | `[et_pb_post_content]` | De daadwerkelijke content |
| Comments | `[et_pb_comments]` | Reacties sectie |
| Post Navigation | `[et_pb_post_nav]` | Vorige/volgende links |

**KRITIEK:** Zonder `et_pb_post_content` module wordt user content NIET getoond.

#### Voorbeeld: Premium Blog Post Layout

```json
POST /claude/v3/theme-builder/create
{
    "title": "Blog Post Template",
    "use_on": ["all:post"],
    "body_content": "[et_pb_section fullwidth=\"on\"][et_pb_fullwidth_post_title featured_image=\"on\" date=\"on\" author=\"on\" categories=\"on\" comments=\"off\" text_color=\"light\" text_background=\"on\" text_bg_color=\"rgba(0,0,0,0.5)\"][/et_pb_fullwidth_post_title][/et_pb_section][et_pb_section][et_pb_row column_structure=\"2_3,1_3\"][et_pb_column type=\"2_3\"][et_pb_post_content][/et_pb_post_content][et_pb_divider][/et_pb_divider][et_pb_post_nav in_same_term=\"on\"][/et_pb_post_nav][et_pb_comments][/et_pb_comments][/et_pb_column][et_pb_column type=\"1_3\"][et_pb_sidebar area=\"sidebar-1\" orientation=\"right\"][/et_pb_sidebar][/et_pb_column][/et_pb_row][/et_pb_section]",
    "dry_run": true
}
```

#### Voorbeeld: Minimalistisch Blog Post

```json
POST /claude/v3/theme-builder/create
{
    "title": "Minimaal Post Template",
    "use_on": ["all:post"],
    "body_content": "[et_pb_section][et_pb_row width=\"800px\" max_width=\"800px\"][et_pb_column type=\"4_4\"][et_pb_post_title featured_image=\"on\" date=\"on\" author=\"on\" categories=\"on\" meta_separator=\"·\"][/et_pb_post_title][et_pb_post_content][/et_pb_post_content][et_pb_divider color=\"#e0e0e0\"][/et_pb_divider][et_pb_post_nav in_same_term=\"on\"][/et_pb_post_nav][et_pb_comments][/et_pb_comments][/et_pb_column][/et_pb_row][/et_pb_section]"
}
```

### Blog Archive Template

Voor categorie-, tag-, en datum-archiefpagina's.

#### Voorbeeld: Grid Archive

```json
POST /claude/v3/theme-builder/create
{
    "title": "Blog Archief Grid",
    "use_on": ["archive:post:category", "archive:post:post_tag", "archive:post:date", "archive:post:author"],
    "body_content": "[et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_title meta=\"off\"][/et_pb_post_title][et_pb_divider][/et_pb_divider][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_blog fullwidth=\"off\" posts_number=\"9\" show_author=\"on\" show_date=\"on\" show_categories=\"on\" show_excerpt=\"on\" show_pagination=\"on\" use_overlay=\"on\" overlay_icon_color=\"#ffffff\" hover_overlay_color=\"rgba(0,0,0,0.6)\"][/et_pb_blog][/et_pb_column][/et_pb_row][/et_pb_section]"
}
```

#### Voorbeeld: Fullwidth Archive

```json
POST /claude/v3/theme-builder/create
{
    "title": "Blog Archief Fullwidth",
    "use_on": ["archive:post:category"],
    "body_content": "[et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_title meta=\"off\"][/et_pb_post_title][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_blog fullwidth=\"on\" posts_number=\"10\" show_author=\"on\" show_date=\"on\" show_categories=\"on\" show_excerpt=\"on\" show_pagination=\"on\" show_more=\"on\"][/et_pb_blog][/et_pb_column][/et_pb_row][/et_pb_section]"
}
```

---

## Header Templates

### Voorbeeld: Zakelijke Header

```
[et_pb_section global_module="header" fullwidth="on" background_color="#ffffff"]
  [et_pb_fullwidth_menu
    logo="https://example.com/logo.png"
    menu_id="5"
    background_color="#ffffff"
    text_color="dark"
    active_link_color="#EE5340"
    dropdown_menu_bg_color="#ffffff"
    dropdown_menu_text_color="#333333"
  ][/et_pb_fullwidth_menu]
[/et_pb_section]
```

### Voorbeeld: Header met Top Bar

```
[et_pb_section background_color="#1a2332" custom_padding="10px||10px|"]
  [et_pb_row column_structure="1_2,1_2"]
    [et_pb_column type="1_2"]
      [et_pb_text text_font_size="14px" text_text_color="#ffffff"]
        <span>+31 20 123 4567</span> · <span>info@bedrijf.nl</span>
      [/et_pb_text]
    [/et_pb_column]
    [et_pb_column type="1_2"]
      [et_pb_social_media_follow icon_color="#ffffff" ...]
        ...social links...
      [/et_pb_social_media_follow]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
[et_pb_section fullwidth="on" background_color="#ffffff"]
  [et_pb_fullwidth_menu logo="..." menu_id="5"]
  [/et_pb_fullwidth_menu]
[/et_pb_section]
```

---

## Footer Templates

### Voorbeeld: 4-kolom Footer

```
[et_pb_section background_color="#1a2332"]
  [et_pb_row column_structure="1_4,1_4,1_4,1_4"]
    [et_pb_column type="1_4"]
      [et_pb_image src="logo-white.png"][/et_pb_image]
      [et_pb_text text_text_color="#94a3b8"]
        <p>Korte bedrijfsbeschrijving hier.</p>
      [/et_pb_text]
    [/et_pb_column]
    [et_pb_column type="1_4"]
      [et_pb_text text_text_color="#ffffff"]
        <h4>Diensten</h4>
        <ul><li>Training</li><li>Coaching</li></ul>
      [/et_pb_text]
    [/et_pb_column]
    [et_pb_column type="1_4"]
      [et_pb_text text_text_color="#ffffff"]
        <h4>Contact</h4>
        <p>Adres<br>Telefoon<br>Email</p>
      [/et_pb_text]
    [/et_pb_column]
    [et_pb_column type="1_4"]
      [et_pb_text text_text_color="#ffffff"]<h4>Volg ons</h4>[/et_pb_text]
      [et_pb_social_media_follow]
        [et_pb_social_media_follow_network social_network="facebook" url="..."]Facebook[/et_pb_social_media_follow_network]
        [et_pb_social_media_follow_network social_network="instagram" url="..."]Instagram[/et_pb_social_media_follow_network]
        [et_pb_social_media_follow_network social_network="linkedin" url="..."]LinkedIn[/et_pb_social_media_follow_network]
      [/et_pb_social_media_follow]
    [/et_pb_column]
  [/et_pb_row]
  [et_pb_row]
    [et_pb_column type="4_4"]
      [et_pb_divider color="#2d3a4a"][/et_pb_divider]
      [et_pb_text text_text_color="#64748b" text_font_size="14px" text_orientation="center"]
        <p>© 2026 Bedrijfsnaam. Alle rechten voorbehouden.</p>
      [/et_pb_text]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
```

---

## API Referentie

### Theme Builder List

```
GET /claude/v3/theme-builder/list
```

Response:
```json
{
    "total": 3,
    "templates": [
        {
            "id": 156,
            "title": "Blog Post Template",
            "status": "publish",
            "conditions": {
                "use_on": ["all:post"],
                "exclude_from": []
            },
            "has_header": false,
            "has_body": true,
            "has_footer": false
        }
    ],
    "default_template": {
        "id": 123,
        "title": "Default Website Template",
        "status": "publish",
        "has_header": true,
        "has_body": false,
        "has_footer": true
    }
}
```

### Theme Builder Read

```
GET /claude/v3/theme-builder/read?template_id=156
```

Response:
```json
{
    "id": 156,
    "title": "Blog Post Template",
    "status": "publish",
    "conditions": {
        "use_on": ["all:post"],
        "exclude_from": []
    },
    "header": null,
    "body": {
        "layout_id": 157,
        "content": "[et_pb_section]...[/et_pb_section]"
    },
    "footer": null
}
```

### Theme Builder Create

```
POST /claude/v3/theme-builder/create
```

Parameters:

| Param | Type | Required | Default |
|-------|------|----------|---------|
| `title` | string | Ja | — |
| `use_on` | array | Nee | `[]` |
| `exclude_from` | array | Nee | `[]` |
| `header_content` | string | Nee | `''` |
| `body_content` | string | Nee | `''` |
| `footer_content` | string | Nee | `''` |
| `dry_run` | boolean | Nee | `false` |

### Theme Builder Update

```
POST /claude/v3/theme-builder/update
```

Parameters:

| Param | Type | Required | Default |
|-------|------|----------|---------|
| `template_id` | integer | Ja | — |
| `header_content` | string | Nee | — |
| `body_content` | string | Nee | — |
| `footer_content` | string | Nee | — |
| `dry_run` | boolean | Nee | `false` |

Alleen meegegeven areas worden geüpdatet. Overgeslagen areas blijven ongewijzigd.

### Theme Builder Assign

```
POST /claude/v3/theme-builder/assign
```

Parameters:

| Param | Type | Required | Default |
|-------|------|----------|---------|
| `template_id` | integer | Ja | — |
| `use_on` | array | Nee | — |
| `exclude_from` | array | Nee | — |
| `dry_run` | boolean | Nee | `false` |

---

## Divi Library

### Layout opslaan

```json
POST /claude/v3/divi/library/save
{
    "title": "Premium Blog Header",
    "content": "[et_pb_section]...[/et_pb_section]",
    "layout_type": "section",
    "category": "Headers"
}
```

Layout types: `module`, `row`, `section`, `full`

### Library bekijken

```
GET /claude/v3/divi/library/list
```

---

## Corruptie Herstel

Als de Theme Builder corrupt raakt:

1. Body rendert LEEG ondanks dat pagina's content hebben
2. `et-tb-has-body et-tb-body-disabled` classes op body (informatief, niet de oorzaak)
3. `et_theme_builder` option mist maar `et_template` posts bestaan

### Fix: Nuclear Reset

De enige betrouwbare fix bij corruptie:
1. DELETE alle `et_template` posts
2. DELETE alle `et_header_layout` posts
3. DELETE alle `et_body_layout` posts
4. DELETE alle `et_footer_layout` posts
5. DELETE de `et_theme_builder` option

Zonder Theme Builder renderen pagina's hun Divi Builder content normaal via `the_content()`.

---

## Veelgemaakte Fouten

| Fout | Gevolg | Oplossing |
|------|--------|-----------|
| Body layout zonder `et_pb_post_content` | Content niet zichtbaar | Voeg module toe |
| Condities in verkeerd format | 500 error hele site | ALTIJD dry_run eerst |
| Default Template condities wijzigen | Onvoorspelbaar gedrag | Laat op `use_on: []` |
| Header verbergen (eye icon) | Header volledig weg (geen fallback) | Maak eigen header |
| Conflicterende condities | Laatst opgeslagen wint | Controleer bestaande templates |

---

*LOIQ WP Agent Training — Divi Theme Builder v1.0*
