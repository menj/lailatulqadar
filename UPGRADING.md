# Upgrading

You can skip versions: upload the newest zip over any 1.9.x and open any admin screen as an administrator. Setup knows every version of each article the theme has shipped, so in one run it brings every unedited page up to date and lists pages you have edited under "Edited, not updated" on the Tools tab. The entries below say what each version changes on the site.

## Updating an existing site

1. Turn on hidden files in the FTP program (in FileZilla: Server > Force showing hidden files). The theme carries `.htaccess` files in its own folder and in `tests/`, `bin/` and `docs/`.
2. Upload the new `lailatulqadar` folder into `wp-content/themes/` as `lailatulqadar-new`, its `.htaccess` files first (the theme folder's, then those in `tests/`, `bin/` and `docs/` when the copy has them), so on Apache none of its private files can be read while the rest uploads. Nginx ignores `.htaccess`: on an Nginx host, check first that the server rules in `README.md` are in place, since they cover `lailatulqadar-new` too. The folder stays on the same filesystem as the live one, so the rename is a single step.
3. Rename the live folder to `lailatulqadar-old` and at once rename `lailatulqadar-new` to `lailatulqadar`, with Appearance > Themes closed, at a quiet hour. A page asked for between the two renames finds no theme, and that screen switches the site to another theme if it finds the folder missing. If WordPress did switch to another theme, activate the theme again under Appearance > Themes. Delete the old one once the site looks right.
4. Open any admin screen as an administrator. Setup runs, the error pages are written and the `.htaccess` sections are checked.
5. Read the note below for every version you pass, and the setup report on the Tools tab.

An upload straight into the live folder is riskier. While one of the PHP files `functions.php` loads is missing, visitors see a 503 "Back shortly" page and the login screen keeps working. Nothing checks `functions.php` itself, the templates, the styles, the scripts or `content/`, so the temporary folder and the rename stay the safe way.

Never upload WordPress core to a live folder without `wp-config.php` in place. The installer answers anyone in that state.

## To 1.22.0

**Check PHP first.** The theme now needs PHP 8.4 or later. Find the version under Tools > Site Health > Info > Server, and have the host raise it before you update. WordPress refuses to install a theme whose PHP requirement the server does not meet; on an older PHP the bundled table of contents does not load and the pages show none, with a notice for administrators.

Then update as usual. Nothing else to do: guide pages show the table of contents in the same place. If the WP TOC plugin is active as well, the plugin's copy runs and the theme's waits; either works, and the plugin can be deactivated.

## To 1.21.0

Nothing to do. The front page in each language shows the reading path as cards. A home page whose text an editor has replaced keeps that text; to take the cards there, set its "Read the guide" group to wide width and keep the Guide menu block in it with "Split into chapters" on.

## To 1.20.0

Nothing to do. On the first admin visit, setup gives the new text to What Is Laylat al-Qadr, Sūrat al-Qadr, the Duʿāʾ, the Islamic Dilemma and the FAQ in each language, unless you have edited them. An edited page is listed on the Tools tab as "Edited, not updated", with a button to take the new text.

## To 1.19.1

Nothing to do. On the first admin visit, setup gives the Islamic Dilemma page in each language the corrected text with its notes, unless you have edited it. An edited page is listed on the Tools tab as "Edited, not updated"; add a Footnotes block at the end of its text to show its notes.

## To 1.19.0

Nothing to do. If the "WordPress Table-of-Contents Shortcode Done Properly" plugin is active, or its code sits in a child theme's `functions.php`, remove it: the theme now runs `[auto_toc]` itself, and the plugin would only add its own styles to every page. Text that uses the shortcode keeps working.

## To 1.18.13

Replace the theme files. Nothing else changes. On a site in a subfolder, put a copy of `images/favicon.ico` in the domain root (or, with a Site Icon set, that icon as `favicon.ico`), since browsers ask for `/favicon.ico` there.

## To 1.18.12

Upload the new zip. The new files are in `images/`: `favicon.svg`, `favicon.ico`, `icon-192.png`, `icon-512.png`, `icon-maskable-512.png` and `site.webmanifest`. Open one admin page so the error pages are written again with the icon. If a `favicon.ico` file sits in the site root, the web server sends it and the theme never answers; delete it to use the theme's.

## To 1.18.11

Nothing to do. Documentation only: the FTP steps above now stage the new folder inside `wp-content/themes/`.

## To 1.18.10

Nothing to do. Guide pages gain their dates, reading time, contents, sources and the previous and next row on the next page load.

- The dates need PHP's intl extension for their Hijri dates; most hosts have it. Without it the dates stay hidden, and the rest shows.
- To show a review date, fill in "Last reviewed against its sources" in the Guide page box of each page.
- The theme is now under the GNU GPL version 3 or later. The `LICENSE` file goes up with the folder.

## To 1.18.9

Nothing to do. The release adds `README.md` and changes no page and no setting.

As after any version change, setup runs once on the first admin screen and finds no article to change, and the error pages are written again with the new version.

## To 1.18.8

Upload with hidden files shown, so the new `.htaccess` files in the theme folder and in `tests/`, `bin/` and `docs/` go up.

On Apache, the first admin screen writes the "Lailatul Qadar: block direct file access" section beside "Block xmlrpc.php". To leave it out, define `LQ_BLOCK_DIRECT_FILES` as false in `wp-config.php` first.

On Nginx nothing is written: give the host the rules in `README.md`.

## To 1.18.7

Upload the zip. Nothing to run. Visitors can no longer list user accounts, and `?author=N` answers them with a 404.

## To 1.18.6

Upload the zip. A footer part saved in the Site Editor before this version keeps working: a switcher inside it takes the footer name anyway.

## To 1.18.5

Upload the zip. Nothing to run: the front pages show the 4 chapters of the reading path at once.

## To 1.18.4

Upload the zip and open any admin screen as an administrator. The theme writes its static error pages into `wp-content/lailatulqadar-errors/`. If `.htaccess` has `ErrorDocument` lines that point at `/wp-content/themes/lailatulqadar/errors/`, replace them with the lines from the Tools tab. The old lines still work on a site at the domain root.

## To 1.18.3

Upload the zip and open any admin screen as an administrator: the error pages are written then, with no button press. A copy another theme left in wp-content is replaced; a file without a theme's mark is left alone. Add the new `ErrorDocument 504` line from the Tools tab to `.htaccess` if you use the static pages.

## To 1.18.1

Upload the zip. If you edited the 404 template in the Site Editor, the saved copy keeps the old Malay line. Change that line by hand in the editor; resetting the template would also drop your other edits to it.

## To 1.18.0

Upload the zip. Nothing is written to wp-content until you open Theme Options > Tools and press Install error pages. A row that says another file is there belongs to a plugin or the host and is left alone. To use the static pages for server errors, add the `ErrorDocument` lines from the same tab to `.htaccess`.

## To 1.17.0

Upload the zip and open any admin screen as an administrator. Setup runs once and finds no article to change. Rewrite rules are flushed once, so searches open at /search/term/. Fill in the Reading and sharing tab, then deactivate the Reading Progress Bar, Unlist Posts & Pages and Pretty Search Permalinks plugins if they are installed: pages the plugin unlisted stay unlisted, and its search base carries over.

## To 1.16.0

Upload the zip and open any admin screen as an administrator. Setup runs once and finds no article to change. Appearance > Theme Options has a new layout; every setting keeps its value. Clear any page cache so editors get the new settings script and style.

## To 1.15.0

Upload the zip and open any admin screen as an administrator. Setup clears the posts page and any post template a page carries, once. Post addresses and feeds then send visitors to the home page of their language. Resubmit the sitemap in Google Search Console afterwards.

## From 1.9.18 to 1.11.0

Upload the new zip over 1.9.18 and open any admin screen as an administrator. Setup refreshes the Night Planner and The Last Ten Nights (English) unless edited. The Security tab (Appearance > Theme Options > Security) is on by default: XML-RPC, application passwords and REST writes from outside the browser stop at once. Writing in the editor, Rank Math and everything visitors do are unchanged.

After the October 2026 attack, the owner checklist:

1. Change the password of every administrator to a long random one used nowhere else, then Log Out Everywhere Else (Users > Profile), or press "Log out all sessions" on the Security tab.
2. Create a new administrator whose name is not the site name, log in as it, and delete the old account, attributing its content to the new one.
3. Press "Revoke all application passwords".
4. Check Plugins for anything not installed by you (auto-posting, AI writers, remote management) and remove it. Check Users for unknown accounts.
5. Run the spam scan, select the results, move them to the trash, then delete the empty spam categories.
6. Purge any page cache, then resubmit the sitemap in Google Search Console.
7. Nothing to do at the server: on Apache the theme writes a "Block xmlrpc.php" section into .htaccess itself, repairs it hourly from the admin, and removes it when XML-RPC is switched back on or the site moves to a theme outside the sister themes. Jetpack needs XML-RPC; if Jetpack is ever used, switch "Turn off XML-RPC" off.

If a lock breaks a tool you rely on, `define( 'LQ_SECURITY_LOCK', false );` in wp-config.php turns the three locks off without changing the settings.

## From 1.9.17 to 1.9.18

Upload the new zip over 1.9.17 and open any admin screen as an administrator. Setup refreshes Start Here, FAQ and The Islamic Dilemma in both languages, which gain images, and sets The Islamic Dilemma's lead image as its featured image. It also brings every title tag the theme wrote into the form "title | Lailatulqadar", under 60 characters; a title tag you have written yourself is kept, so check the Search tab for any that run over. With Rank Math active, setup updates Rank Math's title field in the same way, again keeping your own. To let the images workflow fetch Pixabay or Pexels photos added later, add the repository secrets `PIXABAY_API_KEY` and `PEXELS_API_KEY` (Settings > Secrets and variables > Actions), then start the workflow by hand on the branch that adds the photo (Actions > Images > Run workflow): for safety the keys never reach a run triggered by a pull request.

## From 1.9.16 to 1.9.17

Upload the new zip over 1.9.16 and open any admin screen as an administrator. Setup runs once and finds nothing to change: the page registry now comes from `inc/data/pages.json`, with the same titles, addresses and descriptions as before. If that file is missing or damaged, setup does not run and the Tools tab shows a warning instead of "Setup finished".

## From 1.9.15 to 1.9.16

Upload the new zip over 1.9.15. No page changes and no setup work: existing addresses stay as they are. From now on, a new post or page published without a slug of its own gets an address without "a", "an" or "the".

## From 1.9.14 to 1.9.15

Upload the new zip over 1.9.14 and open any admin screen as an administrator. Setup runs once and gives The Islamic Dilemma and the Qurʾān as Guardian (English) its new wording, unless the page has been edited. `FARUQI.md` is documentation for contributors and changes nothing on the site.

## From 1.9.13 to 1.9.14

Upload the new zip over 1.9.13. Code fixes only: no article changes, so setup finds nothing to update. Clear any page cache, so visitors load the new night window script; editors get the new Search appearance script once their browser reloads the page editor.

## From 1.9.12 to 1.9.13

Upload the new zip over 1.9.12 and open any admin screen as an administrator. Setup refreshes every Malay article, whose Arabic is restored to the exact text of its source, and in English Prayer and Worship, Signs, Start Here and Hadith and Sources. Read the setup report on the Tools tab.

## From 1.9.11 to 1.9.12

Upload the new zip over 1.9.11 and open any admin screen as an administrator. Setup refreshes Start Here, The Last Ten Nights, Duʿāʾ, Signs, When Is and Hadith and Sources in both languages, which gain new sections. Read the setup report on the Tools tab.

## From 1.9.10 to 1.9.11

Upload the new zip over 1.9.10 and open any admin screen as an administrator. Setup refreshes every article that cites shamela.ws in both languages: each Arabic source title now carries a translation. Sūrat al-Qadr, What Is, Signs and Hadith and Sources also gain material from Ibn Kathīr.

## From 1.9.9 to 1.9.10

Upload the new zip over 1.9.9 and open any admin screen as an administrator. Setup refreshes Hadith and Sources in both languages, which gains six hadith rows.

## From 1.9.8 to 1.9.9

Upload the new zip over 1.9.8 and open any admin screen as an administrator. Setup refreshes the articles whose citations or content changed in both languages: footnotes link shamela.ws with titled links, and When Is, Prayer and Worship, Signs, Duʿāʾ and Hadith and Sources gain new sections. Read the setup report on the Tools tab.

## From 1.9.7 to 1.9.8

Upload the new zip over 1.9.7 and open any admin screen as an administrator. Setup fills and publishes every Malay guide page that is still an empty draft, beneath the Malay home at /ms/, so the Malay reading path no longer shows "Akan datang". A Malay page you have written yourself is not replaced; the Tools tab lists it under "Edited, not updated" with a button to replace it. Check the Malay home and the language switcher afterwards.

## From 1.9.6 to 1.9.7

Upload the new zip over 1.9.6 and open any admin screen as an administrator. Pages first seeded by 1.9.0 are now recognised as unedited, so setup gives them the current text and publishes them. If the Tools tab listed The Last Ten Nights, Signs or Duʿāʾ as "Edited, not updated" although nobody had edited them, they now update by themselves.

## From 1.9.5 to 1.9.6

Upload the new zip over 1.9.5. No setup changes. The `[lq_lanah]` shortcode is available for placing by hand; no shipped article uses it. A web licence for the Dubidam Arabic outlines it is drawn from is the site owner's to hold.

## From 1.9.4 to 1.9.5

Upload the new zip over 1.9.4 and open any admin screen as an administrator. Setup creates and publishes The Islamic Dilemma and the Qurʾān as Guardian (English; its Malay page stays a draft until 1.9.8), adds it to the reading path and footer, and refreshes unedited pages whose text changed: Signs, Duʿāʾ, The Last Ten Nights and When Is. The front pages are patterns and change at once.

## From 1.9.3 to 1.9.4

Upload the new zip over 1.9.3 and open any admin screen as an administrator. Setup runs by itself once (it notices the new version): every page whose article ships with the theme is filled and published, pages seeded earlier and not edited take the new text (the old text stays as a revision), and obsolete copies the theme created earlier go to the trash. Pages you have edited are not changed; the Tools tab lists them under "Edited, not updated" with a button to replace each one. About, Contact and Malay pages awaiting translation stay drafts. Read the setup report on the Tools tab.

## From 1.9.2 to 1.9.3

Upload the new zip over 1.9.2. The articles in `content/en/` and `content/ms/` are now `.json` files; an upload over the old folder leaves the `.html` and `.footnotes.json` files behind, which nothing reads, so delete them (or delete the theme folder before uploading). Pages already seeded are not touched.

## From 1.9.1 to 1.9.2

Upload the new zip over 1.9.1. `fonts/kfgqpc/kfgqpc-hafs-uthmanic.ttf` gives way to `kfgqpc-hafs-uthmanic.woff2`; an upload over the old folder leaves the TTF behind, which nothing reads. Clear caches so verse pages preload the new file.

## From 1.9.0 to 1.9.1

Upload the new zip over 1.9.0. Contextual links are on from the start. Review or change them under Appearance > Theme Options > Contextual links. No setup run is needed.

## From 1.8.1 to 1.9.0

1. If Polylang is installed, deactivate and delete it.
2. Upload the new zip over 1.8.1.
3. Press Run setup (Appearance > Theme Options > Tools). It creates the Malay home at /ms/ and every Malay page beneath it, and pairs each page with its English version.
4. On the Tools tab, publish the drafts marked ready. The Malay articles arrive as they are translated.
5. If Rank Math is active, submit the sitemap again in Search Console once the Malay pages are published.

## From 1.8.0 to 1.8.1

1. Upload the new zip over 1.8.0.
2. Open Appearance > Theme Options > Tools. Review each page marked "ready to publish" in the editor, then press "Publish written drafts".
3. Press "Return empty published pages to draft" for any page published without text (for example Start Here, until it is written).
4. Move WordPress's "Sample Page" to the trash if the Tools tab lists it.

## From 1.7.9 to 1.8.0

Upload the new zip over 1.7.9. Keep Twenty Twenty-Five installed and updated; 1.5 is the tested version. If a style variation from the parent was applied in the Site Editor before this release, reset it under Styles > Browse styles or with "Reset" in the Styles menu.

## From 1.7.8 to 1.7.9

Upload the new zip over 1.7.8. No other steps.

## From 1.7.7 to 1.7.8

Upload the new zip over 1.7.7. If a Site Icon is set in Settings > General, replace it with images/lailatulqadar-mark-512.png.

## From 1.7.6 to 1.7.7

Upload the new zip over 1.7.6. The new templates apply at once, unless a template of the same name was customised in the Site Editor, in which case reset it there.

## From 1.7.5 to 1.7.6

Upload the new zip over 1.7.5. The lamp and the typeset calligraphy appear at once. To use a calligraphy image, upload a licensed transparent PNG and choose it under Appearance > Theme Options > General > Logo calligraphy. If a Site Icon is set in Settings > General, replace it with images/lailatulqadar-mark-512.png or your own lamp artwork, since the Site Icon takes precedence over the theme's favicon.

## From 1.7.4 to 1.7.5

Upload the new zip over 1.7.4. The login screen changes at once. Optionally set the tagline and note on the Login tab. Deactivate any separate login-styling plugin (for example Login Logo), since the theme now supplies the logo and styling.

## From 1.7.3 to 1.7.4

1. Upload the new zip over 1.7.3, activate Rank Math, and complete or skip its setup wizard (the plugin produces no output until one of those is done).
2. Press Run setup: it fills or converts the Rank Math title and description fields as described in docs/rank-math.md.
3. Apply the recommended Rank Math settings in docs/rank-math.md, in particular breadcrumbs on and Article schema for the guide pages only.

## From 1.7.2 to 1.7.3

Upload the new zip over 1.7.2. At launch, paste the Search Console verification code on the Search tab, verify, then submit /wp-sitemap.xml. The Last Ten Nights table change reaches the page only if it is still an unedited seeded draft (clear it and press Run setup); otherwise make the two edits by hand.

## From 1.7.1 to 1.7.2

Upload the new zip over 1.7.1. The mark appears beside the site title. As with the light and dark switch, a header customised in the Site Editor needs the "Logo mark" block added by hand. A Site Icon, if you set one, replaces the default favicon and logo.

## From 1.7.0 to 1.7.1

Upload the new zip over 1.7.0. The switch appears in the header automatically. If you have customised the header in the Site Editor, your saved header replaces the theme's file: add the "Light and dark switch" block to it by hand, or reset the header template part.

## From 1.6.1 to 1.7.0

1. Upload the new zip over 1.6.1 and press Run setup (refreshes the planner title and description).
2. Set a Site Icon of at least 512 × 512 pixels in Settings > General; it becomes the Organization logo.
3. Optionally set the author name and profile URL in Appearance > Theme Options > General.
4. After launch, run each template through Google's Rich Results Test and submit /wp-sitemap.xml in Search Console.

## From 1.6.0 to 1.6.1

Upload the new zip over 1.6.0. The date tables, shortcodes and night window gain their Hijri equivalents at once. For the updated wording of the two dated articles, clear them (if still unedited drafts) and press Run setup.

## From 1.5.2 to 1.6.0

1. Upload the new zip over 1.5.2 and press Run setup. Title tags and meta descriptions still carrying the fixed 2027 wording from earlier releases switch to the automatic wording.
2. Pages seeded by 1.4.0 keep their fixed 2027 text if you have edited them. For the When Is and Last Ten Nights pages, if you have not edited them yet, clear their content (leave them as drafts) and press Run setup again to receive the automatic versions.
3. Leave the Ramadan Dates override fields empty unless an official announcement differs from the Umm al-Qura date.

## From 1.5.1 to 1.5.2

Upload the new zip over 1.5.1. No setup run is needed.

## From 1.5.0 to 1.5.1

Upload the new zip over 1.5.0. No setup run is needed.

## From 1.4.0 to 1.5.0

1. Upload the new zip over 1.4.0 and press Run setup. The Night Planner page, in both languages, receives its text and the night window if it is still an empty draft.
2. Check Appearance > Theme Options > Ramadan Dates. The defaults are 8 and 9 February 2027 for Ramadan 1448.
3. Publish the planner pages.

## From 1.3.0 to 1.4.0

1. Upload the new zip over 1.3.0.
2. Press Run setup. The five article pages receive their text if they are still empty drafts; pages you have already written in are left alone.
3. Review each draft, then publish. Links between articles point at the final addresses, so they resolve once the linked page is published.

## From 1.2.0 to 1.3.0

Upload the new zip over 1.2.0. No setup run is needed. Mark hadith and supplications with the "Hadith or supplication" paragraph style; Arabic words inside a sentence need `lang="ar"` on their span for correct spacing, although Arabic without it still renders in Arslan Wessam.

## From 1.1.1 to 1.2.0

1. Upload the new zip over 1.1.1.
2. Install Rank Math if you follow the agency checklist, then press Run setup. Setup creates the two sitemap pages, shortens any title over 60 characters, and fills Rank Math's fields.
3. Check the Search tab: every badge should be green against the new 60-character title limit.

## From 1.1.0 to 1.1.1

Upload the new zip over 1.1.0. No setup run is needed. Existing verses marked with `lq-quran` keep working; new ones can use the "Qurʾān verse" paragraph style.

## From 1.0.1 to 1.1.0

1. Upload the new zip over 1.0.1.
2. Open Appearance > Theme Options > Tools and press Run setup. This creates the six new pages as drafts, fills empty title tags and meta descriptions, and renames any guide page that is still an empty draft.
3. Pages that already have content keep their titles and slugs. If the old Worship and Duʿāʾ page already holds content, rename it to "Prayer and Worship on Laylat al-Qadr" with the slug `laylat-al-qadr-prayer` (Malay: "Amalan Malam Lailatulqadar", `amalan-malam-lailatulqadar`) and move any duʿāʾ text to the new Duʿāʾ page. Do the same for Sources ("Hadith and Sources", `hadith-and-sources`).
4. Check the Search tab: every row should show green length badges.

## From 1.0.0 to 1.0.1

Upload the new zip over 1.0.0. No settings change. The front pages pick up the corrected verse automatically because they reference the theme patterns.

## From nothing to 1.9.0 (first install)

1. Install and activate Polylang. In Languages > Languages, add English (code `en`) and Bahasa Melayu (code `ms`). Set English as the default language.
2. In Languages > Settings > URL modifications, tick "The front page URL contains the language code instead of the page name or page id". Without it, `/ms/` redirects to `/ms/utama/`.
3. Make sure Twenty Twenty-Five is installed. Upload `lailatulqadar-1.9.0.zip` in Appearance > Themes > Add New Theme and activate it.
4. Visit any admin screen. Setup runs once and reports the result in Appearance > Theme Options.
5. If Polylang was not ready at step 4, finish steps 1 and 2 and press Run setup in the Tools tab. Existing pages are kept.

The live site currently runs Twenty Twenty-Four with its demonstration content. That content lives in the Twenty Twenty-Four templates and disappears when this theme is activated. The default "Sample Page" remains and can be deleted.

## General rules for later versions

- Upload the new zip over the existing theme. Settings and pages are preserved.
- Read the matching entry in CHANGELOG.md before upgrading.
- Setup never overwrites an existing page. Running it again only fills gaps.

## Roadmap

Everything below is a plan, open to change. Each item says why it is here. An item leaves this list when it ships, and goes into `CHANGELOG.md`.

### Next

- Run the tests on PHP 8.4 in CI. The theme needs 8.4 since 1.22.0, while the tests here run on whatever PHP the machine has, and WP TOC's own code only loads on 8.4.
- Keep `wp-toc/` in step with https://github.com/menj/wp-toc. It is a copy, so a new WP TOC release reaches the theme only when the folder is copied again and `inc/toc.php` checked against its filters.
- Duʿāʾ, evidence and Qurʾān verse blocks, and a glossary. `SSOT.md` planned them for November to mid-December 2026, and `blocks/` has none of them yet.
- A performance pass and a pre-season SEO review, by 15 January 2027. Ramadan 1448 is expected to begin on 8 February 2027, and `SSOT.md` sets this date.
- Keep the `.htaccess` sections on a network. `lq_sec_remove_htaccess()` takes them out on any theme switch, while another site on the network may still run the theme; the error pages already stay.
- Refuse the core files without mod_rewrite. The direct-access section is inside `<IfModule mod_rewrite.c>` only; an Apache 2.4 `<If>` would cover a server without it.

### Later

- Protect the login screen. The logs for October and November show 1,977 login form posts, many in pairs from rotating addresses every 12 minutes. A private login address would end most of it.
- Find the broken link behind `/wp-login.php%7ccontact@menj.org`, asked for 164 times in the same logs. Some page outside the theme seems to join the login address and an email with a pipe.
- Extend the Umm al-Qura table past 1473 AH (2051), as `SSOT.md` asks.

### Ideas

- A Hijri date without intl. The guide pages leave their dates out on a server without the extension; the Umm al-Qura table in `inc/ramadan.php` holds only the start of each Ramadan and Shawwal, so it cannot date other months.
- Translate the Guide page box and the new editor labels in `languages/ms_MY.po`. The admin strings there are still empty, so an administrator working in Malay sees them in English.

- Remove development files from a live site after an update. Here the `.htaccess` files refuse them, which does nothing on Nginx.
- Check the one 500 on `/ms/embed/` in the logs.
- Before upgrading adhan, check its upstream source: `js/vendor/adhan.js` marks its shadow-angle calculation with a TODO.
