# Lailatulqadar: Single Source of Truth

Version 1.18.6. This file records the architecture, stored data and binding decisions for the Lailatulqadar child theme. Update it with every release.

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
- Malay copy follows Pedoman Umum Ejaan Bahasa Melayu and Tatabahasa Dewan (3rd ed.). Fixed forms: *Surah al-Qadr* (never *Sūrat*), *al-Quran* (*Al-Quran* only at the start of a sentence), *Rasulullah SAW*, pronouns for Allah capitalised and hyphenated (*-Nya*, *-Mu*, *Engkau*), names without diacritics in running text (Abu Hurairah, Aisyah, Ibnu Abbas, at-Tirmizi). Titles of works in footnotes keep the transliteration of the English notes; "VERIFIED" is *Disahkan*, NOT_FOUND is *TIDAK DITEMUI*.
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
| `inc/security.php`, `inc/security-detector.php` | Security tab: remote-publishing locks, spam gate, scan and trash tool, 410 Gone, reports and log. The detector is plain PHP |
| `js/security.js`, `css/security.css` | Security tab script and styles |
| `tests/security-detector-test.php` | Detector self-test against every shipped article, page definition and pattern, and the attack samples |
| `js/vendor/adhan.min.js`, `js/vendor/adhan.js` | adhan 4.4.6 prayer-time library (MIT); `adhan.js` is its unminified source, loaded when `SCRIPT_DEBUG` is on |
| `inc/schema.php` | JSON-LD graph, breadcrumb trail, XML sitemap trimming, Rank Math merge (`lq_rank_math_json_ld()` on `rank_math/json_ld`). Every Article carries an image: featured image, else the lead photograph from `inc/data/images.json`, else the site mark |
| `inc/schema-content.php` | Readers for what a page shows (questions, steps, tables, verses, hadith, diagrams, footnotes, photo blocks) and the graph merge. No WordPress calls |
| `tests/schema-graph-test.php`, `tests/stubs/wordpress.php` | Graph self-test over every page in both languages, with and without Rank Math. Run `php tests/schema-graph-test.php` |
| `inc/image-sitemap.php` | Image sitemap at /image-sitemap.xml (featured images and photo blocks of every published page), named in robots.txt; off while an SEO plugin is active |
| `blocks/breadcrumbs/` | Visible breadcrumbs above the page title |
| `inc/seo.php` | Title tag (also under Rank Math), meta description (stored on pages; excerpt on posts, term description on archives), canonical link on archives, editor box, SEO plugin detection |
| `inc/contextual-links.php` | Contextual links in the running text of guide pages and posts, to this site and the sister sites |
| `inc/data/network-links.json` | Shared phrase map of the seven-site network; identical in every theme, edit it in all seven together |
| `inc/data/path-chapters.json` | The 4 chapters of the reading path on the front page (page keys and an English and a Malay heading each), read by `lq_path_chapters()` and checked by `tests/path-chapters-test.php`; the guide menu block shows them with `chapters` on. Data only, read with `json_decode()`. The chapters group the reading order and never change it (docs/blocks.md) |
| `inc/data/pages.json` | The page registry: per page key, the menu sets and, for each language role, title, slug, menu label, title tag, meta description and focus keyword. The order of the keys is the reading order. Data only, read with `json_decode()` |
| `inc/data/contextual-links-ms.json` | Malay phrases for the Malay guide pages, keyed by page key |
| `inc/setup.php` | Setup on activation, on update and on demand: page creation, publishing of shipped articles, content refresh, trashing of obsolete copies, draft renaming, SEO prefill |
| `inc/slugs.php` | Addresses WordPress builds from a title leave out the words a, an and the |
| `inc/data/seed-history.json` | md5 of every version of each article shipped in earlier releases, so a page seeded before `_lq_seed_hash` existed is recognised as unedited. Data only, read with `json_decode()`. Whenever an article changes, run `php bin/build-seed-history.php > inc/data/seed-history.json.new` and move the new file into place: the script adds the versions in the git history of `content/` to the hashes already in the file and never drops one |
| `inc/social-icons.php`, `blocks/social-icons/`, `css/social-icons.css`, `images/social/` | Social profile icons: footer block, pattern, `[lailatulqadar_social_icons]`; ten SVGs from the Minimalist Social Icons Pack 2.8 (GPL, CC0 sources) |
| `inc/reading-progress.php`, `js/reading-progress.js`, `css/reading-progress.css` | Reading progress bar on single views; off while the Reading Progress Bar plugin is active |
| `inc/unlist.php`, `js/unlist-admin.js`, `tests/unlist-test.php` | Unlisted pages (replaces Unlist Posts & Pages); off while that plugin is active |
| `inc/search-permalinks.php`, `tests/search-permalinks-test.php` | /search/term/ addresses (replaces Pretty Search Permalinks); off while that plugin is active |
| `inc/error-pages.php`, `inc/error-page-html.php` | The wp-content drop-ins (maintenance 503, php-error 500, db-error 503), written on activation and on an administrator's admin page when missing or outdated, removed on a theme switch, and their Install error pages button on the Tools tab (admin only); the self-contained page builder, also used by `bin/build-error-pages.php` for the static pages in `errors/` (the copies for a site at the domain root); since 1.18.4 it also writes a copy of each into `wp-content/lailatulqadar-errors/` with the site's home address. See `docs/settings.md` |
| `inc/settings.php` | Tabbed settings screen and Tools handlers (admin only): card layout, ARIA tabs switched without a reload, toggle, slider and colour-picker rows (`lailatulqadar_*_row()`), assets enqueued by hook suffix |
| `blocks/guide-menu/` | Header links, numbered reading path, footer |
| `blocks/language-switcher/` | Link to the same page in the other language |
| `css/front.css` | Front end, including dawn and auto schemes |
| `css/admin.css` | Settings screen (cards, tabs, toggles, sliders, colour grid; stacks at 782px) |
| `css/editor.css` | Editor-only adjustments (the editor also loads `front.css`) |
| `js/scheme.js` | Head script for the visitor light/dark switch |
| `inc/languages.php` | Two languages without a plugin: page language, translation pairing, hreflang, html lang, Malay site name, Malay search |
| `inc/parent.php` | Parent stylesheet versioning; parent style variations held back |
| `templates/search.html`, `index.html`, `page-no-title.html` | Search results, the fallback view (title and content, as a page) and the page without a title |
| `inc/no-blog.php`, `tests/no-blog-test.php` | The retired blog: drops the post templates, sends post views and feeds to the language home with a 301, keeps posts out of the sitemaps, and runs the one-time migration. Run `php tests/no-blog-test.php` |
| `patterns/no-results.php` | Bilingual message for empty archives and searches |
| `inc/login.php`, `css/login.css` | Login screen styling, brand block, title, favicon |
| `blocks/logo-mark/` | Header logo lockup: lantern alone, and Arabic calligraphy (full mark with crescent and star on the login screen and larger icons) |
| `images/` | Default favicon (SVG, 32px PNG), touch icon, 512px logo for structured data |
| `blocks/scheme-toggle/` | Light and dark switch button |
| `js/admin.js` | Settings tabs and keys, slider values, wp-color-picker with the accent preview, media chooser, confirmation prompts |
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
| `blocks/image-credits/` | Lists every image with author, licence and source (on the Sources page) |
| `bin/check-content.py`, `.github/workflows/content.yml` | Checks that `content/` holds only `<lang>/<key>.json` seeds in the shape `lq_article_data()` accepts, with every footnote marker matched to its note, and that `inc/data/pages.json` gives every page both roles with all their fields, unique slugs without a, an or the, title-tag page parts of 43 characters at most and meta descriptions of 130 at most; runs on pull requests that touch `content/` or `inc/data/pages.json` |
| `bin/fetch-commons-images.py`, `bin/commons-images.json` | Chosen images per page (Commons files by title; Pixabay and Pexels photos by source and id), and the script that reads each licence through the source's API and writes the WebP files and `inc/data/images.json`. Pixabay and Pexels need `PIXABAY_API_KEY` and `PEXELS_API_KEY` in the environment (repository secrets, which the images workflow passes only to a run started by hand, never to a pull_request run, since a pull request runs its own code); keys never go into the repository |

## Stored data

| Option | Contents |
|---|---|
| `lailatulqadar_settings` | Array: `primary_slug`, `secondary_slug`, `primary_label`, `secondary_label`, `footer_note_primary`, `footer_note_secondary`, `scheme` (`night`, `dawn`, `auto`), `scheme_toggle` (`show`, `hide`), `accent_night`, `accent_dawn`, `calligraphy_id`, `login_tagline_primary`, `login_tagline_secondary`, `login_note_primary`, `login_note_secondary`, `verify_google`, `verify_bing`, `author_name`, `author_url`, `ramadan_year`, `ramadan_start`, `ramadan_alt` (optional override for one year; empty means automatic), `ctx_own`, `ctx_sister` (`1` or `0`), `ctx_own_max` (0 to 15), `ctx_sister_max` (0 to 10), `ctx_extra`, `ctx_extra_ms`, `ctx_exclude`, `sec_xmlrpc_off`, `sec_app_passwords_off`, `sec_rest_lock`, `sec_spam_gate`, `sec_gone` (`1` or `0`, all `1` by default) |
| `lailatulqadar_settings` (1.17.0) | Also `rpb_enabled`, `rpb_color` (empty follows the accent), `rpb_height` (2 to 8), `rpb_position` (`top`, `bottom`), `rpb_types` (comma list, default `page`), `social_footer`, `social_<network>` (URLs), `pretty_search`, `search_base` |
| `lailatulqadar_unlisted_posts` | IDs of unlisted pages; copied once from the plugin's `unlist_posts` |
| `lailatulqadar_rewrite_version` | Theme version and search base of the last rewrite flush |
| `lailatulqadar_pages` | Page IDs keyed by role (`primary`, `secondary`) then page key |
| `lailatulqadar_setup_version` | Theme version that last ran setup |
| `lailatulqadar_blog_retired` | Version of the blog migration that has run (`1.15.0`). Setup runs it once: it clears the posts page and any post template a page was given |
| `lailatulqadar_needs_setup` | Set on activation, cleared once setup has run on an admin visit. Setup also runs when `lailatulqadar_setup_version` differs from `LQ_VERSION` |
| Transient `lailatulqadar_setup_lock` | Held while setup runs (five minutes at most), so two requests never run it at once |
| Transient `lailatulqadar_setup_report` | Result of the last setup run, shown once |
| Post meta `_lq_seo_title`, `_lq_meta_description` | Per-page title tag (the page part; the theme adds " \| Lailatulqadar") and meta description |
| `lailatulqadar_settings['commons_featured']` | `1` or `0`: setup sets each page's lead Commons image as its featured image |
| `lailatulqadar_commons_media` | Attachment IDs of the Commons images copied into the media library, keyed by image file base, so English and Malay pages share one attachment |
| Post meta `_lq_lang`, `_lq_key` | Language and page key of a page setup created or adopted; the theme's ownership marker. Only pages carrying `_lq_lang` are ever trashed as obsolete copies |
| Post meta `_lq_seed_hash` | md5 of the content setup wrote (links resolved, line endings unified, trimmed), a colon, and md5 of its footnotes. A page whose content and footnotes still match is unedited and takes new shipped versions |
| Post meta `_lq_seed_edited` | Set when an editor has changed a page and the shipped article differs; the Tools tab lists it as "Edited, not updated" |
| Post meta `_lq_featured_seeded` | Set once setup has given a page its featured image, so an image an editor removes is not put back |
| `lq_security_log`, `lq_security_counts`, `lq_security_failed`, `lq_security_gone`, `lq_security_htaccess` | Security tab: the capped log (200 lines), per-day counts for 30 days, failed logins by account name for a rolling day, addresses of trashed spam answered with 410, and the state of the .htaccess block. None autoloaded |
| Post meta `_lq_spam_held`, user meta `_lq_last_login` | Time the spam gate held a post; last login time and IP of a user |
| Attachment meta `_lq_credit` | Author, licence, licence deed and source of a Commons attachment |

## Page registry

Keys, in reading order: `home`, `start`, `what`, `surah`, `when`, `last10`, `signs`, `worship`, `dua`, `planner`, `faq`, `sources`, `dilemma`, `about`, `sitemap`, `contact`. Titles, slugs, menu labels, title tags, meta descriptions, focus keywords and menu membership live in `inc/data/pages.json` (data only, read with `json_decode()` by `lq_page_definitions()` in `inc/helpers.php`); setup does not run while that file cannot be read, since every guide page would otherwise look retired. Setup runs on the first admin visit (manage_options) after activation or a version change, never on a front-end request. It publishes the home and sitemap pages in both languages and every page whose article ships in `content/<lang>/<key>.json`; a page with no shipped article (About, Contact) stays a draft. When a shipped article changes, a page whose content and footnotes still match `_lq_seed_hash` (or, before the hash, an earlier shipped version in `inc/data/seed-history.json`) is replaced through `wp_update_post()`, which keeps a revision, and keeps its status; an edited page is never overwritten and is listed on the Tools tab with a button to replace it. Copies of guide pages the theme created (carrying `_lq_lang`) that the registry no longer points to go to the trash when they hold no text or only text the theme wrote; edited copies are kept and reported; pages without the marker are never touched. Menus list published pages only; the reading path also shows unpublished pages as "Coming soon". Malay pages sit beneath the Malay home at /ms/. Keyword targets per page: docs/seo.md.

Title tags always read `<page title, keyword or description> | Lailatulqadar` in both languages and stay under 60 characters with the site name (`LQ_TITLE_LIMIT` 59, `LQ_TITLE_SITE`, `LQ_TITLE_SEP` in `inc/seo.php`). The registry's `seo` value and the `_lq_seo_title` meta hold the page part, 43 characters at most with `[lq_ramadan]` counted as its year; the theme composes the whole tag (`lq_compose_title()`) and gives every other view the same form, cutting a long page part at a word. With Rank Math active, setup writes the whole tag into `rank_math_title`, and `lq_rank_math_title()` (on `rank_math/frontend/title`) holds whatever Rank Math prints to the same form: its site name and separator are replaced by " | Lailatulqadar" and a long page part is cut at a word. Setup replaces a stored title tag only when it is empty, too long, or one the theme wrote as that page's default in an earlier release (`inc/data/title-history.json`, keyed by page and language); an editor's title is never replaced unless it runs over the limit.

Addresses (slugs) never contain the words a, an or the; other small words (is, of, and, dan, di, itu) stay where the wording needs them. Existing addresses are not changed to meet this rule, so no redirects are needed. `inc/slugs.php` enforces it for addresses WordPress builds from a title (new posts and pages on first publish); a slug an editor types, a guide page's registry slug and any existing address are left as they are.

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

Breakpoints: phones to 600px, tablets 601 to 1024px, desktop above. Type is fluid (`settings.typography.fluid` in `theme.json`, and `clamp()` for titles and the verse size). On phones the header menu becomes one horizontally scrolling row of 44px pills; tables wrap their cells to fit the screen and the rounded frame scrolls only when a cell cannot wrap (since 1.15.1); the footer menu wraps onto several lines of 44px links; SVG figures and images scale to the column; Arabic wraps without overflow; the night window's controls are at least 44px tall. The page never scrolls sideways at 320px.

## Images

- Three sources, each with its own licences (`lq_image_sources()`): Wikimedia Commons files under public domain, CC0, CC BY or CC BY-SA; Pixabay photos under the Pixabay Content License; Pexels photos under the Pexels License. `lq_images_data()` drops an image whose page address and licence do not belong to one source. `bin/fetch-commons-images.py` reads each licence from the source's API and refuses a Commons file tagged as a copyright violation or for deletion.
- Every image shows its author, its licence linked to the deed or terms, and a link to its page on its source, beneath the image (`lq_image_credit_html()`), and again in the credits list on the Sources page. Pixabay and Pexels ask for no credit; the guide gives it anyway.
- Stock photos (Pixabay, Pexels) are checked at full size before use: any Arabic shown must be real, legible and correctly identified in the caption, and a caption says only what the image shows. Staged props and generated-looking images are refused.
- The folder `images/commons/`, the script and plan names and the `commons_featured` setting keep their first names for all three sources.
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

Article rules: 1,600 to 2,500 words of body text in each language (footnotes not counted); the Night Planner (a tool page) and Hadith and Sources (a reference page) are exempt, and What Is and Prayer and Worship, which are longer, are kept at their length by the owner's decision; an In brief box, tables and at least one illustration; titles of works appear only in footnotes; every hadith VERIFIED on shamela.ws (and sunnah.com) before inclusion, NOT_FOUND items held back. Links to sources use the linked page's title as their text, never a bare address: a shamela.ws page shows its Arabic title (volume, page, book and chapter, without "المكتبة الشاملة"), with `lang="ar" dir="rtl"`, followed outside the link by a translation of that title in parentheses, in the page's language (English pages in English, Malay pages in Malay); a sunnah.com link shows the collection and number that open its title ("Sahih al-Bukhari 2014"). Classical commentaries (Ibn Ḥajar, al-Nawawī, Ibn Rajab, Ibn Kathīr) are cited as their authors' views, read on shamela.ws ("Read on …"); reports they mention are not presented as verified hadith, and practices of the early generations are labelled as theirs, not as sayings of the Prophet ﷺ. Arabic text is copied code point for code point from its source and never Unicode-normalised (NFC reorders marks and merges alif with madda, which breaks the KFGQPC orthography); a Malay page carries the same Arabic as its English page. Where gradings differ, al-Albānī's takes precedence; other gradings, such as Dār al-Salām's, are shown beside it.

Binding rules: Qurʾānic text is always taken verbatim from a verified source and set in the KFGQPC face; it is never retyped, and no other typeface may render it. Hadith and other Arabic never use the KFGQPC face, so the two are always visually distinct. Rejected owner-supplied fonts: Sabon Next LT (Monotype desktop licence does not cover web embedding; lacks ḥ, ṭ, ḍ, ẓ, ʿ, ʾ), Dubidam Arabic (free for personal use only), Special Elite (typewriter display face; lacks the dot-below letters and ʿ ʾ).

## No blog

On 3 November 2026 the owner decided that lailatulqadar.guide has no blog. The site is the fixed guide in English and Malay, and posts are retired.

- The theme ships no template for posts. `home`, `archive`, `single`, `single-post`, `date`, `author`, `category` and `tag` are dropped from the editor and from the template hierarchy, the parent's copies and Site Editor copies with them (`lq_drop_retired_templates()`).
- The blog home, single posts, the date, author, category and tag archives and every feed answer 301 to the home page of their language: the Malay home at /ms/ for a Malay post or an address under /ms/, the front page for everything else. The 410 for trashed spam stays as it was, since that address is a 404 first.
- A page that was the posts page keeps its content. The migration records its ID in `lailatulqadar_former_posts_page`, and its address answers 301 to the home page of its language. A guide page is never redirected this way.
- A post with no language mark is Malay when its address lies under /ms/.
- Posts are out of the core sitemap, the image sitemap and the Rank Math sitemap (with categories and tags). The head carries no feed links.
- The admin keeps its Posts menu. Nobody should write a post; one written anyway is never shown.

## Settings tabs

General, Languages, Colours, Ramadan Dates, Login, Search, Contextual links, Images, Reading and sharing (1.17.0), Security, Tools.

The screen needs `manage_options`, kept in 1.16.0 in place of `edit_theme_options` because it holds the security switches and setup. New PHP functions since 1.16.0 take the prefix `lailatulqadar_`; existing `lq_` functions, option keys, handles, block names and shortcodes keep their names. `git archive` leaves out `.github`, `bin`, `tests`, `.claude` (which holds `CLAUDE.md` and `FARUQI.md`) and the git files (`.gitattributes`).

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
| 1.9.8 | Malay translations of the articles (done) |
| 1.10.0 | Duʿāʾ, evidence and Qurʾān verse blocks, glossary; November to mid-December 2026 |
| 1.11.0 | Performance pass and pre-season SEO review; by 15 January 2027 |

Parent theme: Twenty Twenty-Five, tested with 1.5 (docs/parent-theme.md).

SEO plugin: Rank Math. While it is active, the theme defers on titles, descriptions, robots, canonical, social tags, breadcrumbs (when Rank Math's are on), the XML sitemap and verification codes, and adds its structured data to Rank Math's graph (docs/rank-math.md). Theme-only output remains as the fallback when no SEO plugin is active.

Structured data: the owner keeps every schema.org type that describes a page, Google rich result or not, and never drops one (decision of 3 November 2026). A type goes on a page only when what it describes is shown there. One graph per page: with Rank Math active the theme merges into Rank Math's graph through `rank_math/json_ld` and never prints a second one; `@id` values are the page address plus a fragment. FAQPage goes where questions and answers are shown, HowTo where numbered steps are shown. The types in use are listed in docs/google-guidelines.md.

Dates: never write a Gregorian year or date of Ramadan into content, titles or descriptions. Use `[lq_ramadan]` or the Odd nights dates block. Every Gregorian date shown to readers carries its Hijri equivalent (English "27 Ramadan 1448 AH", Malay "27 Ramadan 1448 H"; Shawwal is Syawal in Malay). The Umm al-Qura table runs to 1473 AH (2051); extend it before then.

Work tracking follows the agency template: `lailatulqadar.guide - SEO Work Progress (2026).xlsx` (CHECKPOINT, META INFO, CONTENT, TIMELINE). Month 0 is October 2026; Ramadan 1448 is expected to begin on 8 February 2027, subject to sighting.

## Contextual links

The first mention of a known phrase in the running text of a guide page or a blog post links to the inner page that treats it, here or on a sister site. The phrase map `inc/data/network-links.json` is shared by the seven themes of the network and must stay identical in all of them; each phrase belongs to exactly one page, chosen by the site whose subject it is (Laylat al-Qadr, the last ten nights, the odd nights, iʿtikāf and the night prayer belong to this site). Malay pages use `inc/data/contextual-links-ms.json` and link only within the Malay guide. Caps: 4 links to this site and 3 to sister sites a page by default, one new link a paragraph. Never in headings, quotations, verses, hadith, Arabic, figures, tables, footnotes or the "In brief" box.

## Writing rule: no contractions

English text anywhere in the theme (page content, interface strings, JavaScript messages, documentation and code comments) spells every word out in full: "it is", "do not", "cannot". Contractions are not used. The only exception is verbatim search-query data in the keyword map, which records what searchers typed.
