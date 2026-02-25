# Divi Design Patterns — Training Documentatie

> Veelgebruikte design patterns als Divi JSON structuren voor de LOIQ WP Agent builder.

## Module Referentie

### Beschikbare Modules (30+)

**Structuur:**

| Type | Shortcode | Zelf-sluitend |
|------|-----------|--------------|
| `section` | `et_pb_section` | Nee |
| `fullwidth_section` | `et_pb_fullwidth_section` | Nee |
| `row` | `et_pb_row` | Nee |
| `row_inner` | `et_pb_row_inner` | Nee |
| `column` | `et_pb_column` | Nee |
| `column_inner` | `et_pb_column_inner` | Nee |

**Content:**

| Type | Shortcode | Heeft Content |
|------|-----------|--------------|
| `text` | `et_pb_text` | Ja |
| `image` | `et_pb_image` | Nee (self-closing) |
| `button` | `et_pb_button` | Nee |
| `divider` | `et_pb_divider` | Nee |
| `code` | `et_pb_code` | Ja (raw HTML) |

**Layout:**

| Type | Shortcode | Heeft Content |
|------|-----------|--------------|
| `blurb` | `et_pb_blurb` | Ja |
| `cta` | `et_pb_cta` | Ja |
| `slider` | `et_pb_slider` | Nee (container) |
| `slide` | `et_pb_slide` | Ja |
| `testimonial` | `et_pb_testimonial` | Ja |
| `accordion` | `et_pb_accordion` | Nee (container) |
| `accordion_item` | `et_pb_accordion_item` | Ja |
| `toggle` | `et_pb_toggle` | Ja |
| `tabs` | `et_pb_tabs` | Nee (container) |
| `tab` | `et_pb_tab` | Ja |
| `counters` | `et_pb_counters` | Nee (container) |
| `counter` | `et_pb_counter` | Nee |
| `number_counter` | `et_pb_number_counter` | Nee |

**Media:**

| Type | Shortcode | Heeft Content |
|------|-----------|--------------|
| `gallery` | `et_pb_gallery` | Nee |
| `video` | `et_pb_video` | Nee |
| `map` | `et_pb_map` | Nee (container) |
| `map_pin` | `et_pb_map_pin` | Nee |

**Fullwidth:**

| Type | Shortcode | Heeft Content |
|------|-----------|--------------|
| `fullwidth_header` | `et_pb_fullwidth_header` | Ja |
| `fullwidth_image` | `et_pb_fullwidth_image` | Nee |

**Overig:**

| Type | Shortcode | Heeft Content |
|------|-----------|--------------|
| `blog` | `et_pb_blog` | Nee |
| `sidebar` | `et_pb_sidebar` | Nee |
| `contact_form` | `et_pb_contact_form` | Nee (container) |
| `contact_field` | `et_pb_contact_field` | Nee |
| `social_media_follow` | `et_pb_social_media_follow` | Nee (container) |
| `social_media_follow_network` | `et_pb_social_media_follow_network` | Ja (label text) |

---

## Kolom Structuren

Gebruik `column_structure` op rows om kolommen te definiëren:

| Structure | Layout |
|-----------|--------|
| `4_4` | 1 kolom (100%) |
| `1_2,1_2` | 2 gelijke kolommen |
| `1_3,1_3,1_3` | 3 gelijke kolommen |
| `1_4,1_4,1_4,1_4` | 4 gelijke kolommen |
| `2_3,1_3` | 2/3 + 1/3 |
| `1_3,2_3` | 1/3 + 2/3 |
| `1_4,3_4` | 1/4 + 3/4 |
| `3_4,1_4` | 3/4 + 1/4 |
| `1_4,1_2,1_4` | 1/4 + 1/2 + 1/4 |

---

## Pattern 1: Hero Section

### Variant A: Fullwidth Header

```json
{
    "sections": [
        {
            "type": "fullwidth_section",
            "children": [
                {
                    "type": "fullwidth_header",
                    "settings": {
                        "title": "Welkom bij Bedrijfsnaam",
                        "subhead": "Uw partner in professionele dienstverlening",
                        "button_one_text": "Bekijk Diensten",
                        "button_one_url": "/diensten/",
                        "button_two_text": "Neem Contact Op",
                        "button_two_url": "/contact/",
                        "background_image": "https://example.com/hero.jpg",
                        "background_overlay_color": "rgba(0,0,0,0.5)",
                        "text_orientation": "center",
                        "header_text_color": "#ffffff",
                        "content_max_width": "800px"
                    },
                    "content": "<p>Extra beschrijving of USP's onder de subheading.</p>"
                }
            ]
        }
    ]
}
```

### Variant B: Split Hero (tekst + afbeelding)

```json
{
    "sections": [
        {
            "type": "section",
            "settings": {
                "background_color": "#1a2332",
                "custom_padding": "100px||100px|"
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
                                    "content": "<h1 style='color:#ffffff;'>Bedrijfsnaam</h1><p style='color:#94a3b8;font-size:18px;'>Korte beschrijving van wat het bedrijf doet en waarom klanten kiezen.</p>"
                                },
                                {
                                    "type": "button",
                                    "settings": {
                                        "button_text": "Start Nu",
                                        "button_url": "/contact/",
                                        "custom_button": "on",
                                        "button_bg_color": "#EE5340",
                                        "button_text_color": "#ffffff",
                                        "button_border_radius": "8px"
                                    }
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
                                        "src": "https://example.com/hero-image.jpg",
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
```

---

## Pattern 2: Features / USP Grid

### 3-kolom met Blurbs

```json
{
    "type": "section",
    "settings": {"custom_padding": "80px||80px|"},
    "children": [
        {
            "type": "row",
            "settings": {"column_structure": "1_3,1_3,1_3"},
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "1_3"},
                    "children": [
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Ervaren Team",
                                "use_icon": "on",
                                "font_icon": "%%72%%",
                                "icon_color": "#EE5340",
                                "icon_placement": "top"
                            },
                            "content": "<p>15+ jaar ervaring in de branche.</p>"
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_3"},
                    "children": [
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Op Maat",
                                "use_icon": "on",
                                "font_icon": "%%110%%",
                                "icon_color": "#EE5340",
                                "icon_placement": "top"
                            },
                            "content": "<p>Diensten afgestemd op uw organisatie.</p>"
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_3"},
                    "children": [
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Resultaat",
                                "use_icon": "on",
                                "font_icon": "%%151%%",
                                "icon_color": "#EE5340",
                                "icon_placement": "top"
                            },
                            "content": "<p>Meetbare resultaten voor uw bedrijf.</p>"
                        }
                    ]
                }
            ]
        }
    ]
}
```

### 4-kolom Statistieken (Number Counters)

```json
{
    "type": "section",
    "settings": {
        "background_color": "#1a2332",
        "custom_padding": "60px||60px|"
    },
    "children": [
        {
            "type": "row",
            "settings": {"column_structure": "1_4,1_4,1_4,1_4"},
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "1_4"},
                    "children": [
                        {
                            "type": "number_counter",
                            "settings": {
                                "title": "Jaar Ervaring",
                                "number": "15",
                                "percent_sign": "off",
                                "number_text_color": "#EE5340",
                                "title_text_color": "#94a3b8"
                            }
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_4"},
                    "children": [
                        {
                            "type": "number_counter",
                            "settings": {
                                "title": "Organisaties",
                                "number": "200",
                                "percent_sign": "off",
                                "number_text_color": "#EE5340",
                                "title_text_color": "#94a3b8"
                            }
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_4"},
                    "children": [
                        {
                            "type": "number_counter",
                            "settings": {
                                "title": "Deelnemers",
                                "number": "10000",
                                "percent_sign": "off",
                                "number_text_color": "#EE5340",
                                "title_text_color": "#94a3b8"
                            }
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_4"},
                    "children": [
                        {
                            "type": "number_counter",
                            "settings": {
                                "title": "Waardering",
                                "number": "89",
                                "percent_sign": "off",
                                "number_text_color": "#EE5340",
                                "title_text_color": "#94a3b8"
                            }
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 3: Testimonials

### Slider

```json
{
    "type": "section",
    "settings": {"background_color": "#f8fafc"},
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "4_4"},
                    "children": [
                        {
                            "type": "text",
                            "content": "<h2 style='text-align:center;'>Wat klanten zeggen</h2>"
                        },
                        {
                            "type": "slider",
                            "settings": {"show_arrows": "on", "show_pagination": "on"},
                            "children": [
                                {
                                    "type": "slide",
                                    "settings": {
                                        "heading": "Jan de Vries",
                                        "button_text": "",
                                        "background_color": "#ffffff"
                                    },
                                    "content": "<p>\"Uitstekende samenwerking. Het team leverde precies wat we nodig hadden.\"</p><p><em>— Directeur, Bedrijf A</em></p>"
                                },
                                {
                                    "type": "slide",
                                    "settings": {
                                        "heading": "Marie Jansen",
                                        "button_text": "",
                                        "background_color": "#ffffff"
                                    },
                                    "content": "<p>\"Professioneel en betrouwbaar. Aanrader voor elke organisatie.\"</p><p><em>— HR Manager, Bedrijf B</em></p>"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ]
}
```

### Testimonial Modules (3 kolommen)

```json
{
    "type": "row",
    "settings": {"column_structure": "1_3,1_3,1_3"},
    "children": [
        {
            "type": "column",
            "settings": {"type": "1_3"},
            "children": [
                {
                    "type": "testimonial",
                    "settings": {
                        "author": "Jan de Vries",
                        "company_name": "Bedrijf A",
                        "portrait_url": "https://example.com/jan.jpg",
                        "quote_icon": "on"
                    },
                    "content": "Uitstekende training. Ons team is merkbaar verbeterd."
                }
            ]
        }
    ]
}
```

---

## Pattern 4: FAQ (Accordion)

```json
{
    "type": "section",
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "4_4"},
                    "children": [
                        {
                            "type": "text",
                            "content": "<h2>Veelgestelde Vragen</h2>"
                        },
                        {
                            "type": "accordion",
                            "children": [
                                {
                                    "type": "accordion_item",
                                    "settings": {"title": "Wat kost een training?"},
                                    "content": "Onze trainingen beginnen vanaf €500 per dagdeel. Neem contact op voor een offerte op maat."
                                },
                                {
                                    "type": "accordion_item",
                                    "settings": {"title": "Hoeveel deelnemers per sessie?"},
                                    "content": "Wij werken met groepen van 8 tot 20 deelnemers voor optimale interactie."
                                },
                                {
                                    "type": "accordion_item",
                                    "settings": {"title": "Komen jullie ook op locatie?"},
                                    "content": "Ja, wij verzorgen trainingen op uw locatie door heel Nederland."
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 5: CTA (Call to Action)

### Centered CTA

```json
{
    "type": "section",
    "settings": {
        "background_color": "#EE5340",
        "custom_padding": "60px||60px|"
    },
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "4_4"},
                    "children": [
                        {
                            "type": "cta",
                            "settings": {
                                "title": "Klaar om te beginnen?",
                                "button_text": "Neem Contact Op",
                                "button_url": "/contact/",
                                "use_background_color": "off",
                                "header_text_color": "#ffffff",
                                "body_text_color": "#ffffff",
                                "custom_button": "on",
                                "button_bg_color": "#ffffff",
                                "button_text_color": "#EE5340"
                            },
                            "content": "<p>Ontdek hoe wij uw organisatie kunnen helpen groeien.</p>"
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 6: Contact Sectie

### 2 kolommen: Info + Formulier

```json
{
    "type": "section",
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
                            "content": "<h2>Neem Contact Op</h2><p>We horen graag van u.</p>"
                        },
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Adres",
                                "use_icon": "on",
                                "font_icon": "%%249%%",
                                "icon_placement": "left"
                            },
                            "content": "<p>Straatnaam 1, 1234 AB Amsterdam</p>"
                        },
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Telefoon",
                                "use_icon": "on",
                                "font_icon": "%%264%%",
                                "icon_placement": "left"
                            },
                            "content": "<p>+31 20 123 4567</p>"
                        },
                        {
                            "type": "blurb",
                            "settings": {
                                "title": "Email",
                                "use_icon": "on",
                                "font_icon": "%%238%%",
                                "icon_placement": "left"
                            },
                            "content": "<p>info@bedrijf.nl</p>"
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {"type": "1_2"},
                    "children": [
                        {
                            "type": "code",
                            "content": "[gravityform id=\"1\" title=\"false\" description=\"false\"]"
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 7: Diensten Grid

### 3 kolommen met Cards

```json
{
    "type": "section",
    "children": [
        {
            "type": "row",
            "settings": {"column_structure": "1_3,1_3,1_3"},
            "children": [
                {
                    "type": "column",
                    "settings": {
                        "type": "1_3",
                        "background_color": "#ffffff",
                        "custom_padding": "30px|30px|30px|30px",
                        "border_radii": "on|8px|8px|8px|8px",
                        "box_shadow_style": "preset1"
                    },
                    "children": [
                        {
                            "type": "image",
                            "settings": {"src": "https://example.com/dienst1.jpg"}
                        },
                        {
                            "type": "text",
                            "content": "<h3>Training</h3><p>Op maat gemaakte trainingen voor uw team.</p>"
                        },
                        {
                            "type": "button",
                            "settings": {
                                "button_text": "Meer Info",
                                "button_url": "/diensten/training/"
                            }
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 8: Blog Grid

```json
{
    "type": "section",
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "4_4"},
                    "children": [
                        {
                            "type": "text",
                            "content": "<h2 style='text-align:center;'>Laatste Berichten</h2>"
                        },
                        {
                            "type": "blog",
                            "settings": {
                                "fullwidth": "off",
                                "posts_number": "3",
                                "show_author": "off",
                                "show_date": "on",
                                "show_categories": "on",
                                "show_excerpt": "on",
                                "show_pagination": "off",
                                "show_more": "on",
                                "use_overlay": "on"
                            }
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Pattern 9: Map + Contact

```json
{
    "type": "section",
    "settings": {"custom_padding": "0px||0px|"},
    "children": [
        {
            "type": "row",
            "settings": {
                "column_structure": "1_2,1_2",
                "use_custom_gutter": "on",
                "gutter_width": "1",
                "custom_padding": "0px||0px|"
            },
            "children": [
                {
                    "type": "column",
                    "settings": {"type": "1_2", "custom_padding": "0px|0px|0px|0px"},
                    "children": [
                        {
                            "type": "map",
                            "settings": {
                                "address_lat": "52.3676",
                                "address_lng": "4.9041",
                                "zoom_level": "14",
                                "mouse_wheel": "off"
                            },
                            "children": [
                                {
                                    "type": "map_pin",
                                    "settings": {
                                        "title": "Bedrijfsnaam",
                                        "pin_address_lat": "52.3676",
                                        "pin_address_lng": "4.9041"
                                    },
                                    "content": "Straatnaam 1, Amsterdam"
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "column",
                    "settings": {
                        "type": "1_2",
                        "background_color": "#1a2332",
                        "custom_padding": "60px|40px|60px|40px"
                    },
                    "children": [
                        {
                            "type": "text",
                            "content": "<h2 style='color:#ffffff;'>Bezoek Ons</h2><p style='color:#94a3b8;'>Straatnaam 1<br>1234 AB Amsterdam<br><br>+31 20 123 4567<br>info@bedrijf.nl</p>"
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## Divi Builder API

### JSON naar Shortcode

```
POST /claude/v3/divi/build
{"json": { "sections": [...] }}
```

### Shortcode naar JSON

```
POST /claude/v3/divi/parse
{"content": "[et_pb_section]...[/et_pb_section]"}
```

### Valideer Shortcode

```
POST /claude/v3/divi/validate
{"content": "[et_pb_section]...[/et_pb_section]"}
```

Checks:
- Niet leeg
- Bevat minimaal 1 `et_pb_section`
- Alle open tags hebben matching close tags
- Bevat rows of fullwidth modules

### Module Lijst

```
GET /claude/v3/divi/modules
```

---

## Tips

1. **Column types MOETEN matchen** met `column_structure` op de row
2. **Geneste rows**: gebruik `row_inner` + `column_inner` binnen kolommen
3. **Fullwidth modules** alleen in `fullwidth_section`, NOOIT in gewone `section`
4. **Blog module** heeft GEEN content — alleen settings
5. **Social media follow** is container → `social_media_follow_network` als children
6. **Accordion** is container → `accordion_item` als children
7. **Slider** is container → `slide` als children
8. **Tabs** is container → `tab` als children
9. **Contact form** is container → `contact_field` als children

---

*LOIQ WP Agent Training — Divi Design Patterns v1.0*
