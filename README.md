# Lailatulqadar: technical reference

The Twenty Twenty-Five child theme behind lailatulqadar.guide, a fixed guide to Laylat al-Qadr in English and Malay. This file is the entry point for developers, hosts and anyone who builds, tests or deploys the theme.

The site owner's guide is `readme.txt`. The fixed facts and rules are in `SSOT.md`, which wins on any matter of fact.

Releases are in `CHANGELOG.md`, and the update notes and the roadmap in `UPGRADING.md`. The detailed references sit in `docs/`, listed at the end.

## At a glance

| Item | Value |
|---|---|
| Version | 1.21.0 (`style.css`, `LQ_VERSION` in `functions.php`) |
| Parent | Twenty Twenty-Five, which must stay installed (tested with its 1.5) |
| WordPress | 6.7 or later, tested up to 7.1 |
| PHP | 7.4 or later |
| Plugins | None needed. Rank Math is supported and takes over titles, descriptions and the sitemap when active |
| Languages | English at the root, Malay under `/ms/`, built in (no translation plugin) |
| Web server | Apache gets its rules written by the theme. Nginx needs the rules below in the server block |
| Multisite | Error pages are written by the main site only and stay on a theme switch. The `.htaccess` sections are written by whichever site runs the theme (see the roadmap) |
| Licence | GNU GPL version 3 or later; the full text is in `LICENSE` at the theme root |
| Build step | None for the theme. Python scripts in `bin/` check content and fetch images |

## Install

1. Install Twenty Twenty-Five. It must stay installed; it need not be active.
2. Upload the zip at Appearance > Themes > Add New, or copy the `lailatulqadar` folder into `wp-content/themes/` over FTP with hidden files shown.
3. Activate it and open any admin screen as an administrator. Setup runs once and creates the pages in both languages.
4. Read the report on Appearance, Theme Options, Tools.

The folder is `lailatulqadar`, all lower case.

## Build, test and release

`git archive` leaves out `.github`, `bin`, `tests`, `.claude` and the git files, as `.gitattributes` marks them:

```bash
git archive --format=zip --prefix=lailatulqadar/ -o lailatulqadar-$(sed -n 's/^Version:[[:space:]]*//p' style.css).zip HEAD
```

The checks, run from the theme folder. Each test file runs on its own without WordPress, through `tests/stubs/wordpress.php` where it needs one:

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
php tests/contents-test.php
php tests/error-pages-test.php
php tests/hardening-test.php
php tests/htaccess-files-test.php
php tests/no-blog-test.php
php tests/path-chapters-test.php
php tests/schema-graph-test.php
php tests/search-permalinks-test.php
php tests/security-detector-test.php
php tests/unlist-test.php
php bin/build-error-pages.php --check
python3 bin/check-content.py
```

GitHub Actions runs `bin/check-content.py` on pull requests that touch `content/` or `inc/data/pages.json` (`.github/workflows/content.yml`). The images workflow (`.github/workflows/images.yml`) is started by hand and runs `bin/fetch-commons-images.py`, with `PIXABAY_API_KEY` and `PEXELS_API_KEY` from the repository secrets.

After an article changes, run `php bin/build-seed-history.php > inc/data/seed-history.json.new` and move the file into place, so setup still knows every shipped version. After the error page text changes, run `php bin/build-error-pages.php`.

To release: bump the version in `style.css`, `functions.php` (`LQ_VERSION`), `readme.txt` (Stable tag and changelog), `SSOT.md` and the At a glance table of this file, add the entry to `CHANGELOG.md`, add a note to `UPGRADING.md` if the owner must act, run the checks and build the zip.

## Architecture

### Boot sequence

1. `functions.php` defines `LQ_VERSION`, `LQ_DIR` and `LQ_URI`.
2. It lists every file in `inc/` it loads, checks each one is readable (`inc/settings.php` too, which loads in the admin only), and then loads them inside a `try`. A missing or half-written file sends visitors to `lq_incomplete_theme()` (the upload guard, below).
3. `after_setup_theme` loads the text domain and declares `title-tag`, `automatic-feed-links` and the editor styles. XML-RPC is shut at priority 1 there (`lq_sec_xmlrpc()`).
4. `init` at priority 0 answers scanner probes with a short 404 (`lq_short_circuit_probe()`), before the query runs.
5. The first admin visit by an administrator after activation or a version change runs setup (`inc/setup.php`): pages, translations, the front page, shipped articles and the SEO prefill. It never runs on a front-end request.
6. `admin_init` writes or repairs the error pages and the `.htaccess` sections. `switch_theme` removes them.

### File map

`SSOT.md` has the full map, file by file. In outline:

| Path | Contents |
|---|---|
| `style.css`, `functions.php`, `theme.json` | Header, boot, palette, fonts and layout |
| `templates/` | front-page, page, page-home, page-no-title, index, search, 404 |
| `parts/` | header and footer |
| `patterns/` | The English and Malay front pages, and the empty-results message |
| `blocks/` | breadcrumbs, contents, guide-menu, image-credits, in-brief, language-switcher, logo-mark, night-window, odd-nights, page-details, path-nav, photo, scheme-toggle, social-icons, sources |
| `inc/` | One file per feature: languages, setup, SEO and schema, the Ramadan calendar, the night window, contextual links, images, the placed contents list, security, hardening, error pages, the settings screen and the built-in plugin features |
| `inc/data/` | The page registry, the shared phrase map, the reading-path chapters, image credits, seed and title histories |
| `content/<lang>/<key>.json` | The shipped articles, read with `json_decode()` and never executed |
| `css/`, `js/`, `fonts/`, `images/` | Styles, scripts (the adhan prayer-time library in `js/vendor/`), self-hosted fonts, logo and photographs |
| `errors/` | Static 403, 500, 502, 503 and 504 pages for a site at the domain root |
| `languages/` | POT file and the `ms_MY` translation |
| `bin/`, `tests/` | Build and check scripts, and the tests. Not shipped |
| `docs/` | The detailed references |

### Front page reading path

`patterns/front-en.php` and `patterns/front-ms.php` hold a wide "Read the guide" group with `lailatulqadar/guide-menu` (`set` path, `chapters` on). `blocks/guide-menu/render.php` draws one `section.lq-chapter` per chapter of `inc/data/path-chapters.json` and a card (`li.lq-gcard`) per page, from `lq_page_summary()`, `lq_page_icon()` and `lq_reading_minutes_cached()` in `inc/helpers.php`; the styles are the `lq-chapter*` and `lq-gcard*` rules in `css/front.css`. Without chapters, or when the path and the chapters do not line up, the block prints the plain numbered list.

### Guide page parts

`templates/page.html` runs: breadcrumbs, title, `lailatulqadar/page-details`, `lailatulqadar/in-brief`, the text (with the contents list after its In brief box), `lailatulqadar/sources`, `lailatulqadar/path-nav`. Everything lives in `inc/page-parts.php`, with `css/page-parts.css`. A guide page is a page in the `path` set of `inc/data/pages.json` (`lailatulqadar_is_guide_page()`); the language homes, About, Contact and the Sitemap take none of the parts.

- Strings come from `lailatulqadar_t()`, English or Malay by the page's language. The author's name stays in the structured data only.
- Dates: `lailatulqadar_date_label()` adds the Hijri date from `IntlDateFormatter` with the `islamic-umalqura` calendar. Without intl it returns '' and the date is left out.
- Contents: `lailatulqadar_heading_anchor()` (on `render_block_core/heading`) gives H2s an id; `lailatulqadar_contents_filter()` (on `the_content` at 30, after the contextual links at 25) inserts the list.
- A list placed by hand (`[auto_toc]` or `lailatulqadar/contents`, see below) takes the place of the contents list.
- Sources: `lailatulqadar_page_sources()` returns the quotations (`lq_sc_quotations()`) and the footnotes that name a source (`lq_sc_citation()`); `lq_schema_graph()` builds the `citation` nodes from the same list.
- Tables: `lailatulqadar_table_parts()` (on `render_block_core/table`) adds `lq-table`, `role="region"`, `tabindex="0"` and the caption as the label.
- Post meta: `_lq_reviewed` (Y-m-d) and `_lq_brief` (points, one a line), from the Guide page box.

### Placed contents list

`inc/contents.php`, from "WordPress Table-of-Contents Shortcode Done Properly" by Ikram Hawramani (MIT; the notice stays in the file header). It works on any page or post, guide page or not.

- `[auto_toc tags="h2,h3" title="..."]` and the `lailatulqadar/contents` block (`blocks/contents/`, attributes `tags`, `title`, `showTitle`) both print a marker, an HTML comment holding the levels and title as base64 JSON (`lailatulqadar_toc_mark()`).
- `lailatulqadar_toc_filter()` on `the_content` at 29 drops the paragraph wpautop puts round a marker, gives an id to each heading of the requested levels that has none (`lailatulqadar_toc_add_ids()`, which never reuses an id already in the text), and replaces each marker with `lailatulqadar_toc_html()`, or with an empty `<!--lq-contents--placed-->` when the text has no heading of its levels. Ids are read quoted or not (`lailatulqadar_toc_attr_id()`). In feeds and excerpts the markers are removed.
- The list is `<nav class="lq-contents lq-contents--placed">` with nested `<ol>`; `lailatulqadar_toc_list()` closes every list. `lailatulqadar_contents_filter()` (30) skips a text that already holds one.
- `lailatulqadar_toc_assets()` loads `css/page-parts.css` on a singular view whose text places a list; the filter also enqueues it when it renders one, which covers a list inside a synced pattern.
- The shortcode is registered on `init` at 20, after plugins load, so the theme's version wins over the plugin's.
- The block works inside a page's or post's text. In a template, outside the text, it prints nothing visible.
- `lailatulqadar_footnote_ref_ids()` (on `the_content` at 12) gives the second and later citations of one footnote the ids `<note>-link-2`, `-link-3`, since core repeats `<note>-link` at each.

## Settings

Appearance, Theme Options (`inc/settings.php`), capability `manage_options`. Everything is stored in one option, `lailatulqadar_settings` (`LQ_OPTION`), and each tab saves only its own fields. `SSOT.md` lists every key.

Tabs, in order: General, Languages, Colours, Ramadan Dates, Login, Search, Contextual links, Images, Reading and sharing, Security, Tools. General, Search, Security and Tools open with a full reload, since they run reports; the rest switch without one. `docs/settings.md` describes every field.

The Security tab is drawn by `inc/security.php`, with `js/security.js` and `css/security.css` loaded only while it is open. The Tools tab also holds the error pages (`inc/error-pages.php`).

## Hooks, filters and constants

| Filter | Effect |
|---|---|
| `lq_schema_graph` | The JSON-LD graph before it prints |
| `lq_contextual_links_map` | The phrase map |
| `lq_contextual_links_enabled` | Contextual links on a given page |
| `lq_night_window_config` | The night window's cities and strings |
| `lq_ramadan_today` | The date the Ramadan calendar counts from |
| `lq_path_show_upcoming` | Whether the reading path lists unpublished pages as "Coming soon" (default true) |
| `lq_slug_stopwords` | The words left out of new slugs (a, an, the) |
| `lq_sec_rest_allow` | REST route prefixes exempt from the logged-in write lock |
| `lq_allow_parent_variations` | Show the parent's style variations |
| `lailatulqadar_unlist_enabled` | The built-in unlisting |
| `lailatulqadar_search_reserved_bases` | Words the search base may not take |
| `lailatulqadar_is_guide_page` | Whether a page takes the guide page parts (default: the pages of the reading path) |
| `lailatulqadar_contents_min` | Fewest H2 headings for a contents list (default 3) |

Shortcodes: `[lq_ramadan]` (the year or dates of the next Ramadan), `[lq_lanah]`, `[lailatulqadar_social_icons]` and `[auto_toc]` (a contents list, `tags` and `title` attributes).

| Constant | Set in | Effect |
|---|---|---|
| `LQ_VERSION`, `LQ_DIR`, `LQ_URI` | `functions.php` | Version and theme paths |
| `LQ_SECURITY_LOCK` | `wp-config.php` | False turns off the three remote-publishing locks (XML-RPC, application passwords, REST writes) without changing the settings |
| `LQ_BLOCK_DIRECT_FILES` | `wp-config.php` | False leaves out the "block direct file access" section |
| `LQ_SPAM_THRESHOLD` | `wp-config.php` | The score at which the spam gate holds a post |
| `LQ_TITLE_LIMIT`, `LQ_TITLE_SITE`, `LQ_TITLE_SEP` | `inc/seo.php` | 59, "Lailatulqadar" and " \| ": the whole title tag stays under 60 characters |
| `LQ_SEC_FILES_MARKER` | `inc/security.php` | The marker of the direct-access section |
| `LAILATULQADAR_META_REVIEWED`, `LAILATULQADAR_META_BRIEF` | `inc/page-parts.php` | `_lq_reviewed` and `_lq_brief`, the Guide page box's meta keys |
| `LAILATULQADAR_TOC_MARK` | `inc/contents.php` | The prefix of the placed contents list's marker comment |

The theme defines no `*_KEEP_*` or `*_ALLOW_*` constant, since it has no production clean-up of development files and no switch for core files (see the roadmap in `UPGRADING.md`).

## Security

### The upload guard

The owner uploads over FTP, one file at a time. While a file is missing or half written, visitors get a 503 "Back shortly" page in English and Malay, with `Retry-After: 300`, `no-store` and `noindex` (`lq_incomplete_theme()`). The error log gets one line per problem every ten minutes.

The login screen (known by its path), AJAX, cron and WP-CLI go on. The admin sees the page only when a file broke part-way through loading.

`functions.php` holds the guard and is parsed before any of it runs, so it cannot guard itself: a `functions.php` cut off part-way still gives a PHP error. Uploading to a temporary folder and renaming it into place avoids that.

### Accounts, probes and XML-RPC

- Visitors who are not logged in get no `/wp/v2/users` routes, and `?author=N` answers them with a 404. Any logged-in user, a subscriber included, keeps both (`is_user_logged_in()`).
- Scanner probes (PHP files that are not WordPress's entry points, and dot files such as `.env`) get a short 404 on `init`. A real file behind the path is never cut short. `tests/hardening-test.php` covers it.
- The Security tab turns off XML-RPC, application passwords and REST writes from outside a logged-in browser, all on by default. It also holds gambling spam and anything published from outside the editor as Pending, and answers 410 Gone on the address of spam it trashed.
- Failed logins are counted by account name for a rolling day, and each administrator login is recorded with its time and IP address.

### The theme folder's own rules

`.htaccess` in the theme folder is read before WordPress runs. It refuses:

- every dot entry (`.git`, `.claude`, `.env` and the like), with `.well-known` left open;
- every PHP file in the theme;
- the `tests`, `bin` and `docs` folders;
- every Markdown file by name.

`docs/settings.md` and `readme.txt` stay open, since the Theme Options footer links to them. `tests/`, `bin/` and `docs/` carry a deny-all `.htaccess` of their own (`docs/settings.md` excepted), and their PHP scripts exit unless run from the command line.

### The sections in the site's .htaccess

On Apache with a writable `.htaccess`, `lq_sec_sync_htaccess()` keeps two marked sections. It runs on an admin visit at most once an hour, at once when the settings are saved, and on activation. `lq_sec_remove_htaccess()` takes both out on a theme switch. For a site at the domain root they read:

```apache
# BEGIN Block xmlrpc.php
<Files xmlrpc.php>
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order Deny,Allow
Deny from all
</IfModule>
</Files>
# END Block xmlrpc.php
# BEGIN Lailatul Qadar: block direct file access
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule (^|/)\.(git|claude) - [F]
RewriteRule ^wp-includes/(?!(js/tinymce/wp-tinymce|ms-files)\.php$).+\.php(/|$) - [F]
RewriteRule ^wp-admin/includes/.+\.php(/|$) - [F]
RewriteRule ^(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) - [F]
RewriteRule ^wp-content/themes/.+\.php(/|$) - [F]
RewriteRule ^wp-content/themes/lailatulqadar/(tests|bin)(/|$) - [F]
RewriteRule ^wp-content/themes/lailatulqadar/docs(/(?!settings\.md$)|$) - [F]
</IfModule>
# END Lailatul Qadar: block direct file access
```

"Block xmlrpc.php" is there only while XML-RPC is off. The marker name is a common one, so a switch between themes that use it never writes the section twice.

The rules carry the site's own folder prefixes. With WordPress in its own folder below the `.htaccess`, a rule for `wp-config.php` one folder up is added. Define `LQ_BLOCK_DIRECT_FILES` as false to leave the second section out. `tests/htaccess-files-test.php` checks the lines and the theme's own files.

### Nginx

Nginx reads no `.htaccess`. Put these in the server block above every other regex `location`, the `location ~ \.php$` block that hands PHP to PHP-FPM and any static-file block among them: Nginx takes the first regex location that matches, so a rule further down never runs.

Where the host keeps such blocks in an include, merge the denials into them instead. Check for a matching `location ^~` or exact `location =` block too (for `/wp-content/` or the themes folder, say): Nginx skips every regex location after one of those matches, so put the denials inside it.

```nginx
location ~ /\.(git|claude) { return 403; }
location ~ ^/wp-content/themes/lailatulqadar[^/]*/(.+/)?\.(?!well-known(/|$)) { return 403; }
location ~ ^/wp-includes/(?!js/tinymce/wp-tinymce\.php$|ms-files\.php$).+\.php(/|$) { return 403; }
location ~ ^/wp-admin/includes/.+\.php(/|$) { return 403; }
location ~ ^/(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) { return 403; }
location ~ ^/wp-content/themes/.+\.php(/|$) { return 403; }
location ~ ^/wp-content/themes/lailatulqadar[^/]*/(tests|bin)(/|$) { return 403; }
location ~ ^/wp-content/themes/lailatulqadar[^/]*/docs(/(?!settings\.md$)|$) { return 403; }
location ~* ^/wp-content/themes/lailatulqadar[^/]*/(?!docs/settings\.md$).+\.md$ { return 403; }
location = /xmlrpc.php { return 403; }
```

The first line refuses `.git` and `.claude` anywhere on the site, as the section in the site's `.htaccess` does. The second refuses every other dot file and folder inside the theme folder only, with `.well-known` left open, as the theme folder's `.htaccess` does. Neither touches a dot path another application on the host may serve.

The `(/|$)` endings also catch a PHP file asked for with path info after it, as the Apache rules do.

The `lailatulqadar[^/]*` in the theme rules also covers the `lailatulqadar-new` and `lailatulqadar-old` folders of an FTP update, which Nginx would otherwise serve while both sit on the server.

Leave out the last line while XML-RPC is switched on. For WordPress in a subfolder, put its path in front of every rule but the first (the `.git` and `.claude` rule stays global), as in `^/blog/wp-includes/`, and the theme-folder rules with it (`^/blog/wp-content/themes/lailatulqadar/`). WordPress also reads `wp-config.php` from the folder above its own, so refuse both copies: `location ~ ^/blog/wp-config\.php(/|$) { return 403; }` and `location ~ ^/wp-config\.php(/|$) { return 403; }`.

### The installer

The theme cannot protect a site whose WordPress core sits on the server without `wp-config.php`, since the installer then answers anyone. Upload `wp-config.php` first, or upload core to a folder the web server does not serve and rename it into place.

## Error pages

WordPress reads three files from `wp-content` when it cannot build a page:

| File | Shown when | Status sent |
|---|---|---|
| `maintenance.php` | WordPress is updating | 503, `Retry-After: 600` |
| `php-error.php` | a fatal PHP error | 500 |
| `db-error.php` | the database cannot be reached | 503, `Retry-After: 600` |

The theme writes them on activation, on an administrator's admin page when one is missing or outdated, and at once with Install error pages on the Tools tab.

A file marked `Lailatulqadar error page drop-in`, or one another theme marked "safe to delete", is replaced. Any other file is left alone. On a theme switch the theme deletes its own files.

The theme also writes static 403, 500, 502, 503 and 504 pages into `wp-content/lailatulqadar-errors/`, with the site's name and real home address. Copy the `ErrorDocument` lines from the Tools tab. At the domain root they read:

```apache
ErrorDocument 403 /wp-content/lailatulqadar-errors/403.html
ErrorDocument 500 /wp-content/lailatulqadar-errors/500.html
ErrorDocument 502 /wp-content/lailatulqadar-errors/502.html
ErrorDocument 503 /wp-content/lailatulqadar-errors/503.html
ErrorDocument 504 /wp-content/lailatulqadar-errors/504.html
```

On Nginx: `error_page 403 /wp-content/lailatulqadar-errors/403.html;` and so on for each code. Where `wp-content` sits below a subfolder, use its real address, as in `/blog/wp-content/lailatulqadar-errors/403.html`; the tab's lines show it.

Every page carries the mark as its tab icon, as a `data:` address built from the inline mark, so it needs no path.

`errors/` keeps the copies for a site at the domain root, rebuilt with `php bin/build-error-pages.php`. On a network only the main site writes the files, and they stay on a theme switch, since every site shares `wp-content`. `docs/settings.md` has the details.

## Browser icons

`lq_default_icons()` (`inc/assets.php`) prints `images/favicon.svg`, `favicon-32.png`, `apple-touch-icon.png`, `site.webmanifest` and `theme-color` #141b2e on `wp_head`, `admin_head` and, through `lq_login_icons()`, `login_head`, while `has_site_icon()` is false. With a Site Icon, WordPress prints its own tags.

`/favicon.ico` reaches WordPress when no such file sits in the site root. On a site in a subfolder, such as `example.com/blog/`, browsers still ask for `example.com/favicon.ico`, which WordPress never sees: put a copy of `images/favicon.ico` at the domain root there. The head tags cover the pages either way. `lq_serve_favicon()` answers it on `template_redirect` (priority 0, so HEAD is covered) and `do_faviconico` with `images/favicon.ico`, `Cache-Control: public, max-age=86400` and an ETag, with a 304 on a match. With a Site Icon, WordPress redirects to it. The manifest is a static file with relative icon paths. The theme `.htaccess` refuses dot files, PHP, Markdown, `tests`, `bin` and `docs` only, so the icons are served (`tests/htaccess-files-test.php`).

## SEO and structured data

- Title tags read `<page> | Lailatulqadar` and stay under 60 characters (`lq_compose_title()`), with Rank Math too.
- Meta descriptions are stored on each page.
- Every page carries one JSON-LD graph, merged into Rank Math's graph when it is active.
- A guide page's Article cites what its Evidence and sources block lists, and a review date becomes the WebPage's `lastReviewed`.
- There is an XML sitemap trimmed to pages, an image sitemap at `/image-sitemap.xml` and hreflang links between the languages.

`docs/seo.md`, `docs/google-guidelines.md` and `docs/rank-math.md` have the details.

## Performance

- Fonts are self-hosted WOFF2 subsets. Arslan Wessam downloads only on pages with Arabic (`unicode-range`), and the Qur'an face only where a verse is shown.
- Images are WebP at 640 and 1200 pixels, each under 200 KB, with `srcset`. The lead image loads eagerly with `fetchpriority="high"`, and later ones lazily.
- Scanner probes stop on `init`, where each used to render the 41 KB 404 page.
- The settings screen's CSS and JS load on that screen only.
- The night window works out prayer times in the browser, so it costs the server nothing.

## Detailed references

| File | Covers |
|---|---|
| `docs/settings.md` | Every Theme Options field, and the error pages |
| `docs/blocks.md` | The blocks, their attributes and the front page's arc |
| `docs/content.md` | The shipped articles and how setup writes them |
| `docs/languages.md` | English and Malay without a plugin |
| `docs/night-window.md` | The Night Planner's night window |
| `docs/seo.md` | Keyword targets per page |
| `docs/google-guidelines.md` | Google's guidelines and the structured data types in use |
| `docs/rank-math.md` | What the theme hands to Rank Math |
| `docs/parent-theme.md` | Compatibility with Twenty Twenty-Five |
