=== Lailatulqadar ===
Contributors: menj
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.9.14
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bilingual English and Malay guide theme for Laylat al-Qadr, built as a child of Twenty Twenty-Five.

== Description ==

Lailatulqadar powers lailatulqadar.guide. It provides a night and a dawn colour scheme, self-hosted EB Garamond, Arslan Wessam and KFGQPC Hafs typefaces, a language-aware guide menu, a the theme's own English and Malay handling language switcher and a tabbed settings screen. A setup routine creates the guide pages in both languages and links their translations.

== Installation ==

See UPGRADING.md for the full first-install sequence.

== Frequently Asked Questions ==

= Does the theme need a translation plugin? =

No. The theme handles English and Malay itself: Run setup creates the Malay pages beneath /ms/ and pairs each with its English version. Deactivate Polylang if it is installed.

= Why do new pages not appear in the menus? =

Setup saves guide pages as drafts. A page joins the menus when it is published.

== Changelog ==

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
* Own templates for single posts, archives, search results and the blog index.

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
adhan 4.4.6 (prayer-time calculation), Copyright 2016 Batoul Apps. MIT License. See js/vendor/adhan-LICENSE.txt.
KFGQPC HAFS Uthmanic Script, Copyright 2010 King Fahd Glorious Quran Printing Complex. Free to use, copy and distribute; the file must not be modified. Served as a WOFF2 conversion of the original TTF by the owner's decision of 2026-10-26, accepting that restriction. See fonts/kfgqpc/LICENSE.txt.
