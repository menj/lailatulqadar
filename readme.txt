=== Lailatulqadar ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.21.2
License: GNU General Public License v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Bilingual English and Malay guide theme for Laylat al-Qadr, built as a child of Twenty Twenty-Five.

== Description ==

Lailatulqadar runs lailatulqadar.guide, a fixed guide to Laylat al-Qadr in English at the site root and in Malay under /ms/. It needs Twenty Twenty-Five installed as its parent and no plugins.

A setup routine creates the guide pages in both languages, fills them with the articles the theme ships, pairs each page with its translation and sets the front page. It never overwrites text an editor has written. A guide page that is still an empty draft takes the shipped title and address before it is filled.

The site has no blog. Post addresses and feeds send visitors to the home page of their language.

== Features ==

* English and Malay without a translation plugin: page language, hreflang links, a language switcher and a Malay site name.
* Night and dawn colour schemes, an automatic scheme that follows the device, and a light and dark switch for visitors.
* Self-hosted typefaces: EB Garamond for Latin text, KFGQPC Hafs for Qurʾānic text only, Arslan Wessam for hadith and Arabic quotations.
* Blocks: guide menu, Night Planner (night window), odd nights, breadcrumbs, logo mark, scheme toggle, language switcher, photo, image credits and contents.
* A contents list you place yourself, on any page: the Contents block, or the `[auto_toc]` shortcode written for the "Table-of-Contents Shortcode Done Properly" plugin, which now works without it.
* A perpetual Ramadan calendar from the Umm al-Qura table up to 1473 AH, with the `[lq_ramadan]` shortcode.
* Title tags in the form "page title | Lailatulqadar" under 60 characters, meta descriptions, structured data, an XML and an image sitemap. With Rank Math active the theme hands over and joins its graph.
* Guide pages show when they were published and last updated, the date they were last checked against their sources, the reading time, a contents list, the evidence and sources they rest on, and the previous and next page of the reading path. Every date carries its Hijri date, in English or in Malay as the page is.
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
2. Upload the new lailatulqadar folder into wp-content/themes under a temporary name, such as lailatulqadar-new. Send its .htaccess files first: the one at the top of the folder, then those in tests, bin and docs when your copy has them. On Apache they keep the theme's private files closed to visitors while the rest arrives. Nginx ignores .htaccess files, so on an Nginx host make sure the server rules in README.md are in place before you upload; they cover the lailatulqadar-new folder too. Staying inside wp-content/themes keeps the folder on the same disk as the live one, so the rename in step 3 works in one step.
3. When every file is up, rename the old folder (if there is one) to lailatulqadar-old, then at once rename lailatulqadar-new to lailatulqadar. Do the two renames one straight after the other, at a quiet hour, with Appearance > Themes closed. A page asked for in the moment between them finds no theme and shows an error, and if WordPress opens that screen while the folder is missing, it switches the site to another theme. Delete lailatulqadar-old once the site looks right.
4. Activate the theme if it is not active yet, and open any admin screen as an administrator.

The folder must be called lailatulqadar, all lower case.

Uploading into the live folder file by file is riskier. While one of the theme's PHP files is missing or half written, visitors see a short "Back shortly" page in English and Malay instead of an error.

That page covers only those PHP files. functions.php itself, the templates, the stylesheets, the images and the content files are not checked, and while one of them is missing or cut off a page can look broken or show an error. The temporary folder and the rename avoid all of it.

Never upload WordPress itself (wp-admin, wp-includes and the files beside them) to a live folder that has no wp-config.php. In that state the WordPress installer answers anyone who finds it. Put wp-config.php in place first, or upload WordPress to a temporary folder outside the web root and move it into place.

== Updating ==

Upload the new version in the same way. Then open any admin screen as an administrator: setup notices the new version and brings every page you have not edited up to date.

A page you have edited keeps your text. The Tools tab lists it as "Edited, not updated", with a button to take the new version if you want it.

UPGRADING.md in the theme folder has a note for each version and says what is planned next.

== Privacy ==

The theme makes no remote requests and loads no tracking, fonts or scripts from other servers. Every font, image and script ships in the theme.

Two things stay in the visitor's own browser (localStorage) and never reach the server: the light or dark choice made with the switch, and the city and start date chosen in the night window of the Night Planner. The night window asks for the visitor's location only when the visitor presses its location button. It works out the times in the browser and keeps that position in localStorage as the chosen city; it sends it nowhere.

For administrators, the Security tab records the time and IP address of each administrator login, failed logins by account name and IP address, deleted once they are 24 hours old, and a log of up to 200 security events. None of it leaves the site.

== Frequently Asked Questions ==

= How do I show when a page was last checked? =

Open the page in the editor and find the Guide page box beside it. Enter the date in "Last reviewed against its sources" and update. The date shows under the title, with its Hijri date. Leave it empty and the line does not show.

= A page has no In brief box. Can I add one? =

Yes. Type the points in the Guide page box, one a line, up to six. They show above the text in the same box the other pages use. A page whose text already has an In brief box keeps its own and ignores these points.

= Does the theme need a translation plugin? =

No. The theme handles English and Malay itself: Run setup creates the Malay pages beneath /ms/ and pairs each with its English version. Deactivate Polylang if it is installed.

= Why do new pages not appear in the menus? =

Menus list published pages only. A draft joins the menus when it is published; the reading path shows it as "Coming soon" until then.

= I see "Back shortly" on the site. =

The theme is incomplete on the server, usually because an upload is still running or stopped part-way. Upload the missing files, or the whole folder again. The login screen keeps working, unless the file missing or broken is functions.php itself.

= My host runs Nginx. =

The theme writes its server rules only on Apache. On Nginx, give your host the rules in README.md to add to the server settings.

= Who can open Theme Options? =

Administrators (the manage_options capability). The page holds the security switches and setup, so the theme keeps it to administrators.

= How do I add a table of contents? =

Add the Contents block where the list should go, and choose the headings to list and the title in the block settings. In text without blocks, write [auto_toc] on a line of its own; [auto_toc tags="h2,h3,h4" title="In this article"] lists three levels under your own title, and title="" leaves the title out. Guide pages get a list of their H2 headings without either; a list you place takes its place.

= My site is in a subfolder. Where does the browser icon go? =

Browsers ask for /favicon.ico at the top of the domain, such as example.com/favicon.ico, even when the site lives in example.com/blog/. WordPress never sees that request, so put a copy of the theme's favicon.ico in the domain root folder. The icon tags in each page cover the pages either way. If you have set a Site Icon, put that icon there as favicon.ico instead.

== Upgrade Notice ==

= 1.21.2 =
Nothing to do. Keyboard users keep their place in the night window when they change the start date or the year.

= 1.21.1 =
Nothing to do. Phones get larger tap areas, and long titles in the breadcrumb no longer widen the page.

= 1.21.0 =
Nothing to do. The front page shows the reading path as cards in both languages.

= 1.20.0 =
Nothing to do. Five guide pages in each language take their new text on the first admin visit, unless you have edited them.

= 1.19.1 =
Nothing to do. The Islamic Dilemma page in each language takes the corrected text on the first admin visit, unless you have edited it.

= 1.19.0 =
Nothing to do. If you used the "Table-of-Contents Shortcode Done Properly" plugin or its code in functions.php, remove it: the theme now runs [auto_toc] itself.

= 1.18.13 =
Replace the theme files. On a site in a subfolder, also put a copy of images/favicon.ico in the domain root folder (or, with a Site Icon set, that icon as favicon.ico); otherwise nothing to do.

= 1.18.12 =
Upload the new zip. Open one admin page so the error pages are written again with the icon.

= 1.18.11 =
Documentation only. The FTP update steps now upload the new folder into wp-content/themes under a temporary name.

= 1.18.10 =
Nothing to do. Guide pages gain their dates, reading time, contents, sources and a previous and next row. The theme is now under the GNU GPL version 3 or later.

= 1.18.9 =
Documentation only. Setup runs once, as after any update, and finds nothing to change.

= 1.18.8 =
Upload with hidden files shown, so the new .htaccess files go up.

= 1.17.0 =
Four plugin features are built in. Once the new Reading and sharing tab is set, deactivate Reading Progress Bar, Unlist Posts & Pages and Pretty Search Permalinks; until then the theme leaves those jobs to them.

= 1.16.0 =
Theme Options has a new card layout with tabs that switch without a reload. Every setting keeps its value. Setup runs once on the first admin visit.

== Changelog ==

= 1.21.2 =
* Accessibility audit (WCAG 2.2 AA) in English and under /ms/. In the night window, keyboard focus now stays on the start-date choice or the year button you used. The choices are larger targets. New tests/a11y-axe.js, and RESOURCES.md records the licences.

= 1.21.1 =
* Mobile-first audit in English and Malay. A long title in the breadcrumb no longer makes a phone scroll sideways, the night window year buttons and the header logo are easier to tap, and the text no longer jumps when the font loads.

= 1.21.0 =
* The front page shows the reading path as cards, in English and Malay: each chapter under an eight-pointed star, a feature card for its first page, and each page with its number, an icon, a summary and its reading time.
* Pages open faster (the next page is prepared when you point at its link) and change with a soft fade in browsers that support it. Keyboard focus is always visible.

= 1.20.0 =
* The classical Arabic dictionaries join the sources: What Is Laylat al-Qadr, Sūrat al-Qadr, the Duʿāʾ, the Islamic Dilemma and the FAQ cite them on the words they explain, in English and Malay, with links to each entry.
* Code review: the structured data can no longer be broken by a search term, admin actions check permissions before anything else, and the admin boxes and code comments were tidied.

= 1.19.1 =
* The Islamic Dilemma page shows its notes again, in English and Malay: the numbers in its text led to notes the page never displayed.
* A note cited twice on a page no longer repeats the same anchor in the page code.

= 1.19.0 =
* A contents list you place yourself: the new Contents block, or the [auto_toc] shortcode. It lists the headings you choose (H2 and H3 by default), nested by level, with a title of your own or "On this page" ("Kandungan halaman" on Malay pages). On a guide page it takes the place of the automatic list.

= 1.18.13 =
* The settings and the readme speak of the other sites in plain words. Links to them stay.
* The readme says where the browser icon goes on a site in a subfolder: a copy of favicon.ico in the domain root.

= 1.18.12 =
* The browser icon set is complete: favicon.ico, 192px, 512px and maskable icons and a web app manifest, all drawn from the lantern mark. The site answers /favicon.ico with the icon, the icons show in the admin as well as on the site and the login screen, and the error pages show the mark in the tab. A Site Icon you set still wins.

= 1.18.11 =
* The FTP update steps upload the new folder into wp-content/themes under a temporary name, its .htaccess files first, and then rename it into place.

= 1.18.10 =
* Guide pages show, under the title, when they were published and updated, when they were last reviewed and how long they take to read. Each date carries its Hijri date.
* A contents list follows the In brief box on every guide page with three or more sections. An Evidence and sources block lists the verses, hadith and works the page cites, and a previous and next row ends the page.
* The Guide page box on the editor takes the review date, and In brief points for a page that has no box of its own.
* Every table on a guide page takes the guide's table style and can be scrolled from the keyboard.
* The theme is now under the GNU GPL version 3 or later, with the full text in LICENSE.

= 1.18.9 =
* Documentation only. A technical README joins the readme, the changelog, the single source of truth and the upgrade notes. The readme now covers updating over FTP.

= 1.18.8 =
* The web server refuses the theme's development files, notes and PHP files, and the WordPress core files that never serve a page. The settings reference and the theme readme stay open.

Earlier releases, back to 1.0.0, are in CHANGELOG.md.

== Copyright ==

Lailatulqadar is distributed under the terms of the GNU GPL version 3 or later. The full text is in the LICENSE file.

EB Garamond, Copyright 2017 The EB Garamond Project Authors. SIL Open Font License 1.1. See fonts/eb-garamond/OFL.txt.
Arslan Wessam A and B, by Arslan (Dev-Point.com). No licence text is included in the font files. See fonts/arslan-wessam/NOTICE.txt.
Noto Naskh Arabic, Copyright 2022 The Noto Project Authors. SIL Open Font License 1.1. See fonts/noto-naskh-arabic/OFL.txt.
adhan 4.4.6 (prayer-time calculation), Copyright 2016 Batoul Apps. MIT License. See js/vendor/adhan-LICENSE.txt. js/vendor/adhan.js is the unminified source of js/vendor/adhan.min.js, both from the adhan 4.4.6 npm package (lib/bundles/adhan.umd.js and adhan.umd.min.js).
Social icons in images/social (X, Facebook, YouTube, Instagram, TikTok, Threads, Bluesky, Mastodon, Telegram, WhatsApp): Minimalist Social Icons Pack 2.8, GPL. These ten come from CC0 sources; the pack's two CC BY 4.0 icons are not bundled. The brand marks belong to their owners.
KFGQPC HAFS Uthmanic Script, Copyright 2010 King Fahd Glorious Quran Printing Complex. Free to use, copy and distribute; the file must not be modified.
Served as a WOFF2 conversion of the original TTF by the owner's decision of 2026-10-26, accepting that restriction. See fonts/kfgqpc/LICENSE.txt.
Theme images: the logo mark, favicons, touch icon, app icons and 512px logo (images/) are original artwork made for the theme; the pixel book (images/book-open.webp) was supplied by the site owner. Both are distributed with the theme under the GNU GPL version 3 or later.
The contents list is adapted from "WordPress Table-of-Contents Shortcode Done Properly" by Ikram Hawramani, Copyright 2025. MIT License; the notice is kept in inc/contents.php.
Photographs in images/commons/ come from Wikimedia Commons, Pixabay and Pexels under the licences listed per file in inc/data/images.json, and are credited beneath each image and on the Sources page.
