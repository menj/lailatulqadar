# Changelog

All notable changes to the Lailatulqadar theme are recorded here.

## 1.9.0 (2026-09-26)

The theme now runs both languages itself. Polylang is no longer needed or supported. See docs/languages.md.

### Added
- `inc/languages.php`: page language and translation pairing without a plugin. The Malay home is the page at /ms/ (template "Language home page", `templates/page-home.html`), and every Malay guide page sits beneath it, so its address begins with /ms/ through WordPress's own page hierarchy. Each page carries `_lq_lang` (en or ms) and `_lq_translation` (the paired page).
- On Malay pages: `<html lang="ms-MY">`, the site name "Lailatulqadar" (Languages tab), Malay wording on the search screen, and `?lang=ms` on search forms so results stay in Malay.
- hreflang links (en, ms-MY, x-default) on every page that has a published translation, and on the two home pages. Printed with or without an SEO plugin, since Rank Math does not produce them.
- Language switcher: links to the same page in the other language, or to that language's home when no translation is published.
- Helpers `lq_role_of()`, `lq_is_home()`, `lq_translation_of()`, `lq_hreflang()`.

### Changed
- Run setup always creates both languages, places the Malay pages beneath the Malay home, gives the Malay home the address /ms/, and pairs every page with its translation. Pages adopted from a Polylang install keep their content and move under the Malay home.
- Languages tab explains the arrangement and shows the Malay home address; Polylang language-code fields removed.
- Internal links in seeded articles resolve to the real page addresses.

### Removed
- Every Polylang call and requirement. A notice asks for Polylang to be deactivated if it is still active.

## 1.8.1 (2026-09-26)

### Added
- Reading path on the front page: guide pages not yet published appear in their place as plain text with a "Coming soon" ("Akan datang") label, so the full plan shows from launch. Header, footer and HTML sitemap still list published pages only. Filter `lq_path_show_upcoming` turns this off.
- Tools tab, "Guide pages": every guide page in both languages with its word count and state (published; draft ready to publish; draft not written; published with little or no text; not created), linked to the editor. Buttons to publish every written draft at once and to return published pages with little or no text to draft. A note when WordPress's "Sample Page" is still published, and a warning when Polylang is not ready.
- `lq_page_status_report()` and `lq_word_count()` helpers.

## 1.8.0 (2026-09-25)

Checked against Twenty Twenty-Five 1.5, supplied by the site owner. See docs/parent-theme.md.

### Added
- `templates/home.html` and `templates/page-no-title.html`. The parent's versions had still applied to the posts index and to pages using the "Page without title" template.
- Heading on the posts index (`patterns/blog-title.php`): the posts page's title, or "Posts" / "Catatan".
- `inc/parent.php`: the parent stylesheet now carries the parent's version number; the parent's style variations, colour palettes and typography presets are held back from the Site Editor (filter `lq_allow_parent_variations` restores them). The parent's block and section styles remain.
- `theme.json`: code blocks use the child's monospace stack at weight 400, replacing the parent's reference to Fira Code, which the child does not load; the "Page without title" template is named in the child.

### Changed
- `front.css` now loads after the parent stylesheet by declared dependency, so the child's rules always win on equal specificity.

### Checked
- Every parent style rule and template references only presets the child defines, apart from the two font references now overridden. No parent pattern references a parent font.

## 1.7.9 (2026-09-25)

### Changed
- Header: the logo lockup uses the lantern alone, beside the calligraphy. The full mark with the crescent and star remains on the login screen, the touch icon and the 512 px structured-data logo; the browser-tab icon keeps the lantern alone.

## 1.7.8 (2026-09-25)

### Changed
- Logo mark redrawn as a Ramadan lantern: an onion dome with petal lines and a hanging ring, a collar, a glass body with an arched window lit by a flame, and a stepped base, set against a crescent with a four-pointed star. Proportions, dome and window drawn after the reference icon supplied by the site owner; the artwork is original.
- `lq_mark_svg( 'icon' )` gives the lantern alone for very small sizes. The browser-tab icon (SVG and 32 px) uses it; the touch icon, the 512 px structured-data logo, the header and the login screen use the full mark.
- The mark is larger in the header (2.6rem) and on the login screen (88 px) to show the detail.

## 1.7.7 (2026-09-25)

### Added
- Templates `single.html`, `archive.html`, `search.html` and `index.html`, so every view carries the theme's design where the parent theme's templates had applied before (a narrow left-set post column with an open comment form, and archives and search results printing whole articles).
  - Single post: breadcrumbs, category eyebrow, title, date, featured image, content and previous/next links; no comment form.
  - Archive, blog index and search: breadcrumbs, title, search box (search only), and the entries as cards with date, linked title and a 32-word excerpt; numbered pagination; a bilingual "nothing matched" message with the reading path (`patterns/no-results.php`).
- Breadcrumbs extended to posts (Home, category, post), archives, the blog index and search results; structured data now covers single posts as Article.
- Guide pages without a written excerpt use their meta description as the excerpt in lists and search results.

## 1.7.6 (2026-09-25)

### Changed
- Logo: the rising-sun mark is replaced by a hanging mosque lamp (qandīl) with its flame, paired with the Arabic calligraphy of ليلة القدر. Header lockup: lamp, calligraphy, a thin rule, then the site title. Login screen: lamp, calligraphy, site name, tagline. Both follow the night and dawn schemes.
- Favicon, touch icon and the 512-pixel structured-data logo redrawn with the lamp on an indigo tile.

### Added
- General tab, "Logo calligraphy": choose a transparent PNG (or SVG, where the site allows SVG uploads) from the Media Library; the theme recolours it to the scheme's gold with a CSS mask, so one file serves both schemes. With no image chosen, the words are typeset in Arslan Wessam B.
- `lq_calligraphy_html()` helper; `lq_mark_svg()` now returns the lamp.

## 1.7.5 (2026-09-25)

### Added
- Login screen (`inc/login.php`, `css/login.css`) styled to the theme on every wp-login.php screen: log in, lost password, reset password, registration and the interim login.
  - Brand block above the form: the logo mark, the site name in EB Garamond, a tagline and an optional note, in the language the visitor logs in with (English or Malay).
  - Night sky with a faint star field and a rayless sun rising at the foot of the page, echoing the mark; the dawn scheme uses the light palette, and "auto" follows the device.
  - Card with a gold top rule, cream inputs with a gold focus ring, a full-width gold button, and restyled messages, links, privacy link and language switcher.
  - Accent colours from the Colours tab; favicon as on the site; browser title "Log In ‹ Laylat al-Qadr" (WordPress removed from the title).
- Login settings tab: tagline and note in English and Malay, with a preview link.
- `lq_mark_svg()` helper: one source for the logo mark, used by the header block and the login screen.

## 1.7.4 (2026-09-25)

Rank Math is the site's SEO plugin; the theme now gives way to it on everything it controls. Tested against Rank Math 1.0.279 with the plugin on and off. See docs/rank-math.md.

### Added
- Rank Math variables `%lq_ramadan_year%` and `%lq_ramadan_hijri%`, registered through Rank Math's own variable system, so dated titles and descriptions roll over inside Rank Math and show correctly in its snippet preview.
- Breadcrumbs block: shows Rank Math's trail (and leaves BreadcrumbList to Rank Math) when Rank Math breadcrumbs are on; styled to match the theme.

### Changed
- Run setup writes Rank Math fields only when they are empty or still hold a default from an earlier theme release; dated defaults are written with `%lq_ramadan_year%`. Fields edited in Rank Math are never overwritten. Fixed-year "2027" values written by earlier releases are converted.
- Verification codes: the theme prints none while an SEO plugin is active; the Search tab points to Rank Math's Webmaster Tools settings instead of showing the fields.
- General tab: the author fields state that the SEO plugin supplies structured data while it is active.

### Already in place (confirmed)
- Title tags, meta descriptions, structured data, the XML sitemap and the editor's Search appearance box were already handed to the SEO plugin; testing showed one title, one description, one canonical and a single JSON-LD graph (Rank Math's) on every page.

## 1.7.3 (2026-09-25)

Second pass against Google's SEO Starter Guide and supported structured data list.

### Added
- Profile page structured data on the About page in both languages (`WebPage` + `ProfilePage`, with `mainEntity`, `dateCreated`, `dateModified`): the author named on the General tab, or the organisation when none is named.
- Search tab: Google Search Console and Bing Webmaster Tools verification codes. Accepts the code or the whole meta tag; printed on the front page only.

### Changed
- Last Ten Nights article: the "Read more" column became "Where this guide covers it", and its unlinked "Seeking forgiveness" entry now links to the duʿāʾ page.

## 1.7.2 (2026-09-25)

### Added
- Logo mark (`lailatulqadar/logo-mark`) beside the site title in the header: a sun at the horizon without rays, the sign of the morning after Laylat al-Qadr (Muslim 762a), beneath a four-pointed star for the night itself. Drawn in the scheme's colours, so it follows night and dawn. Decorative for assistive technology; the site title carries the name and link.
- `images/`: `lailatulqadar-mark.svg` (favicon on an indigo tile), `favicon-32.png`, `apple-touch-icon.png` (180 × 180) and `lailatulqadar-mark-512.png`. They serve as favicon, touch icon and Organization logo until a Site Icon is set in Settings > General, which then takes over.

### Changed
- Header items align on their centres, so the mark, title, menu and switches sit on one line.

## 1.7.1 (2026-09-25)

### Added
- Light and dark switch (`lailatulqadar/scheme-toggle`) in the header, after the language switcher. It shows a sun in the night scheme and a moon in the dawn scheme, labels itself in English or Malay ("Switch to light mode", "Tukar ke mod cerah"), and remembers the visitor's choice in their browser (`localStorage` key `lq-scheme`). The site-wide scheme remains what first-time visitors see.
- `js/scheme.js`, loaded in the head so a saved choice applies before the page paints.
- Colours tab: option to show or hide the switch.

### Fixed
- Night window in the dawn scheme: the bar now darkens towards the middle of the night (warm sand at sunset and dawn, deep indigo at midnight) where it had faded to white. The "now" marker gained a halo so it stays visible on light and dark parts of the bar in both schemes.

## 1.7.0 (2026-09-25)

Audited against Google's SEO Starter Guide and Google's list of supported structured data (updated 15 June 2026), both supplied by the site owner. See docs/google-guidelines.md.

### Added
- JSON-LD graph on every page (`inc/schema.php`): Organization (logo from the Site Icon), WebSite, WebPage, BreadcrumbList, and Article on the ten guide articles (headline, description, dates, author, publisher, language, featured image when set). Language-aware (`en`, `ms-MY`). Filter `lq_schema_graph`. Printed only when no SEO plugin is active.
- Breadcrumbs block (`lailatulqadar/breadcrumbs`), placed above the title in the page template; Home or Utama, then the page.
- Author name and profile URL for structured data (General tab); empty means each page author's display name and the About page.

### Changed
- XML sitemap limited to pages: the user and taxonomy sitemaps, which would list empty archives, are removed (theme-only; SEO plugins keep control of their own sitemaps).
- Night Planner title tags and descriptions now describe the night window ("Laylat al-Qadr Night Planner: Last Third Times by City"); setup replaces the old checklist wording.

### Removed from the plan
- FAQPage schema: Google no longer lists it among supported rich results.

## 1.6.1 (2026-09-25)

### Added
- Hijri equivalents beside every Gregorian date the site generates:
  - `[lq_ramadan]` adds the Hijri date in brackets to `start`, `alt`, `eid` and `night` ("Friday 5 March 2027 (night of 27 Ramadan 1448 AH)"); `hijri="0"` suppresses it where the sentence already names the Hijri date. New `show="hijri_night"`.
  - Odd nights dates block: the first column names each night by its Hijri date ("Night of 27 Ramadan 1448 AH"), the column headings state which Gregorian date 1 Ramadan falls on, and the Eid row reads "1 Shawwal 1448 AH".
  - Night window: a Hijri line under each night's heading, Hijri dates in the status line and in the calendar entries, "1 Ramadan:" on the start-date choices, and Hijri dates in each night card's accessible label.
  - Ramadan Dates tab: each Gregorian date shown with its Hijri equivalent.
- Malay forms use "H" and Syawal ("malam 27 Ramadan 1448 H", "1 Syawal 1448 H"); English uses "AH" and Shawwal.
- `lq_hijri()` helper.

### Changed
- When Is Laylat al-Qadr? and The Last Ten Nights of Ramadan: headings read "Ramadan 1448 AH (2027)", and the text gives the Hijri date of the 27th night and of Eid.

## 1.6.0 (2026-09-25)

### Added
- Perpetual Ramadan calendar (`inc/ramadan.php`): 1 Ramadan and 1 Shawwal for 1446 to 1473 AH (2025 to 2051) under the Umm al-Qura calendar, generated from the Unicode CLDR "islamic-umalqura" calendar (ICU 78.2) and checked against published dates for 1446, 1447 and 1448. The site shows the Ramadan in progress, or the next one, and rolls over the day after Eid. Each year offers the following day as the alternative start for countries that rely on local sighting.
- Shortcode `[lq_ramadan]` (`show="year|hijri|start|alt|eid|night"`, `n`, `which`, `style`) for dates in text, headings, title tags and meta descriptions. Expanded for the theme's own tags and inside Rank Math and Yoast titles and descriptions.
- Odd nights dates block (`lailatulqadar/odd-nights`), with an optional expected Eid row, in English and Malay.
- Night window: year switcher from the previous Ramadan to ten years ahead; the start choice now means "Umm al-Qura date" or "one day later" in every year.
- Filter `lq_ramadan_today` for previewing another date.

### Changed
- Ramadan Dates tab: shows the next five years automatically; the date fields became an optional override for one year when an official announcement differs.
- When Is Laylat al-Qadr? and The Last Ten Nights of Ramadan use the shortcode and the new block in place of fixed 2027 text and tables. Their title tags and meta descriptions do the same; setup replaces the fixed-2027 versions from earlier releases.
- Night window label "Ramadan began on" became "Start of Ramadan", which reads correctly for future years.

## 1.5.2 (2026-09-25)

### Added
- Three cities in the night window, all in the Southeast Asia group: Marawi (Philippines; 8.0047, 124.2854; Asia/Manila; 20° / 18°), Narathiwat (Thailand; 6.4350, 101.8229; Asia/Bangkok; 20° / 18°) and Maungdaw (Myanmar; 20.8269, 92.3661; Asia/Yangon; Karachi). Coordinates from OpenStreetMap Nominatim. The presets now number 41.

## 1.5.1 (2026-09-25)

### Changed
- Night window on tablets (30rem to 56rem wide, which covers iPad mini, iPad and iPad Air in portrait and small Android tablets): all ten nights show at once in two rows of five, with no sideways scrolling. From 40rem to 56rem the city and start-date controls sit side by side, the location button spans the city column, and the two start dates share one line.
- Hover lift on night cards applies only to devices with a pointer that hovers, so touch screens do not show a stuck hover state.
- The "tonight" dot on a night card no longer changes the card's height.

### Fixed
- The "In brief" box, tables, figures and verse cards now align with the text column; their padding had pushed them wider than the column at every screen size.

## 1.5.0 (2026-09-25)

### Added
- Night window block (`lailatulqadar/night-window`): an interactive timetable of the last ten nights. For a chosen city it shows when each night begins (sunset), when its last third begins, and when dawn ends it, with a live status line ("Tonight is the 23rd night…", countdown to the last third), a visual bar of the night divided into thirds with a "now" marker, and stat cards.
- 38 preset cities across the Muslim world plus London and New York, grouped by region, each with its national or nearest standard calculation method; "Use my location" option calculated on the device.
- Choice of Ramadan start date (expected and alternative), remembered in the browser along with the city.
- Calendar export (.ics) for one night or all ten, each with a reminder fifteen minutes before the last third.
- English and Malay interfaces; the Malay page defaults to Kuala Lumpur, the English page to Makkah.
- Ramadan Dates settings tab (Hijri year, expected start, alternative start).
- Night Planner page content in English and Malay, seeded into empty drafts by Run setup, with the last-third hadith (al-Bukhārī 1145, VERIFIED).
- adhan 4.4.6 (MIT) bundled in `js/vendor/`.

## 1.4.0 (2026-09-25)

### Added
- First tranche of English articles in `content/en/`: Laylat al-Qadr Duʿāʾ (1,333 words), Sūrat al-Qadr (1,429), When Is Laylat al-Qadr? (1,317), The Last Ten Nights of Ramadan (1,268), Signs of Laylat al-Qadr (1,240). Each has an "In brief" box, tables, an inline SVG illustration, Qurʾānic text in KFGQPC Hafs from the verified Quran.com text, hadith Arabic from sunnah.com, and footnotes in the core Footnotes block.
- Run setup fills any empty draft with its shipped article and footnotes. Internal links written as `{{url:key}}` resolve to the page address in the same language. Pages stay drafts until published.
- Styles for the brief box, tables, figures, verse and hadith cards, and footnotes.

## 1.3.0 (2026-09-25)

### Changed
- Latin text now uses EB Garamond (SIL Open Font License), supplied by the site owner, in place of Literata. It covers the full transliteration set (ā, ī, ū, ḥ, ṣ, ṭ, ḍ, ẓ, ʿ, ʾ). Subset to Latin ranges, weights 400 to 700: about 90 KB per style. Body size raised slightly to suit the face.
- Non-Qurʾānic Arabic (hadith, supplications, quotations, Arabic terms) now uses Arslan Wessam A, supplied by the site owner, so it is visibly distinct from Qurʾānic text in KFGQPC Hafs. Noto Naskh Arabic stays only as a fallback for glyphs Arslan lacks and is not downloaded otherwise.
- Arslan faces use `size-adjust: 140%` so their small drawn size matches the Latin line.

### Added
- "Hadith or supplication" paragraph style (Arslan Wessam A, right-to-left).
- "Arabic quotation (display)" paragraph style (Arslan Wessam B, centred, pale gold).

### Removed
- Literata font files.

## 1.2.0 (2026-09-25)

Aligned with the SEO Work Progress template (the agency checklist) supplied on 25 September 2026.

### Added
- HTML sitemap page in both languages (`/sitemap/`, `/ms/peta-laman/`), published by setup, listed in the footer, and rendered by the guide menu block's new `sitemap` set.
- Rank Math support: on Run setup, empty Rank Math title, description and focus keyword fields are filled from the page registry. The registry now carries a focus keyword per page and language.

### Changed
- Title tag limit lowered from 65 to 60 characters to match the template. Five titles were shortened (front page, Signs, Duʿāʾ, Night Planner, Soalan Lazim).
- Setup now replaces any stored title tag or meta description that exceeds its limit with the registry value.

## 1.1.1 (2026-09-25)

### Changed
- Qurʾānic text renders in KFGQPC HAFS Uthmanic Script alone. The fallback faces were removed from its stack, the face uses `font-display: block` so no substitute font appears while it loads, and its rules now outrank the general Arabic rule, including for elements nested inside a verse.
- The KFGQPC file is preloaded on every page that shows a verse.

### Added
- "Qurʾān verse" block style for paragraphs (class `is-style-lq-quran`), which applies the KFGQPC face, right-to-left direction and verse sizing.

## 1.1.0 (2026-09-25)

### Added
- Three pages from the keyword research, in both languages: Sūrat al-Qadr (Surah 97), The Last Ten Nights of Ramadan, and Laylat al-Qadr Duʿāʾ.
- Title tag and meta description for every guide page, stored per page and prefilled by setup from the keyword plan (docs/seo.md).
- "Search appearance" box on the page editor with live character counts (65 for titles, 130 for descriptions).
- Search tab in Appearance > Lailatulqadar listing every title tag and description with its length.
- Automatic hand-over to Yoast SEO, Rank Math, SEOPress, All in One SEO or The SEO Framework: when one is active the theme prints no title or description of its own.
- Setup renames untouched drafts (no content) to the current titles and slugs, so renamed pages carry forward.

### Changed
- Page registry reorganised around search intent. Worship becomes "Prayer and Worship on Laylat al-Qadr" (`/laylat-al-qadr-prayer/`), with duʿāʾ moved to its own page; Malay "Ibadat dan Doa" becomes "Amalan Malam Lailatulqadar"; Sources becomes "Hadith and Sources".
- Header menu: What it is, Surah, When, Signs, Prayer, Duʿāʾ. The reading path gains the three new pages.
- English front-page introduction names the Night of Power and the spelling Laylatul Qadr once each.

### Fixed
- Setup no longer adopts a page belonging to the other language when two slugs coincide.

## 1.0.1 (2026-09-25)

### Changed
- Qurʾānic text now uses KFGQPC HAFS Uthmanic Script (King Fahd Glorious Quran Printing Complex), shipped as the original unmodified TTF under its licence. Applied to the front-page verse and to any element with the class `lq-quran`.
- Noto Naskh Arabic replaces Amiri for all other Arabic: hadith, duʿāʾ and Arabic terms in running text. Subset to Arabic ranges (83 KB) and loaded only on pages containing Arabic.
- The settings screen preview uses the KFGQPC Hafs face.

### Fixed
- Sūrat al-Qadr 97:3 on both front pages now uses the verified KFGQPC Hafs text with its verse marker. The previous text omitted the shaddah on مِّن.

### Removed
- Amiri font files.

## 1.0.0 (2026-09-25)

### Added
- Child theme of Twenty Twenty-Five for lailatulqadar.guide.
- Night colour scheme (default), dawn scheme, and an option to follow the visitor's device; configurable accent colour for each scheme.
- Self-hosted Literata and Amiri typefaces, subset to the characters the site uses.
- Guide menu block with three sets: header links, numbered reading path and footer. Only published pages appear.
- Language switcher block for Polylang.
- Setup routine that creates eleven guide pages in English and Malay, links the translations, sets the static front page and gives the site title its Malay form. Runs on the first admin visit after activation and on demand from the Tools tab.
- Tabbed settings screen at Appearance > Lailatulqadar: General, Languages, Colours, Tools.
- English and Malay front-page patterns, header and footer parts, and front page, page and 404 templates.
- Malay (`ms_MY`) translation of front-end interface strings.
