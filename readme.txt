=== Lailatulqadar ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.18.1
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
* Contextual links to other guide pages and to the sister sites of the network.
* Social profile icons, a reading progress bar, unlisted pages and search addresses of the form /search/term/.
* A Security tab that closes XML-RPC, application passwords and REST writes from outside the browser, holds gambling spam as Pending and offers a spam scan.

== Usage ==

Open Appearance > Theme Options. Each tab saves only its own fields. Most tabs switch without a reload; General, Search, Security and Tools reload the page, since they run reports. Arrow keys, Home and End move between tabs.

* General: the logo calligraphy image, the footer note in each language, the author named in structured data, and the state of every guide page.
* Languages: how the two languages work, the switcher labels and the site name on Malay pages.
* Colours: the colour scheme cards, the visitor switch and the two accent colours, each with a live preview.
* Ramadan Dates: the next five Ramadans from the Umm al-Qura table, and an optional override for one year.
* Login: tagline and note on the login screen in each language.
* Search: every title tag and meta description with its length, and the Google and Bing verification codes.
* Contextual links: switches and sliders for links to this site and to the sister sites, extra phrases and pages left without links.
* Images: whether setup sets each page's lead image as its featured image.
* Reading and sharing: the reading progress bar, the social profile addresses and the search address base.
* Security: the five security switches, the spam scan, password and session tools, and the reports and log.
* Tools: page states, publishing written drafts, Run setup and Reset settings.

docs/settings.md describes every field.

== Installation ==

1. Install Twenty Twenty-Five. It must stay installed; it need not be active.
2. Upload the Lailatulqadar zip in Appearance > Themes > Add New and activate it.
3. Open any admin screen as an administrator. Setup runs once and creates the pages in both languages.
4. Check the report on the Tools tab of Theme Options.

UPGRADING.md lists what each version changes on the site.

== Privacy ==

The theme makes no remote requests and loads no tracking, fonts or scripts from other servers. Every font, image and script ships in the theme.

Two things stay in the visitor's own browser (localStorage) and never reach the server: the light or dark choice made with the switch, and the city and start date chosen in the night window of the Night Planner. The night window asks for the visitor's location only when the visitor presses its location button. It works out the times in the browser and keeps that position in localStorage as the chosen city; it sends it nowhere.

For administrators, the Security tab records the time and IP address of each administrator login, failed logins by account name and IP address, deleted once they are 24 hours old, and a log of up to 200 security events. None of it leaves the site.

== Frequently Asked Questions ==

= Does the theme need a translation plugin? =

No. The theme handles English and Malay itself: Run setup creates the Malay pages beneath /ms/ and pairs each with its English version. Deactivate Polylang if it is installed.

= Why do new pages not appear in the menus? =

Menus list published pages only. A draft joins the menus when it is published; the reading path shows it as "Coming soon" until then.

= Who can open Theme Options? =

Administrators (the manage_options capability). The page holds the security switches and setup, so the theme keeps it to administrators.

== Upgrade Notice ==

= 1.17.0 =
Four plugin features are built in. Once the new Reading and sharing tab is set, deactivate Reading Progress Bar, Unlist Posts & Pages and Pretty Search Permalinks; until then the theme leaves those jobs to them.

= 1.16.0 =
Theme Options has a new card layout with tabs that switch without a reload. Every setting keeps its value. Setup runs once on the first admin visit.

== Changelog ==

= 1.18.1 =
* The Malay line on the 404 page reads more plainly.

= 1.18.0 =
* Error pages in the guide's night colours, in English and Malay, for the moments WordPress cannot build a page: an update, a PHP fault, a lost database. Install them from Theme Options > Tools.
* Static 403, 500, 502 and 503 pages for the web server, with the .htaccess lines on the Tools tab.
* The 404 page has a search field and tells search engines not to index it.

= 1.17.0 =
* Social icons for the site profiles in the footer, with a block, a pattern and a shortcode for anywhere else.
* A reading progress bar on single pages, with its colour, height, position and post types in Theme Options.
* Unlist a page from its editor sidebar, Quick Edit or Bulk Edit. It keeps its address, leaves search, the sitemaps and page lists, and carries noindex.
* Searches move to /search/term/ with a permanent redirect. The base can be changed in Theme Options or Settings > Permalinks.

= 1.16.0 =
* Theme Options rebuilt as cards with keyboard tabs, toggle switches, sliders and colour pickers. Every option and behaviour is unchanged.
* The theme declares title-tag and automatic-feed-links, ships the unminified adhan source, and has fresh translation files. The readme gains features, usage, privacy and upgrade notes.

= 1.15.1 =
* Phones: tables fit the screen, the footer menu no longer runs off a 320px screen, and the footer links and the light and dark switch are 44px tall. The search field shows a focus ring.

= 1.15.0 =
* The site has no blog. Every post address and feed sends you to the home page of its language, and posts leave the sitemaps.

= 1.14.0 =
* A small pixel book before each Qur'an reference under a verse. Still for readers who ask for reduced motion.

= 1.13.0 =
* With Rank Math active, the theme's structured data joins Rank Math's graph. A page still has one graph.
* FAQPage on the FAQ, HowTo where a page shows numbered steps, Event for the Ramadan dates and odd nights a page shows.
* Photographs carry their credit, licence and source; footnotes become citations; tables, verses and hadith, menus, header and footer are marked up.
* English and Malay articles point to each other as translations.

= 1.12.1 =
* Links to the sister sites follow their new addresses, which carry no verse numbers, counts or years.

= 1.12.0 =
* Title tags keep the form "page title | Lailatulqadar", under 60 characters, with Rank Math active too.
* Posts and archives get a meta description, and archives a canonical link.
* Every Article in the structured data has an image: the featured image, else the lead photograph, else the site mark.
* New image sitemap at /image-sitemap.xml, named in robots.txt.

= 1.11.2 =
* English articles: 6 sentences and table cells that set up a claim by denying another now state the claim, and 4 uses of banned verbs are replaced with plain ones. Facts, citations and quotations are unchanged.

= 1.11.1 =
* Security fixes: the spam gate exempts only pages that carry the theme's seed markers or that setup is creating, counts WP-CLI as outside the editor and leaves private posts as they are. 410 Gone also covers /?p=ID. Detection no longer depends on mbstring.

= 1.11.0 =
* New Security tab: XML-RPC, application passwords and REST writes from outside the browser are off; gambling spam and anything published from outside the editor is held as Pending; spam scan, trash tool, 410 Gone and reports. Night Planner: a checklist for each night. Last Ten Nights: one sentence on counting the last ten days with their nights.

= 1.9.18 =
* Title tags read "title | Lailatulqadar" and stay under 60 characters. Images from Pixabay and Pexels beside Wikimedia Commons; four new images.

= 1.9.17 =
* The page registry (titles, addresses, menu labels and search descriptions) is a JSON data file. Nothing on the site changes.

= 1.9.16 =
* New posts and pages get addresses without "a", "an" or "the". Existing addresses are unchanged.

= 1.9.15 =
* The Islamic Dilemma page calls the two parts of the argument its two claims, the term the sister sites use.

= 1.9.14 =
* Code review fixes: an atomic setup lock, accurate word counts, folded calendar lines, safer login colours and several smaller fixes.

= 1.9.13 =
* Malay pages keep the exact Arabic code points of the source text; a hadith card shows its full Arabic; sign summaries corrected.

= 1.9.12 =
* Every article is 1,600 to 2,500 words in English and Malay (planner and sources exempt). New sourced sections on Start Here, Last Ten Nights, Dua, Signs and When.

= 1.9.11 =
* Arabic source titles now carry a translation in parentheses. New material from Ibn Kathir's tafsir on Surah al-Qadr; held-back reports traced.

= 1.9.10 =
* Hadith and Sources lists every hadith in the Laylat al-Qadr chapters of al-Bukhari and Muslim: six reports added, each checked on shamela.ws.

= 1.9.9 =
* Every hadith checked on shamela.ws; source links show page titles. New sections from Ibn Hajar, al-Nawawi and Ibn Rajab in English and Malay.

= 1.9.8 =
* Malay translations of all eleven articles; setup publishes them beneath /ms/. Malay alt text and captions for the Commons images.

= 1.9.7 =
* Upgrade fix: guide pages first seeded by 1.9.0 are no longer mistaken for edited pages, so they update and publish.

= 1.9.6 =
* New [lq_lanah] shortcode: laʿnat Allāh after the name of Paul of Tarsus, as calligraphy in the scheme accent. No article uses it.

= 1.9.5 =
* Content corrections: sun-safety wording on Signs, al-Albānī gradings first, verbatim 97:3 on the front page, no fixed Gregorian Ramadan date.

= 1.9.4 =
* Five new English articles: Start Here, What Is Laylat al-Qadr?, Prayer and Worship, FAQ, Hadith and Sources.

= 1.9.3 =
* The shipped articles are JSON data files (`content/<lang>/<key>.json`), read with `json_decode()`; the HTML and separate footnotes files are gone.

= 1.9.2 =
* The Qurʾān face is served as a WOFF2 conversion (107 KB, from 298 KB) in place of the original TTF, by the owner's decision, accepting that the font's licence forbids modifying it.

= 1.9.1 =
* Contextual links: the first mention of a known phrase in a guide page or post links to the page that treats it, on this site or on a sister site.

= 1.9.0 =
* English and Malay handled by the theme itself; Polylang no longer used.

= 1.8.1 =
* Reading path lists upcoming pages as "Coming soon"; Tools tab shows every guide page's state and publishes written drafts in one step.

= 1.8.0 =
* Parent-theme compatibility with Twenty Twenty-Five 1.5.

= 1.7.9 =
* Header shows the lantern alone; the crescent and star stay on the login screen and larger icons.

= 1.7.8 =
* Lantern redrawn: onion dome, arched window, stepped base, with a crescent and star.

= 1.7.7 =
* Own templates for single posts, archives, search results and the blog index (retired in 1.15.0).

= 1.7.6 =
* New logo: mosque lamp with the Arabic calligraphy of Laylat al-Qadr.

= 1.7.5 =
* Login screen styled to match the theme, in both colour schemes, with a Login settings tab.

= 1.7.4 =
* Full hand-over to Rank Math when it is active: verification codes, breadcrumbs and dated title variables.

= 1.7.3 =
* Profile page structured data on About; Search Console and Bing verification fields; descriptive anchors in the Last Ten Nights table.

= 1.7.2 =
* Logo mark beside the wordmark; default favicon, touch icon and structured-data logo.

= 1.7.1 =
* Light and dark switch in the header, remembered per visitor; night bar corrected in the light scheme.

= 1.7.0 =
* Structured data (Organization, Breadcrumb, Article) and visible breadcrumbs; XML sitemap trimmed to pages.

= 1.6.1 =
* Hijri equivalents beside every Gregorian date.

= 1.6.0 =
* Perpetual Ramadan calendar: dates, articles, title tags and the night window roll over to the next Ramadan automatically, through 1473 AH (2051).

= 1.5.2 =
* Night window adds Marawi, Narathiwat and Maungdaw (41 cities).

= 1.5.1 =
* Tablet layouts for the night window; article boxes aligned to the text column.

= 1.5.0 =
* Night window: interactive timetable of the last ten nights for 38 cities or the visitor's location, with calendar export.

= 1.4.0 =
* First five English articles, seeded into empty drafts by Run setup; article styling.

= 1.3.0 =
* EB Garamond for Latin text; Arslan Wessam for hadith and Arabic quotations; new paragraph styles.

= 1.2.0 =
* Aligned with the agency SEO work-progress template: 60-character titles, HTML sitemap page, Rank Math field sync.

= 1.1.1 =
* Qurʾānic text locked to KFGQPC Hafs; new "Qurʾān verse" block style.

= 1.1.0 =
* Keyword architecture: new Surah al-Qadr, Last Ten Nights and Duʿāʾ pages; title tags and meta descriptions; Search tab. See CHANGELOG.md.

= 1.0.1 =
* Qurʾānic text set in KFGQPC Hafs; Noto Naskh Arabic replaces Amiri. See CHANGELOG.md.

= 1.0.0 =
* First release. See CHANGELOG.md.

== Copyright ==

Lailatulqadar is distributed under the terms of the GNU GPL v2 or later.

EB Garamond, Copyright 2017 The EB Garamond Project Authors. SIL Open Font License 1.1. See fonts/eb-garamond/OFL.txt.
Arslan Wessam A and B, by Arslan (Dev-Point.com). No licence text is included in the font files. See fonts/arslan-wessam/NOTICE.txt.
Noto Naskh Arabic, Copyright 2022 The Noto Project Authors. SIL Open Font License 1.1. See fonts/noto-naskh-arabic/OFL.txt.
adhan 4.4.6 (prayer-time calculation), Copyright 2016 Batoul Apps. MIT License. See js/vendor/adhan-LICENSE.txt. js/vendor/adhan.js is the unminified source of js/vendor/adhan.min.js, both from the adhan 4.4.6 npm package (lib/bundles/adhan.umd.js and adhan.umd.min.js).
Social icons in images/social (X, Facebook, YouTube, Instagram, TikTok, Threads, Bluesky, Mastodon, Telegram, WhatsApp): Minimalist Social Icons Pack 2.8, GPL. These ten come from CC0 sources; the pack's two CC BY 4.0 icons are not bundled. The brand marks belong to their owners.
KFGQPC HAFS Uthmanic Script, Copyright 2010 King Fahd Glorious Quran Printing Complex. Free to use, copy and distribute; the file must not be modified. Served as a WOFF2 conversion of the original TTF by the owner's decision of 2026-10-26, accepting that restriction. See fonts/kfgqpc/LICENSE.txt.
Theme images: the logo mark, favicon, touch icon and 512px logo (images/) are original artwork made for the theme; the pixel book (images/book-open.webp) was supplied by the site owner. Both are distributed with the theme under the GNU GPL v2 or later.
Photographs in images/commons/ come from Wikimedia Commons, Pixabay and Pexels under the licences listed per file in inc/data/images.json, and are credited beneath each image and on the Sources page.
