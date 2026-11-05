=== Lailatulqadar ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.18.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bilingual English and Malay guide theme for Laylat al-Qadr, built as a child of Twenty Twenty-Five.

== Description ==

Lailatulqadar runs lailatulqadar.guide, a fixed guide to Laylat al-Qadr in English at the site root and in Malay under /ms/. It needs Twenty Twenty-Five installed as its parent and no plugins.

A setup routine creates the guide pages in both languages, fills them with the articles the theme ships, pairs each page with its translation and sets the front page. It never overwrites text an editor has written. A guide page that is still an empty draft takes the shipped title and address before it is filled.

The site has no blog. Post addresses and feeds send visitors to the home page of their language.

== Features ==

* English and Malay without a translation plugin: page language, hreflang links, a language switcher and a Malay site name.
* Night and dawn colour schemes, an automatic scheme that follows the device, and a light and dark switch for visitors.
* Self-hosted typefaces: EB Garamond for Latin text, KFGQPC Hafs for Qurʾānic text only, Arslan Wessam for hadith and Arabic quotations.
* Blocks: guide menu, Night Planner (night window), odd nights, breadcrumbs, logo mark, scheme toggle, language switcher, photo and image credits.
* A perpetual Ramadan calendar from the Umm al-Qura table up to 1473 AH, with the `[lq_ramadan]` shortcode.
* Title tags in the form "page title | Lailatulqadar" under 60 characters, meta descriptions, structured data, an XML and an image sitemap. With Rank Math active the theme hands over and joins its graph.
* Contextual links from the first mention of a subject to the page that treats it, on this site or another.
* Social profile icons, a reading progress bar, unlisted pages and search addresses of the form /search/term/.
* A Security tab that closes XML-RPC, application passwords and REST writes from outside the browser, holds gambling spam as Pending and offers a spam scan.

== Usage ==

Open Appearance > Theme Options. Each tab saves only its own fields.

Most tabs switch without a reload; General, Search, Security and Tools reload the page, since they run reports. Arrow keys, Home and End move between tabs.

* General: the logo calligraphy image, the footer note in each language, the author named in structured data, and the state of every guide page.
* Languages: how the two languages work, the switcher labels and the site name on Malay pages.
* Colours: the colour scheme cards, the visitor switch and the two accent colours, each with a live preview.
* Ramadan Dates: the next five Ramadans from the Umm al-Qura table, and an optional override for one year.
* Login: tagline and note on the login screen in each language.
* Search: every title tag and meta description with its length, and the Google and Bing verification codes.
* Contextual links: switches and sliders for links to this site and to other sites, extra phrases and pages left without links.
* Images: whether setup sets each page's lead image as its featured image.
* Reading and sharing: the reading progress bar, the social profile addresses and the search address base.
* Security: the five security switches, the spam scan, password and session tools, and the reports and log.
* Tools: page states, publishing written drafts, Run setup and Reset settings.

docs/settings.md describes every field.

== Installation ==

Twenty Twenty-Five must be installed first. It must stay installed, but it need not be active.

With the WordPress uploader:

1. Go to Appearance > Themes > Add New > Upload Theme.
2. Choose the Lailatulqadar zip, install it and activate it.
3. Open any admin screen as an administrator. Setup runs once and creates the pages in both languages.
4. Read the report on the Tools tab of Theme Options.

With FTP:

1. Turn on hidden files in your FTP program first. In FileZilla it is Server > Force showing hidden files. The theme has files whose names start with a dot (.htaccess), and they must go up too.
2. Upload the lailatulqadar folder into wp-content/themes under a temporary name, such as lailatulqadar-new.
3. When every file is up, rename the old folder (if there is one) to lailatulqadar-old, then at once rename lailatulqadar-new to lailatulqadar. Do the two renames one straight after the other, at a quiet hour, with Appearance > Themes closed. A page asked for in the moment between them finds no theme and shows an error, and if WordPress opens that screen while the folder is missing, it switches the site to another theme. Delete lailatulqadar-old once the site looks right.
4. Activate the theme if it is not active yet, and open any admin screen as an administrator.

The folder must be called lailatulqadar, all lower case.

Uploading into the live folder file by file also works for most files. While one of them is missing or half written, visitors see a short "Back shortly" page in English and Malay instead of an error.

The one file that page cannot cover is functions.php, since it is the file that shows it. If that file is cut off part-way, visitors get an error until it is whole. The temporary folder and the rename avoid both.

Never upload WordPress itself (wp-admin, wp-includes and the files beside them) to a live folder that has no wp-config.php. In that state the WordPress installer answers anyone who finds it. Put wp-config.php in place first, or upload WordPress to a temporary folder and rename it.

== Updating ==

Upload the new version in the same way. Then open any admin screen as an administrator: setup notices the new version and brings every page you have not edited up to date.

A page you have edited keeps your text. The Tools tab lists it as "Edited, not updated", with a button to take the new version if you want it.

UPGRADING.md in the theme folder has a note for each version and says what is planned next.

== Privacy ==

The theme makes no remote requests and loads no tracking, fonts or scripts from other servers. Every font, image and script ships in the theme.

Two things stay in the visitor's own browser (localStorage) and never reach the server: the light or dark choice made with the switch, and the city and start date chosen in the night window of the Night Planner. The night window asks for the visitor's location only when the visitor presses its location button. It works out the times in the browser and keeps that position in localStorage as the chosen city; it sends it nowhere.

For administrators, the Security tab records the time and IP address of each administrator login, failed logins by account name and IP address, deleted once they are 24 hours old, and a log of up to 200 security events. None of it leaves the site.

== Frequently Asked Questions ==

= Does the theme need a translation plugin? =

No. The theme handles English and Malay itself: Run setup creates the Malay pages beneath /ms/ and pairs each with its English version. Deactivate Polylang if it is installed.

= Why do new pages not appear in the menus? =

Menus list published pages only. A draft joins the menus when it is published; the reading path shows it as "Coming soon" until then.

= I see "Back shortly" on the site. =

The theme is incomplete on the server, usually because an upload is still running or stopped part-way. Upload the missing files, or the whole folder again. The login screen keeps working.

= My host runs Nginx. =

The theme writes its server rules only on Apache. On Nginx, give your host the rules in README.md to add to the server settings.

= Who can open Theme Options? =

Administrators (the manage_options capability). The page holds the security switches and setup, so the theme keeps it to administrators.

== Upgrade Notice ==

= 1.18.9 =
Documentation only. Setup runs once, as after any update, and finds nothing to change.

= 1.18.8 =
Upload with hidden files shown, so the new .htaccess files go up.

= 1.17.0 =
Four plugin features are built in. Once the new Reading and sharing tab is set, deactivate Reading Progress Bar, Unlist Posts & Pages and Pretty Search Permalinks; until then the theme leaves those jobs to them.

= 1.16.0 =
Theme Options has a new card layout with tabs that switch without a reload. Every setting keeps its value. Setup runs once on the first admin visit.

== Changelog ==

= 1.18.9 =
* Documentation only. A technical README joins the readme, the changelog, the single source of truth and the upgrade notes. The readme now covers updating over FTP.

= 1.18.8 =
* The web server refuses the theme's development files, notes and PHP files, and the WordPress core files that never serve a page. The settings reference and the theme readme stay open.

= 1.18.7 =
* A theme caught half uploaded shows a short "Back shortly" page (503) instead of an error.
* Visitors can no longer list the site's user accounts, and scanner probes for PHP and .env files get a short 404 without loading a whole page.

Earlier releases, back to 1.0.0, are in CHANGELOG.md.

== Copyright ==

Lailatulqadar is distributed under the terms of the GNU GPL v2 or later.

EB Garamond, Copyright 2017 The EB Garamond Project Authors. SIL Open Font License 1.1. See fonts/eb-garamond/OFL.txt.
Arslan Wessam A and B, by Arslan (Dev-Point.com). No licence text is included in the font files. See fonts/arslan-wessam/NOTICE.txt.
Noto Naskh Arabic, Copyright 2022 The Noto Project Authors. SIL Open Font License 1.1. See fonts/noto-naskh-arabic/OFL.txt.
adhan 4.4.6 (prayer-time calculation), Copyright 2016 Batoul Apps. MIT License. See js/vendor/adhan-LICENSE.txt. js/vendor/adhan.js is the unminified source of js/vendor/adhan.min.js, both from the adhan 4.4.6 npm package (lib/bundles/adhan.umd.js and adhan.umd.min.js).
Social icons in images/social (X, Facebook, YouTube, Instagram, TikTok, Threads, Bluesky, Mastodon, Telegram, WhatsApp): Minimalist Social Icons Pack 2.8, GPL. These ten come from CC0 sources; the pack's two CC BY 4.0 icons are not bundled. The brand marks belong to their owners.
KFGQPC HAFS Uthmanic Script, Copyright 2010 King Fahd Glorious Quran Printing Complex. Free to use, copy and distribute; the file must not be modified.
Served as a WOFF2 conversion of the original TTF by the owner's decision of 2026-10-26, accepting that restriction. See fonts/kfgqpc/LICENSE.txt.
Theme images: the logo mark, favicon, touch icon and 512px logo (images/) are original artwork made for the theme; the pixel book (images/book-open.webp) was supplied by the site owner. Both are distributed with the theme under the GNU GPL v2 or later.
Photographs in images/commons/ come from Wikimedia Commons, Pixabay and Pexels under the licences listed per file in inc/data/images.json, and are credited beneath each image and on the Sources page.
