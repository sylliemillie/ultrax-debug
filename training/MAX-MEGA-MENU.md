# Max Mega Menu — Training Documentatie

> Configuratie van Max Mega Menu via de LOIQ WP Agent API.

## Overzicht

Max Mega Menu breidt WordPress `wp_nav_menu()` uit met mega menu functionaliteit: grid layouts, widget areas, geavanceerde styling.

## Database Opslag

### Menu Location Settings

```php
// wp_options key: 'megamenu_settings'
[
    'primary' => [
        'enabled' => '1',
        'theme'   => 'default_14',
        'event'   => 'hover',         // hover | click | hover_intent
        'effect'  => 'fade_up',
        'effect_mobile' => 'slide_down',
        'second_click' => 'go',        // go | close
        'mobile_behaviour' => 'standard',  // standard | off_canvas_left | off_canvas_right
    ]
]
```

### Per Menu Item Settings

```php
// post_meta '_megamenu' op nav_menu_item posts
[
    'type'              => 'megamenu',  // megamenu | flyout (default)
    'align'             => 'left',
    'hide_text'         => 'false',
    'hide_arrow'        => 'false',
    'disable_link'      => 'false',
    'icon'              => '',          // dashicons class
    'icon_position'     => 'left',
    'hide_on_mobile'    => 'false',
    'hide_on_desktop'   => 'false',
    'panel_columns'     => '6',         // 1-8 kolommen
    'mega_menu_columns' => '1-of-6',    // kolom breedte
]
```

---

## API Endpoints

### Mega Menu Config Lezen

```
GET /claude/v3/menu/mega-menu/read?menu_item_id=1082
```

Response:
```json
{
    "menu_item_id": 1082,
    "mega_menu_active": true,
    "settings": {
        "type": "megamenu",
        "panel_columns": "4",
        "align": "left"
    }
}
```

### Mega Menu Configureren

```
POST /claude/v3/menu/mega-menu/configure
```

Parameters:

| Param | Type | Required | Beschrijving |
|-------|------|----------|-------------|
| `menu_item_id` | integer | Ja | Nav menu item ID |
| `settings` | object | Ja | Mega menu instellingen (merge) |
| `dry_run` | boolean | Nee | Preview zonder uitvoering |

**Settings worden GEMERGED**, niet vervangen. Dit maakt incrementele configuratie mogelijk.

---

## Configuratie Patronen

### Patroon 1: Basis Mega Menu

Maak een top-level menu item "Diensten" mega met 4 kolommen:

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

### Patroon 2: Flyout Submenu (default)

Standaard gedrag — gewoon dropdown submenu:

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1083,
    "settings": {
        "type": "flyout"
    }
}
```

### Patroon 3: Icon op Menu Item

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1082,
    "settings": {
        "icon": "dashicons-admin-tools",
        "icon_position": "left"
    }
}
```

### Patroon 4: Item Verbergen op Mobile

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1085,
    "settings": {
        "hide_on_mobile": "true"
    }
}
```

### Patroon 5: Item Verbergen op Desktop

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1086,
    "settings": {
        "hide_on_desktop": "true",
        "hide_on_mobile": "false"
    }
}
```

### Patroon 6: Kolom Breedte voor Subitems

Child items binnen een mega panel kunnen eigen breedte krijgen:

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1090,
    "settings": {
        "mega_menu_columns": "2-of-6"
    }
}
```

Formaat: `{span}-of-{total}` (bijv. `2-of-6` = 2 van 6 kolommen breed)

---

## Volledige Menu Build Workflow

### Stap 1: Menu Aanmaken

```json
POST /claude/v3/menu/create
{"name": "Hoofdmenu"}
```
→ Response: `{"menu_id": 5}`

### Stap 2: Items Toevoegen

```json
POST /claude/v3/menu/items/add
{
    "menu_id": 5,
    "items": [
        {"type": "page", "object_id": 12, "title": "Home", "position": 1},
        {"type": "page", "object_id": 14, "title": "Diensten", "position": 2},
        {"type": "page", "object_id": 51, "title": "Trainingen", "parent_item_id": 0, "position": 1},
        {"type": "page", "object_id": 52, "title": "Teambuilding", "parent_item_id": 0, "position": 2},
        {"type": "page", "object_id": 54, "title": "Medezeggenschap", "parent_item_id": 0, "position": 3},
        {"type": "page", "object_id": 63, "title": "Events", "parent_item_id": 0, "position": 4},
        {"type": "page", "object_id": 13, "title": "Over Ons", "position": 3},
        {"type": "page", "object_id": 15, "title": "Blog", "position": 4},
        {"type": "page", "object_id": 16, "title": "Contact", "position": 5}
    ]
}
```

→ Noteer de `items_added[].id` voor parent_item_id referenties.

### Stap 3: Submenu Items Koppelen

Na het aanmaken van items, gebruik reorder om parent-child relaties te zetten:

```json
POST /claude/v3/menu/items/reorder
{
    "menu_id": 5,
    "items": [
        {"id": 1090, "position": 1, "parent": 1082},
        {"id": 1091, "position": 2, "parent": 1082},
        {"id": 1092, "position": 3, "parent": 1082},
        {"id": 1093, "position": 4, "parent": 1082}
    ]
}
```

### Stap 4: Menu Toewijzen

```json
POST /claude/v3/menu/assign
{
    "menu_id": 5,
    "location": "primary-menu"
}
```

### Stap 5: Mega Menu Activeren

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

### Stap 6: Child Items Kolom Breedte

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1090,
    "settings": {"mega_menu_columns": "1-of-4"}
}
```

```json
POST /claude/v3/menu/mega-menu/configure
{
    "menu_item_id": 1091,
    "settings": {"mega_menu_columns": "1-of-4"}
}
```

(Herhaal voor elk child item)

---

## Beschikbare Settings

### Menu Item Type

| Waarde | Beschrijving |
|--------|-------------|
| `megamenu` | Full-width mega panel met grid |
| `flyout` | Standaard dropdown submenu (default) |

### Panel Columns

Aantal kolommen in het mega panel: `1` tot `8` (string).

### Kolom Breedte

Format: `{span}-of-{total}` waar total = `panel_columns` van parent.

| Voorbeeld | Betekenis |
|-----------|-----------|
| `1-of-4` | 25% breed |
| `2-of-4` | 50% breed |
| `1-of-6` | ~17% breed |
| `2-of-6` | ~33% breed |
| `3-of-6` | 50% breed |

### Icon Classes

Gebruik WordPress Dashicons: `dashicons-{naam}`

Veelgebruikt:
- `dashicons-admin-home` — Huis
- `dashicons-admin-tools` — Tools
- `dashicons-admin-users` — Gebruikers
- `dashicons-calendar` — Kalender
- `dashicons-email` — Email
- `dashicons-phone` — Telefoon
- `dashicons-location` — Locatie
- `dashicons-megaphone` — Megafoon

### Visibility

| Setting | `true` | `false` |
|---------|--------|---------|
| `hide_on_mobile` | Verborgen op mobiel | Zichtbaar |
| `hide_on_desktop` | Verborgen op desktop | Zichtbaar |
| `hide_text` | Alleen icon zichtbaar | Tekst + icon |
| `hide_arrow` | Geen dropdown pijl | Pijl zichtbaar |
| `disable_link` | Klik doet niks | Normaal link |

---

## CSS Selectors

| Selector | Element |
|----------|---------|
| `.max-mega-menu` | Menu wrapper |
| `.mega-menu-item` | Items met submenu |
| `a.mega-menu-link` | Menu item links |
| `.mega-menu-toggle` | Mobile toggle button |
| `.mega-sub-menu` | Submenu/mega panel |
| `.mega-menu-row` | Rij in mega panel |
| `.mega-menu-column` | Kolom in mega panel |

## JavaScript Events

| Event | Beschrijving |
|-------|-------------|
| `open_panel` | Mega/flyout panel opent |
| `close_panel` | Mega/flyout panel sluit |
| `mmm:showMobileMenu` | Mobile menu opent |
| `mmm:hideMobileMenu` | Mobile menu sluit |

---

## Veelgemaakte Fouten

| Fout | Gevolg | Oplossing |
|------|--------|-----------|
| Max Mega Menu niet actief | 400 error op configure | Activeer plugin eerst |
| `panel_columns` als integer | Kan falen | Gebruik string: `"4"` |
| Child items zonder parent | Staan los in mega panel | Gebruik reorder voor parent |
| Settings vervangen i.p.v. mergen | Onze API merged al | Alleen gewijzigde keys sturen |

---

## Vereisten

- Max Mega Menu plugin actief
- Power mode `menus` enabled
- Menu items bestaan (nav_menu_item post type)
- Rate limit: 10 writes/min

---

*LOIQ WP Agent Training — Max Mega Menu v1.0*
