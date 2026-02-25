# Site Build Playbook — LOIQ WP Agent

> Stap-voor-stap handleiding voor het autonoom bouwen van een complete WordPress/Divi website via de LOIQ WP Agent REST API.

## Pre-flight Checklist

Voordat je begint, verifieer:

```
GET /claude/v1/status
```

Check:
- [ ] LOIQ WP Agent actief (versie ≥ 3.1.0)
- [ ] Divi/Extra theme actief OF Divi Builder plugin
- [ ] Power modes enabled voor benodigde domeinen
- [ ] Token werkt (geen 401)

### Power Modes Activeren

Alle benodigde power modes voor een volledige site build:

| Mode | Nodig voor |
|------|-----------|
| `content` | Pagina's aanmaken, content updaten |
| `css` | CSS deployen naar child theme |
| `divi_builder` | Divi Builder, Theme Builder, Library |
| `menus` | Menu's aanmaken, items toevoegen, mega menu |
| `media` | Afbeeldingen uploaden |
| `forms` | Gravity Forms aanmaken |
| `child_theme` | functions.php wijzigen |

Activeer via WP Admin → LOIQ Agent → Power Modes, of via API:
```
GET /claude/v2/power-modes
```

---

## Fase 1: Pagina's Aanmaken

### 1.1 Pagina's met template

Gebruik voorgebouwde templates voor snelle start:

```json
POST /claude/v3/page/create
{
    "title": "Home",
    "template": "homepage",
    "status": "draft",
    "page_layout": "et_full_width_page",
    "dry_run": true
}
```

Beschikbare templates: `homepage`, `diensten`, `contact`, `over-ons`, `blog-single`, `landing-page`

### 1.2 Pagina's met custom Divi content

Bouw eerst de JSON, dan naar shortcode:

```json
POST /claude/v3/divi/build
{
    "json": {
        "sections": [
            {
                "type": "section",
                "settings": {
                    "background_color": "#1a2332"
                },
                "children": [
                    {
                        "type": "row",
                        "settings": {"column_structure": "1_2,1_2"},
                        "children": [
                            {
                                "type": "column",
                                "settings": {"type": "1_2"},
                                "children": [
                                    {
                                        "type": "text",
                                        "content": "<h1>Welkom</h1><p>Uw tekst hier.</p>"
                                    }
                                ]
                            },
                            {
                                "type": "column",
                                "settings": {"type": "1_2"},
                                "children": [
                                    {
                                        "type": "image",
                                        "settings": {
                                            "src": "https://example.com/hero.jpg",
                                            "alt": "Hero afbeelding"
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    }
}
```

Gebruik de response `shortcode` in `page/create`:

```json
POST /claude/v3/page/create
{
    "title": "Over Ons",
    "content": "[et_pb_section background_color=\"#1a2332\"]...[/et_pb_section]",
    "status": "draft",
    "page_layout": "et_full_width_page"
}
```

### 1.3 Standaard pagina's voor een bedrijfssite

| Pagina | Template | Layout |
|--------|----------|--------|
| Home | `homepage` | `et_full_width_page` |
| Over Ons | `over-ons` | `et_full_width_page` |
| Diensten | `diensten` | `et_full_width_page` |
| Contact | `contact` | `et_full_width_page` |
| Blog | Custom (Blog module) | `et_full_width_page` |
| Privacy | Geen (plain text) | `et_no_sidebar` |

### 1.4 Pagina klonen

```json
POST /claude/v3/page/clone
{
    "source_id": 12,
    "title": "Dienst: Training"
}
```

---

## Fase 2: Content Vullen

### 2.1 Content updaten

```json
POST /claude/v2/content/update
{
    "post_id": 42,
    "content": "[et_pb_section]...[/et_pb_section]",
    "title": "Nieuwe Titel",
    "excerpt": "Korte beschrijving voor SEO"
}
```

### 2.2 Media uploaden

```json
POST /claude/v3/media/upload
{
    "url": "https://example.com/team-foto.jpg",
    "title": "Team foto",
    "alt_text": "Het team van Bedrijf X"
}
```

Of base64:
```json
POST /claude/v3/media/upload
{
    "base64": "data:image/png;base64,...",
    "filename": "logo.png",
    "title": "Bedrijfslogo"
}
```

Max 5MB, alleen image/* MIME types.

### 2.3 Media zoeken

```json
GET /claude/v3/media/search?search=logo&mime_type=image
```

---

## Fase 3: Menu's Bouwen

### 3.1 Menu aanmaken + items toevoegen

```json
POST /claude/v3/menu/create
{"name": "Hoofdmenu"}
```

```json
POST /claude/v3/menu/items/add
{
    "menu_id": 5,
    "items": [
        {"type": "page", "object_id": 12, "title": "Home", "position": 1},
        {"type": "page", "object_id": 14, "title": "Diensten", "position": 2},
        {"type": "page", "object_id": 51, "title": "Trainingen", "parent_item_id": 1082, "position": 1},
        {"type": "page", "object_id": 52, "title": "Teambuilding", "parent_item_id": 1082, "position": 2},
        {"type": "page", "object_id": 13, "title": "Over Ons", "position": 3},
        {"type": "page", "object_id": 15, "title": "Blog", "position": 4},
        {"type": "page", "object_id": 16, "title": "Contact", "position": 5}
    ]
}
```

### 3.2 Menu toewijzen aan locatie

```json
POST /claude/v3/menu/assign
{
    "menu_id": 5,
    "location": "primary-menu"
}
```

### 3.3 Max Mega Menu configureren

Zie `training/MAX-MEGA-MENU.md` voor uitgebreide documentatie.

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1082,
    "settings": {
        "type": "megamenu",
        "panel_columns": "4"
    }
}
```

---

## Fase 4: Theme Builder (Header/Footer/Blog)

Zie `training/DIVI-THEME-BUILDER.md` voor uitgebreide documentatie.

### 4.1 Global Header aanmaken

```json
POST /claude/v3/theme-builder/list
```

Pak het `default_template.id`, update dan de header:

```json
POST /claude/v3/theme-builder/update
{
    "template_id": 123,
    "header_content": "[et_pb_section global_module=\"header\" fullwidth=\"on\"][et_pb_fullwidth_menu logo=\"https://example.com/logo.png\" menu_id=\"5\"][/et_pb_fullwidth_menu][/et_pb_section]"
}
```

### 4.2 Blog Post Template

```json
POST /claude/v3/theme-builder/create
{
    "title": "Blog Post Template",
    "use_on": ["all:post"],
    "body_content": "[et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_title featured_image=\"on\" meta=\"on\" author=\"on\" date=\"on\" categories=\"on\"][/et_pb_post_title][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_content][/et_pb_post_content][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_comments][/et_pb_comments][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_nav][/et_pb_post_nav][/et_pb_column][/et_pb_row][/et_pb_section]",
    "dry_run": true
}
```

### 4.3 Blog Archive Template

```json
POST /claude/v3/theme-builder/create
{
    "title": "Blog Archief",
    "use_on": ["archive:post:category", "archive:post:post_tag"],
    "body_content": "[et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_post_title][/et_pb_post_title][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section][et_pb_row][et_pb_column type=\"4_4\"][et_pb_blog fullwidth=\"off\" posts_number=\"9\" show_author=\"on\" show_date=\"on\" show_categories=\"on\" show_excerpt=\"on\" show_pagination=\"on\"][/et_pb_blog][/et_pb_column][/et_pb_row][/et_pb_section]"
}
```

### 4.4 Global Footer

```json
POST /claude/v3/theme-builder/update
{
    "template_id": 123,
    "footer_content": "[et_pb_section background_color=\"#1a2332\"][et_pb_row column_structure=\"1_3,1_3,1_3\"][et_pb_column type=\"1_3\"][et_pb_text]<h4>Contact</h4><p>Adres<br>Telefoon<br>Email</p>[/et_pb_text][/et_pb_column][et_pb_column type=\"1_3\"][et_pb_text]<h4>Links</h4>[/et_pb_text][/et_pb_column][et_pb_column type=\"1_3\"][et_pb_social_media_follow][et_pb_social_media_follow_network social_network=\"facebook\" url=\"https://facebook.com/bedrijf\"]Facebook[/et_pb_social_media_follow_network][et_pb_social_media_follow_network social_network=\"instagram\" url=\"https://instagram.com/bedrijf\"]Instagram[/et_pb_social_media_follow_network][et_pb_social_media_follow_network social_network=\"linkedin\" url=\"https://linkedin.com/company/bedrijf\"]LinkedIn[/et_pb_social_media_follow_network][/et_pb_social_media_follow][/et_pb_column][/et_pb_row][/et_pb_section]"
}
```

---

## Fase 5: Formulieren

### 5.1 Contactformulier aanmaken

```json
POST /claude/v3/forms/create
{
    "title": "Contactformulier",
    "fields": [
        {"type": "name", "label": "Naam", "isRequired": true},
        {"type": "email", "label": "E-mail", "isRequired": true},
        {"type": "phone", "label": "Telefoon"},
        {"type": "textarea", "label": "Bericht", "isRequired": true}
    ],
    "notifications": {
        "to": "{admin_email}",
        "subject": "Nieuw contactformulier: {form_title}",
        "message": "{all_fields}"
    },
    "confirmations": {
        "type": "message",
        "message": "Bedankt voor uw bericht. We nemen zo snel mogelijk contact op."
    }
}
```

### 5.2 Formulier embedden

```json
GET /claude/v3/forms/embed?form_id=1
```

Response bevat shortcode `[gravityform id="1" title="true"]` — gebruik in content update.

---

## Fase 6: Child Theme + CSS

### 6.1 Custom PHP functionaliteit

```json
POST /claude/v3/child-theme/functions/append
{
    "tag": "custom-post-types",
    "code": "function register_diensten_cpt() {\n    register_post_type('dienst', [\n        'labels' => ['name' => 'Diensten', 'singular_name' => 'Dienst'],\n        'public' => true,\n        'has_archive' => true,\n        'supports' => ['title', 'editor', 'thumbnail'],\n        'rewrite' => ['slug' => 'diensten'],\n    ]);\n}\nadd_action('init', 'register_diensten_cpt');",
    "dry_run": true
}
```

### 6.2 CSS deployen

```json
POST /claude/v2/css/deploy
{
    "css": ".hero-section { min-height: 80vh; display: flex; align-items: center; }\n.cta-button { background: #EE5340; padding: 15px 40px; border-radius: 8px; }",
    "target": "child_theme",
    "mode": "append"
}
```

Targets: `child_theme`, `divi_custom_css`, `customizer`
Modes: `append`, `replace`

---

## Fase 7: FacetWP (optioneel)

### 7.1 Facet aanmaken

```json
POST /claude/v3/facet/create
{
    "name": "Locatie",
    "type": "dropdown",
    "source": "tax/locatie",
    "label_any": "Alle locaties"
}
```

### 7.2 Template configureren

```json
POST /claude/v3/facet/template
{
    "name": "vacatures",
    "query": {
        "post_type": "vacature",
        "posts_per_page": 12,
        "orderby": "date",
        "order": "DESC"
    },
    "display": "[facetwp facet=\"locatie\"][facetwp template=\"vacatures\"]"
}
```

---

## Fase 8: Taxonomie + Categorisatie

### 8.1 Categorieën aanmaken

```json
POST /claude/v3/taxonomy/create-term
{
    "taxonomy": "category",
    "name": "Trainingen",
    "slug": "trainingen",
    "description": "Alle trainingen en workshops"
}
```

### 8.2 Terms toewijzen aan posts

```json
POST /claude/v3/taxonomy/assign
{
    "post_id": 51,
    "taxonomy": "category",
    "terms": [5, 8]
}
```

---

## Fase 9: Verificatie + Oplevering

### 9.1 Pagina's checken

```json
GET /claude/v3/page/list?status=publish
```

### 9.2 Menu's checken

```json
GET /claude/v3/menu/list
```

### 9.3 Theme Builder checken

```json
GET /claude/v3/theme-builder/list
```

### 9.4 Formulieren checken

```json
GET /claude/v1/forms
```

### 9.5 Snapshots bekijken (rollback indien nodig)

```json
GET /claude/v2/snapshots
```

---

## Volledige Workflow Samenvatting

```
1. PRE-FLIGHT  → GET /v1/status + power modes check
2. PAGINA'S    → POST /v3/page/create (× N pagina's)
3. MEDIA       → POST /v3/media/upload (logo, hero, team)
4. CONTENT     → POST /v2/content/update (vul pagina's)
5. MENU'S      → POST /v3/menu/create → items/add → assign
6. MEGA MENU   → POST /v3/menu/mega-menu/configure
7. HEADER      → POST /v3/theme-builder/update (global header)
8. FOOTER      → POST /v3/theme-builder/update (global footer)
9. BLOG        → POST /v3/theme-builder/create (post + archive)
10. FORMULIER  → POST /v3/forms/create → embed in content
11. CSS        → POST /v2/css/deploy (fine-tuning)
12. VERIFY     → GET endpoints voor elke categorie
```

---

## Veiligheidsregels

1. **ALTIJD `dry_run: true` eerst** bij Theme Builder operaties
2. **NOOIT** condities zetten zonder exact Divi format te kennen
3. **Body layout MOET** `et_pb_post_content` module bevatten
4. **Snapshots** worden automatisch gemaakt — gebruik rollback bij fouten
5. **Rate limits**: 10 writes/min, 120 writes/uur
6. **Media**: max 5MB, alleen images

---

*LOIQ WP Agent Training — Site Build Playbook v1.0*
