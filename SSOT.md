# Lailatulqadar: Single Source of Truth

Version 1.9.0. This file records the architecture, stored data and binding decisions for the Lailatulqadar child theme. Update it with every release.

## Identity

| Item | Value |
|---|---|
| Theme name | Lailatulqadar |
| Folder and text domain | `lailatulqadar` |
| Parent | Twenty Twenty-Five (`twentytwentyfive`) |
| Site | https://lailatulqadar.guide |
| Package name | `lailatulqadar-[version].zip` |
| Requirements | WordPress 6.7 or later, PHP 7.4 or later |
| Languages | Built into the theme: English at the root, Malay beneath /ms/ (docs/languages.md); no plugin |

## Editorial decisions

- English display form: **Laylat al-Qadr**. Malay display form: **Lailatulqadar**, capitalised in all positions (Pedoman Umum Ejaan Bahasa Melayu, huruf besar, rule 8).
- Malay page titles capitalise every word except particles such as *di* and *dan*, and both halves of a reduplicated word (rules 11 and 12).
- Spelling variants belong in schema `alternateName` and the FAQ, never rotated through body copy. Exception adopted in 1.1.0 on the keyword evidence ("laylatul qadr" 4,400 US searches a month against 1,300 for "laylat al qadr"): the home and Duʿāʾ title tags carry "(Laylatul Qadr)", and the English front-page introduction names that spelling once.
- Audience: mixed. English at the site root, Malay under `/ms/`.
- Arabic terms carry transliteration and translation on first use.

## File map

| Path | Role |
|---|---|
| `style.css` | Theme header only |
| `functions.php` | Bootstrap, text domain, editor styles |
| `theme.json` | Night palette, EB Garamond, Arslan Wessam, KFGQPC Hafs and Noto Naskh Arabic (fallback), layout widths |
| `inc/helpers.php` | Settings access, language role, page registry, menu items |
| `inc/assets.php` | Front stylesheet, accent variables, `data-scheme` attribute |
| `inc/blocks.php` | Registers the editor script and both dynamic blocks |
| `inc/ramadan.php` | Umm al-Qura table (1446 to 1473 AH), current-Ramadan logic, date formatting, `[lq_ramadan]` shortcode |
| `blocks/odd-nights/` | Self-updating table of odd-night dates |
| `inc/night-window.php` | City presets, strings and dates for the night window |
| `blocks/night-window/` | Night window block (server shell; the browser script builds the interface) |
| `js/night-window.js`, `css/night-window.css` | Night window script and styles |
| `js/vendor/adhan.min.js` | adhan 4.4.6 prayer-time library (MIT) |
| `inc/schema.php` | JSON-LD graph (Organization, WebSite, WebPage, BreadcrumbList, Article), breadcrumb trail, XML sitemap trimming |
| `blocks/breadcrumbs/` | Visible breadcrumbs above the page title |
| `inc/seo.php` | Title tag, meta description, editor box, SEO plugin detection |
| `inc/setup.php` | First-run and on-demand setup routine, draft renaming, SEO prefill |
| `inc/settings.php` | Tabbed settings screen and Tools handlers (admin only) |
| `blocks/guide-menu/` | Header links, numbered reading path, footer |
| `blocks/language-switcher/` | Link to the same page in the other language |
| `css/front.css` | Front end, including dawn and auto schemes |
| `css/admin.css` | Settings screen |
| `css/editor.css` | Editor-only adjustments (the editor also loads `front.css`) |
| `js/scheme.js` | Head script for the visitor light/dark switch |
| `inc/languages.php` | Two languages without a plugin: page language, translation pairing, hreflang, html lang, Malay site name, Malay search |
| `inc/parent.php` | Parent stylesheet versioning; parent style variations held back |
| `templates/single.html`, `archive.html`, `search.html`, `index.html`, `home.html`, `page-no-title.html` | Post, archive, search and blog-index views in the theme design |
| `patterns/blog-title.php` | Posts index heading |
| `patterns/no-results.php` | Bilingual message for empty archives and searches |
| `inc/login.php`, `css/login.css` | Login screen styling, brand block, title, favicon |
| `blocks/logo-mark/` | Header logo lockup: lantern alone, and Arabic calligraphy (full mark with crescent and star on the login screen and larger icons) |
| `images/` | Default favicon (SVG, 32px PNG), touch icon, 512px logo for structured data |
| `blocks/scheme-toggle/` | Light and dark switch button |
| `js/admin.js` | Accent preview and confirmation prompts |
| `js/meta-box.js` | Character counts in the Search appearance box |
| `css/meta-box.css` | Search appearance box |
| `js/editor-blocks.js` | Editor registration for the two dynamic blocks |
| `parts/header.html`, `parts/footer.html` | Override the parent parts |
| `templates/front-page.html`, `page.html`, `404.html` | Override the parent templates |
| `patterns/front-en.php`, `front-ms.php` | Front page content per language |
| `content/<lang>/<key>.html`, `<key>.footnotes.json` | Shipped articles and their footnotes, seeded into empty drafts by setup |
| `fonts/eb-garamond/`, `fonts/noto-naskh-arabic/` | Self-hosted WOFF2 subsets with OFL licences |
| `fonts/arslan-wessam/` | Arslan Wessam A and B, WOFF2 subsets, with a notice (no licence text in the source files) |
| `fonts/kfgqpc/` | KFGQPC Hafs TTF, unmodified, with its licence |
| `languages/` | POT file and `ms_MY` translation |

## Stored data

| Option | Contents |
|---|---|
| `lailatulqadar_settings` | Array: `primary_slug`, `secondary_slug`, `primary_label`, `secondary_label`, `footer_note_primary`, `footer_note_secondary`, `scheme` (`night`, `dawn`, `auto`), `scheme_toggle` (`show`, `hide`), `accent_night`, `accent_dawn`, `calligraphy_id`, `login_tagline_primary`, `login_tagline_secondary`, `login_note_primary`, `login_note_secondary`, `verify_google`, `verify_bing`, `author_name`, `author_url`, `ramadan_year`, `ramadan_start`, `ramadan_alt` (optional override for one year; empty means automatic) |
| `lailatulqadar_pages` | Page IDs keyed by role (`primary`, `secondary`) then page key |
| `lailatulqadar_setup_version` | Theme version that last ran setup |
| `lailatulqadar_needs_setup` | Set on activation, cleared on the first admin visit |
| Transient `lailatulqadar_setup_report` | Result of the last setup run, shown once |
| Post meta `_lq_seo_title`, `_lq_meta_description` | Per-page title tag and meta description |

## Page registry

Keys, in reading order: `home`, `start`, `what`, `surah`, `when`, `last10`, `signs`, `worship`, `dua`, `planner`, `faq`, `sources`, `about`, `sitemap`, `contact`. Titles, slugs, menu labels, title tags, meta descriptions, focus keywords and menu membership live in `lq_page_definitions()` in `inc/helpers.php`. Setup publishes the home and sitemap pages in both languages and saves every other page as a draft. Menus list published pages only; the reading path also shows unpublished pages as "Coming soon". Malay pages sit beneath the Malay home at /ms/. Keyword targets per page: docs/seo.md.

## Colour system

The `theme.json` palette is the night scheme and keeps the parent's slugs (`base`, `contrast`, `accent-1` to `accent-6`) so parent templates stay coherent. The dawn scheme overrides the same `--wp--preset--color--*` variables in `css/front.css`, selected by `data-scheme` on `<html>`. Accent overrides arrive as `--lq-accent-night` and `--lq-accent-dawn`.

| Slug | Night | Dawn | Role |
|---|---|---|---|
| base | #141b2e | #f2f1f6 | Ground |
| contrast | #e8e4d8 | #1b2033 | Text |
| accent-1 | #c9a85c | #7a5a14 | Verse, links, buttons |
| accent-2 | #e3cf9a | #a8842f | Pale gold |
| accent-3 | #2c3654 | #dcdae6 | Rules and borders |
| accent-4 | #9aa3b8 | #555c70 | Secondary text |
| accent-5 | #1c2540 | #ffffff | Surface |
| accent-6 | currentColor at 20% | same | Hairline |

## Typography

| Role | Face | Where | Notes |
|---|---|---|---|
| Latin text | EB Garamond (owner-supplied, OFL) | Everywhere | Variable, 400 to 700; covers ā, ī, ū, ḥ, ṣ, ṭ, ḍ, ẓ, ʿ, ʾ |
| Qurʾānic text | KFGQPC HAFS Uthmanic Script v2.2, supplied by the site owner; no fallback face | `.lq-ayah__text`, `.lq-quran`, `.is-style-lq-quran` (block style "Qurʾān verse") | Licence forbids modification: never subset, convert or rename internally. Feed it KFGQPC Hafs-encoded text (quran.com `text_qpc_hafs`); Arabic-Indic digits render as verse markers |
| Hadith, supplications, Arabic terms | Arslan Wessam A (owner-supplied) | `[lang="ar"]`, `.lq-hadith`, `.is-style-lq-hadith`, second in the body stack | `size-adjust: 140%`; `unicode-range` limits downloads to pages with Arabic |
| Display Arabic quotations | Arslan Wessam B (owner-supplied) | `.lq-arabic-quote`, `.is-style-lq-arabic-quote` | Centred, pale gold |
| Fallback only | Noto Naskh Arabic | Behind Arslan | Downloads only if Arslan lacks a glyph |
| Interface | System sans-serif | Menus, footer, captions | |

Article rules: at least 1,200 words; an In brief box, tables and at least one illustration; titles of works appear only in footnotes; every hadith VERIFIED on sunnah.com before inclusion, NOT_FOUND items held back.

Binding rules: Qurʾānic text is always taken verbatim from a verified source and set in the KFGQPC face; it is never retyped, and no other typeface may render it. Hadith and other Arabic never use the KFGQPC face, so the two are always visually distinct. Rejected owner-supplied fonts: Sabon Next LT (Monotype desktop licence does not cover web embedding; lacks ḥ, ṭ, ḍ, ẓ, ʿ, ʾ), Dubidam Arabic (free for personal use only), Special Elite (typewriter display face; lacks the dot-below letters and ʿ ʾ).

## Settings tabs

General, Languages, Colours, Ramadan Dates, Login, Search, Tools.

## Roadmap

| Version | Scope |
|---|---|
| 1.1.0 | Keyword architecture, title tags and meta descriptions (done) |
| 1.2.0 | Alignment with the agency SEO checklist: HTML sitemap, 60-character titles, Rank Math sync (done) |
| 1.3.0 | Typography: EB Garamond, Arslan Wessam for hadith and quotations (done) |
| 1.4.0 | First five English articles and article styling (done) |
| 1.5.0 | Night window: interactive timetable for preset cities (41 from 1.5.2) and the visitor's location (done) |
| 1.6.0 | Perpetual Ramadan calendar; dates roll over each year without editing (done) |
| 1.7.0 | Structured data (Organization, Breadcrumb, Article) and breadcrumbs, per Google's supported list (done) |
| 1.7.x | Remaining English articles, then Malay translations |
| 1.8.0 | Parent-theme compatibility with Twenty Twenty-Five 1.5 (done) |
| 1.9.0 | Built-in English and Malay; Polylang removed (done) |
| 1.9.x | Malay translations of the articles |
| 1.10.0 | Duʿāʾ, evidence and Qurʾān verse blocks, glossary; November to mid-December 2026 |
| 1.11.0 | Performance pass and pre-season SEO review; by 15 January 2027 |

Parent theme: Twenty Twenty-Five, tested with 1.5 (docs/parent-theme.md).

SEO plugin: Rank Math. While it is active, the theme defers on titles, descriptions, robots, canonical, social tags, structured data, breadcrumbs (when Rank Math's are on), the XML sitemap and verification codes (docs/rank-math.md). Theme-only output remains as the fallback when no SEO plugin is active.

Structured data uses only types on Google's supported list (docs/google-guidelines.md): Organization, Breadcrumb, Article, Profile page. FAQPage and HowTo are excluded.

Dates: never write a Gregorian year or date of Ramadan into content, titles or descriptions. Use `[lq_ramadan]` or the Odd nights dates block. Every Gregorian date shown to readers carries its Hijri equivalent (English "27 Ramadan 1448 AH", Malay "27 Ramadan 1448 H"; Shawwal is Syawal in Malay). The Umm al-Qura table runs to 1473 AH (2051); extend it before then.

Work tracking follows the agency template: `lailatulqadar.guide - SEO Work Progress (2026).xlsx` (CHECKPOINT, META INFO, CONTENT, TIMELINE). Month 0 is October 2026; Ramadan 1448 is expected to begin on 8 February 2027, subject to sighting.

## Writing rule: no contractions

English text anywhere in the theme (page content, interface strings, JavaScript messages, documentation and code comments) spells every word out in full: "it is", "do not", "cannot". Contractions are not used. The only exception is verbatim search-query data in the keyword map, which records what searchers typed.
