# Lailatulqadar: Single Source of Truth

Version 1.9.5. This file records the architecture, stored data and binding decisions for the Lailatulqadar child theme. Update it with every release.

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
| `inc/contextual-links.php` | Contextual links in the running text of guide pages and posts, to this site and the sister sites |
| `inc/data/network-links.json` | Shared phrase map of the seven-site network; identical in every theme, edit it in all seven together |
| `inc/data/contextual-links-ms.json` | Malay phrases for the Malay guide pages, keyed by page key |
| `inc/setup.php` | Setup on activation, on update and on demand: page creation, publishing of shipped articles, content refresh, trashing of obsolete copies, draft renaming, SEO prefill |
| `inc/data/seed-history.json` | md5 of every version of each article shipped in earlier releases (rebuilt from the git history of `content/`), so a page seeded before `_lq_seed_hash` existed is recognised as unedited. Data only, read with `json_decode()`. Add the old version's md5 here whenever an article changes |
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
| `content/<lang>/<key>.json` | Shipped articles, written into their pages and published by setup: block markup under `content`, core footnotes under `footnotes`. Data only, read with `json_decode()` and validated; never included or executed |
| `fonts/eb-garamond/`, `fonts/noto-naskh-arabic/` | Self-hosted WOFF2 subsets with OFL licences |
| `fonts/arslan-wessam/` | Arslan Wessam A and B, WOFF2 subsets, with a notice (no licence text in the source files) |
| `fonts/kfgqpc/` | KFGQPC Hafs as a WOFF2 conversion of the original TTF, with its licence |
| `languages/` | POT file and `ms_MY` translation |
| `inc/images.php` | Commons images: reads and checks `inc/data/images.json`, renders figures with credits, the credits list, and sets lead images as featured images during setup |
| `inc/data/images.json` | Credits for every Commons image, keyed by page key (file, Commons title, alt, caption, author, licence, licence deed, file page, widths, size). Data only, read with `json_decode()` |
| `images/commons/` | The Commons images as WebP, `<key>-<n>-<width>.webp` at 640 and 1200 pixels (smaller originals are never enlarged), each under 200 KB |
| `blocks/photo/` | Commons image block (`lailatulqadar/photo`, attributes `key` and `n`): one image of a page with caption and credit |
| `blocks/image-credits/` | Lists every Commons image with author, licence and source (on the Sources page) |
| `bin/fetch-commons-images.py`, `bin/commons-images.json` | Chosen Commons files per page, and the script that checks each licence through the Commons API and writes the WebP files and `inc/data/images.json` |

## Stored data

| Option | Contents |
|---|---|
| `lailatulqadar_settings` | Array: `primary_slug`, `secondary_slug`, `primary_label`, `secondary_label`, `footer_note_primary`, `footer_note_secondary`, `scheme` (`night`, `dawn`, `auto`), `scheme_toggle` (`show`, `hide`), `accent_night`, `accent_dawn`, `calligraphy_id`, `login_tagline_primary`, `login_tagline_secondary`, `login_note_primary`, `login_note_secondary`, `verify_google`, `verify_bing`, `author_name`, `author_url`, `ramadan_year`, `ramadan_start`, `ramadan_alt` (optional override for one year; empty means automatic), `ctx_own`, `ctx_sister` (`1` or `0`), `ctx_own_max` (0 to 15), `ctx_sister_max` (0 to 10), `ctx_extra`, `ctx_extra_ms`, `ctx_exclude` |
| `lailatulqadar_pages` | Page IDs keyed by role (`primary`, `secondary`) then page key |
| `lailatulqadar_setup_version` | Theme version that last ran setup |
| `lailatulqadar_needs_setup` | Set on activation, cleared once setup has run on an admin visit. Setup also runs when `lailatulqadar_setup_version` differs from `LQ_VERSION` |
| Transient `lailatulqadar_setup_lock` | Held while setup runs (five minutes at most), so two requests never run it at once |
| Transient `lailatulqadar_setup_report` | Result of the last setup run, shown once |
| Post meta `_lq_seo_title`, `_lq_meta_description` | Per-page title tag and meta description |
| `lailatulqadar_settings['commons_featured']` | `1` or `0`: setup sets each page's lead Commons image as its featured image |
| `lailatulqadar_commons_media` | Attachment IDs of the Commons images copied into the media library, keyed by image file base, so English and Malay pages share one attachment |
| Post meta `_lq_lang`, `_lq_key` | Language and page key of a page setup created or adopted; the theme's ownership marker. Only pages carrying `_lq_lang` are ever trashed as obsolete copies |
| Post meta `_lq_seed_hash` | md5 of the content setup wrote (links resolved, line endings unified, trimmed), a colon, and md5 of its footnotes. A page whose content and footnotes still match is unedited and takes new shipped versions |
| Post meta `_lq_seed_edited` | Set when an editor has changed a page and the shipped article differs; the Tools tab lists it as "Edited, not updated" |
| Post meta `_lq_featured_seeded` | Set once setup has given a page its featured image, so an image an editor removes is not put back |
| Attachment meta `_lq_credit` | Author, licence, licence deed and source of a Commons attachment |

## Page registry

Keys, in reading order: `home`, `start`, `what`, `surah`, `when`, `last10`, `signs`, `worship`, `dua`, `planner`, `faq`, `sources`, `dilemma`, `about`, `sitemap`, `contact`. Titles, slugs, menu labels, title tags, meta descriptions, focus keywords and menu membership live in `lq_page_definitions()` in `inc/helpers.php`. Setup runs on the first admin visit (manage_options) after activation or a version change, never on a front-end request. It publishes the home and sitemap pages in both languages and every page whose article ships in `content/<lang>/<key>.json`; a page with no shipped article (About, Contact, a Malay page awaiting translation) stays a draft. When a shipped article changes, a page whose content and footnotes still match `_lq_seed_hash` (or, before the hash, an earlier shipped version in `inc/data/seed-history.json`) is replaced through `wp_update_post()`, which keeps a revision, and keeps its status; an edited page is never overwritten and is listed on the Tools tab with a button to replace it. Copies of guide pages the theme created (carrying `_lq_lang`) that the registry no longer points to go to the trash when they hold no text or only text the theme wrote; edited copies are kept and reported; pages without the marker are never touched. Menus list published pages only; the reading path also shows unpublished pages as "Coming soon". Malay pages sit beneath the Malay home at /ms/. Keyword targets per page: docs/seo.md.

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

## Shape and layout

Radius tokens live in `theme.json` under `settings.custom.radius` and reach CSS as `--wp--custom--radius--small` (8px), `--medium` (14px), `--large` (22px) and `--pill` (999px). Every stylesheet uses them, with the same value as a fallback for screens that do not load the global styles (the login screen). Cards, figures, images, the In brief box, tables (outer corners), verse and hadith cards, the footnotes area and the night window use `large` or `medium`; inputs `medium`; buttons, tags, categories, the "Coming soon" badges, header menu items, breadcrumbs chips, pagination, the language switcher and credit links are pills (`pill`). Colours stay on the palette variables, so both schemes follow, and focus outlines stay visible. No inline styles.

Breakpoints: phones to 600px, tablets 601 to 1024px, desktop above. Type is fluid (`settings.typography.fluid` in `theme.json`, and `clamp()` for titles and the verse size). On phones the header menu becomes one horizontally scrolling row of 44px pills; tables scroll inside their rounded frame; SVG figures and images scale to the column; Arabic wraps without overflow; the night window's controls are at least 44px tall. The page never scrolls sideways at 320px.

## Images

- Only Wikimedia Commons files under public domain, CC0, CC BY or CC BY-SA; `lq_images_data()` drops any other licence, and `bin/fetch-commons-images.py` refuses it, and any file tagged as a copyright violation or for deletion, after reading the file's own licence from the Commons API.
- Every image shows its author, its licence linked to the deed, and a link to its file page on Wikimedia Commons, beneath the image (`lq_image_credit_html()`), and again in the credits list on the Sources page.
- No depiction of the Prophet ﷺ or any prophet, nothing sectarian or polemical. Subjects: night skies, the crescent, mosques at night, prayer and iʿtikāf shown respectfully (from behind or at a distance where possible), Qurʾān manuscripts, lanterns, dawn.
- No file used by a sister theme of the network (checked by file name, by author and subject, and by SHA-256).
- Articles place images with `<!-- wp:lailatulqadar/photo {"key":"<key>","n":<n>} /-->`; image 1 is the lead, placed after the In brief box, eager and `fetchpriority="high"`; later images are lazy. Each carries `width`, `height`, `srcset` and `sizes`.
- Setup sets the lead image as the page's featured image (Images tab), never replacing one an editor chose.

## Typography

| Role | Face | Where | Notes |
|---|---|---|---|
| Latin text | EB Garamond (owner-supplied, OFL) | Everywhere | Variable, 400 to 700; covers ā, ī, ū, ḥ, ṣ, ṭ, ḍ, ẓ, ʿ, ʾ |
| Qurʾānic text | KFGQPC HAFS Uthmanic Script v2.2, supplied by the site owner; no fallback face | `.lq-ayah__text`, `.lq-quran`, `.is-style-lq-quran` (block style "Qurʾān verse") | Licence forbids modification. The owner chose on 2026-10-26 to serve a WOFF2 conversion of the KFGQPC HAFS Uthmanic Script (107 KB, from 298 KB), accepting that the KFGQPC licence forbids modifying the font. The conversion is lossless for glyphs, cmap and OpenType layout; the digital signature (DSIG) is dropped. Never subset it or rename it internally. Feed it KFGQPC Hafs-encoded text (quran.com `text_qpc_hafs`); Arabic-Indic digits render as verse markers |
| Hadith, supplications, Arabic terms | Arslan Wessam A (owner-supplied) | `[lang="ar"]`, `.lq-hadith`, `.is-style-lq-hadith`, second in the body stack | `size-adjust: 140%`; `unicode-range` limits downloads to pages with Arabic |
| Display Arabic quotations | Arslan Wessam B (owner-supplied) | `.lq-arabic-quote`, `.is-style-lq-arabic-quote` | Centred, pale gold |
| Fallback only | Noto Naskh Arabic | Behind Arslan | Downloads only if Arslan lacks a glyph |
| Interface | System sans-serif | Menus, footer, captions | |

Article rules: at least 1,200 words; an In brief box, tables and at least one illustration; titles of works appear only in footnotes; every hadith VERIFIED on sunnah.com (and shamela.ws) before inclusion, NOT_FOUND items held back. Where gradings differ, al-Albānī's takes precedence; other gradings, such as Dār al-Salām's, are shown beside it.

Binding rules: Qurʾānic text is always taken verbatim from a verified source and set in the KFGQPC face; it is never retyped, and no other typeface may render it. Hadith and other Arabic never use the KFGQPC face, so the two are always visually distinct. Rejected owner-supplied fonts: Sabon Next LT (Monotype desktop licence does not cover web embedding; lacks ḥ, ṭ, ḍ, ẓ, ʿ, ʾ), Dubidam Arabic (free for personal use only), Special Elite (typewriter display face; lacks the dot-below letters and ʿ ʾ).

## Settings tabs

General, Languages, Colours, Ramadan Dates, Login, Search, Contextual links, Images, Tools.

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

## Contextual links

The first mention of a known phrase in the running text of a guide page or a blog post links to the inner page that treats it, here or on a sister site. The phrase map `inc/data/network-links.json` is shared by the seven themes of the network and must stay identical in all of them; each phrase belongs to exactly one page, chosen by the site whose subject it is (Laylat al-Qadr, the last ten nights, the odd nights, iʿtikāf and the night prayer belong to this site). Malay pages use `inc/data/contextual-links-ms.json` and link only within the Malay guide. Caps: 4 links to this site and 3 to sister sites a page by default, one new link a paragraph. Never in headings, quotations, verses, hadith, Arabic, figures, tables, footnotes or the "In brief" box.

## Writing rule: no contractions

English text anywhere in the theme (page content, interface strings, JavaScript messages, documentation and code comments) spells every word out in full: "it is", "do not", "cannot". Contractions are not used. The only exception is verbatim search-query data in the keyword map, which records what searchers typed.
