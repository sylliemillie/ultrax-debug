# Claude Code Memory - LOIQ WordPress Agent

## Project Overview
WordPress plugin voor beveiligde remote debugging + write capabilities via Claude CLI.
**v2.0:** Read-only diagnose (v1) + write endpoints met safeguards (v2).

---

## Quick Start

### Klant Setup
1. Upload plugin naar `/wp-content/plugins/loiq-wp-agent/`
2. Activeer plugin in WordPress admin
3. **Kopieer het getoonde token direct** (wordt 1x getoond)
4. Plugin is nu 24 uur actief
5. **Enable gewenste Power Modes** in Tools > LOIQ WP Agent (standaard alles uit)

### Claude CLI Gebruik
```bash
# Read (v1)
curl -H "X-Claude-Token: <token>" https://site.nl/wp-json/claude/v1/status

# Write (v2) — vereist Power Mode enabled
curl -X POST -H "X-Claude-Token: <token>" \
  -H "Content-Type: application/json" \
  -d '{"css":".hero{color:red}","target":"child_theme","dry_run":true}' \
  https://site.nl/wp-json/claude/v2/css/deploy
```

---

## Read Endpoints (v1)

| Endpoint | Beschrijving |
|----------|--------------|
| `GET /claude/v1/status` | WP/PHP versie, memory, debug mode |
| `GET /claude/v1/errors?lines=50` | Laatste N regels debug.log |
| `GET /claude/v1/plugins` | Alle plugins met update status |
| `GET /claude/v1/theme` | Actief thema + parent info |
| `GET /claude/v1/database` | Tabellen, row counts, sizes (GEEN data) |
| `GET /claude/v1/code-context?topic=X` | Code context per topic |
| `GET /claude/v1/styles` | CSS stylesheets, customizer CSS, theme CSS |
| `GET /claude/v1/forms` | Gravity Forms structuur (alle of ?id=3) |
| `GET /claude/v1/page?url=/pad` | Pagina context (of ?id=42) |

---

## Write Endpoints (v2)

Alle write endpoints vereisen:
- Actieve timer (niet verlopen)
- Geldige token
- **Power Mode enabled** voor die categorie
- Write rate limit: 5/min, 30/hr

| Endpoint | Power Mode | Beschrijving |
|----------|-----------|-------------|
| `POST /claude/v2/css/deploy` | css | CSS deployen naar child theme, Divi custom CSS, of customizer |
| `POST /claude/v2/option/update` | options | Whitelisted WP opties bijwerken |
| `POST /claude/v2/plugin/toggle` | plugins | Plugin activeren/deactiveren |
| `POST /claude/v2/content/update` | content | Post/pagina titel, content, meta, status bijwerken |
| `POST /claude/v2/snippet/deploy` | snippets | PHP snippet deployen als mu-plugin |

### Management Endpoints (v2)

| Endpoint | Beschrijving |
|----------|-------------|
| `POST /claude/v2/rollback` | Snapshot terugdraaien |
| `GET /claude/v2/snapshots` | Recente snapshots lijst |
| `GET /claude/v2/power-modes` | Huidige power mode status |

---

## Write Endpoint Details

### CSS Deploy
```json
// POST /claude/v2/css/deploy
{
    "css": ".hero-section { background: #d85f2c; }",
    "target": "child_theme",     // "child_theme" | "divi_custom_css" | "customizer"
    "mode": "append",            // "append" | "replace" | "prepend"
    "dry_run": false
}
```

### Option Update
```json
// POST /claude/v2/option/update
{
    "option": "blogname",
    "value": "Nieuwe Site Naam",
    "dry_run": false
}
```
Whitelisted: blogname, blogdescription, timezone_string, date_format, time_format, start_of_week, posts_per_page, permalink_structure.

### Plugin Toggle
```json
// POST /claude/v2/plugin/toggle
{
    "plugin": "gravityforms/gravityforms.php",
    "action": "deactivate",
    "dry_run": false
}
```

### Content Update
```json
// POST /claude/v2/content/update
{
    "post_id": 42,
    "fields": {
        "post_title": "Nieuwe titel",
        "post_content": "...",
        "post_status": "draft",
        "meta": {
            "_yoast_wpseo_title": "SEO Title"
        }
    },
    "dry_run": false
}
```

### Snippet Deploy
```json
// POST /claude/v2/snippet/deploy
{
    "name": "custom-redirect",
    "code": "add_action('template_redirect', function() { ... });",
    "dry_run": false
}
```

### Rollback
```json
// POST /claude/v2/rollback
{
    "snapshot_id": 42
}
```

---

## Safeguards

### Power Modes (fail-closed)
- Default: alles **uitgeschakeld**
- Per-categorie: css, options, plugins, content, snippets
- Timer verlopen = alles geblokkeerd (ongeacht power mode setting)
- Toggelbaar via wp-admin UI

### Snapshots
- Elke write operatie maakt een pre-edit snapshot
- Bevat before/after state, session ID, IP, timestamp
- Rollback herstelt de before state
- Dry-run maakt snapshot zonder uit te voeren

### Snippet Veiligheid
- Geblokkeerde patronen: eval(), exec(), system(), shell_exec(), passthru(), proc_open()
- Max 500 regels per snippet
- Max 5 actieve snippets
- ABSPATH check auto-injected
- Deployed als mu-plugin: `/wp-content/mu-plugins/loiq-snippet-{name}.php`

### Option Whitelist
Deny-by-default. Alleen veilige opties toegestaan. NOOIT: siteurl, home, admin_email, users_can_register, db_*, secret_*.

### Content Safeguards
- Geen `post_status: "trash"` (geen hard delete)
- Geblokkeerde meta key prefixes: _wp_attached, _edit_lock, _wp_trash, etc.
- Geblokkeerde meta patterns: password, secret, token, auth_key, nonce, hash

---

## Security Features

| Feature | Implementatie |
|---------|---------------|
| Token | Auto-generated, `password_hash()` opslag |
| HTTPS | Verplicht (behalve localhost) |
| Read rate limit | 10/min, 100/uur per IP |
| Write rate limit | 5/min, 30/uur per IP |
| Auto-disable | Timer-based (default 24u) — endpoints + debug logging + power modes |
| Managed logging | mu-plugin toggled WP_DEBUG_LOG via timer |
| IP whitelist | Optioneel in admin |
| Logging | Database met GDPR IP anonimisatie |
| Power modes | Per-categorie, fail-closed |
| Snapshots | Pre-edit met rollback |
| Dry-run | Preview changes zonder uitvoeren |
| Snippet scanner | Blokkeert gevaarlijke PHP patronen |

---

## Key Files

| File | Doel |
|------|------|
| `loiq-wp-agent.php` | Entry point, v1 read endpoints, admin UI, permission checks |
| `includes/class-write-endpoints.php` | Alle v2 write + management endpoint handlers |
| `includes/class-safeguards.php` | Power modes, snapshots, dry-run, rollback, snippet scanning |
| `includes/class-audit.php` | Extended audit trail voor write operaties |
| `uninstall.php` | Cleanup bij verwijderen (tabellen, options, mu-plugins, snippets) |
| `mu-plugins/loiq-agent-logging.php` | Auto-generated, toggled WP_DEBUG_LOG via timer |

---

## Admin Functies

**Tools > LOIQ WP Agent**
- Token regenereren
- Timer verlengen (1u/24u/1 week)
- IP whitelist beheer
- **Power Modes toggles** (per-categorie write permissions)
- **Recente Snapshots** (met rollback buttons)
- **Gedeployde Snippets** (met verwijder buttons)
- Request log viewer

---

## Response Codes

| Code | Betekenis |
|------|-----------|
| 200 | Success |
| 400 | Ongeldige request (bad input, dry-run rollback, etc.) |
| 401 | Token ontbreekt of ongeldig |
| 403 | HTTPS verplicht, IP geblokkeerd, power mode uit, of geblokkeerde actie |
| 404 | Resource niet gevonden (post, plugin, snapshot) |
| 429 | Rate limit bereikt (read of write) |
| 500 | Server fout (niet schrijfbaar, etc.) |
| 503 | Auto-disable timer verlopen |

---

## Learnings

### v2.0.0
- Write endpoints met per-categorie power modes (fail-closed)
- Snapshot systeem voor pre-edit state capture + rollback
- Dry-run mode op alle write endpoints
- Snippet deployer met veiligheidsscanner (blokkeert eval/exec/system)
- Content updates met blocked meta keys (geen _wp_*, geen secrets)
- Option whitelist (deny-by-default, alleen veilige opties)
- Plugin toggle safeguards (kan zichzelf niet deactiveren)
- Modulaire architectuur: main file + 3 includes
- DB versie check voor upgrades zonder re-activatie
- Stricter write rate limits (5/min, 30/hr vs 10/min, 100/hr voor reads)

### v1.8.0
- `/styles` endpoint: CSS stylesheets, customizer CSS, child theme CSS, Divi custom CSS
- `/forms` endpoint: Gravity Forms structuur inclusief fields, confirmations, notifications
- `/page` endpoint: Pagina context met meta keys, Divi modules, Yoast data

### v1.7.0
- Managed Debug Logging: mu-plugin koppelt WP_DEBUG_LOG aan de timer
- Timer verlopen = logging stopt automatisch, geen wp-config editing

### v1.5.0
- Code context endpoint maakt Claude "slim" over de codebase
- Security filtering essentieel: regex patterns voor sensitive data

### v1.0.0
- Single-file architectuur simpeler voor dit use case
- `password_hash()` beter dan plain CONSTANT in wp-config
