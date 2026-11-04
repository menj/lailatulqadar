# Settings reference

Appearance > Theme Options. Each tab saves only its own fields. The screen is a card layout: the tabs switch without a reload, except General, Search, Security and Tools, whose panels run report queries and open with a full reload (arrow keys, Home and End move between tabs; without JavaScript each tab is a link). Two-valued settings are toggle switches, the link caps are sliders that show their value, and the accents use the WordPress colour picker. The page needs the manage_options capability; its CSS and JS (`css/admin.css`, `js/admin.js`, `css/security.css`, `js/security.js`) load on this screen only, by its hook suffix.

## General
- **Logo calligraphy.** A transparent PNG from the Media Library, recoloured to the scheme's gold; empty means typeset calligraphy.
- **Author for structured data.** Name and profile address of the author of every guide article. Not used while an SEO plugin outputs the structured data.
- **Footer note (English, Bahasa Melayu).** One line above the copyright notice in the footer. Empty hides it.
- **Guide pages.** Read-only table showing whether each page exists and is published in each language, with edit links.

## Languages
- **How the two languages work.** Explains the arrangement and shows the Malay home address (/ms/).
- **Site name on Malay pages.** Default "Lailatulqadar".
- **Switcher label.** Text shown in the language switcher.

## Colours
- **Colour scheme.** Night, dawn, or follow the visitor's device, chosen on three cards. This is what first-time visitors see.
- **Visitor switch.** A toggle that shows or hides the light and dark switch in the header. A visitor's choice is stored in their own browser.
- **Night accent, Dawn accent.** Colour for the Arabic verse, links and buttons. Keep a contrast ratio of at least 4.5:1 against the background.

## Ramadan Dates
- **Automatic dates.** The next five Ramadans from the Umm al-Qura table, with the alternative start and the evening the 27th night begins.
- **Override one year.** Hijri year, 1 Ramadan and an alternative 1 Ramadan. Empty fields mean automatic dates.

## Login
- **Login screen.** Tagline and note on the login screen, in English and Malay. An empty tagline uses the default; an empty note shows nothing.

## Search
- **Webmaster tools verification.** Google Search Console and Bing Webmaster Tools codes, printed as meta tags on the front page. Hidden while an SEO plugin handles them.
- **Search appearance.** Every guide page in both languages with its title tag and meta description and a length badge (green within 60 and 130 characters, red over). Edit values in the Search appearance box on each page. When an SEO plugin is active the theme stops printing these tags and says so here.

## Contextual links
- **How contextual links work.** Explains the rules and shows how many pages the shared map lists here and on the sister sites.
- **This site / Sister sites.** Turn each kind of link on or off, and set the most links of each kind on one page (defaults 4 and 3). Sister-site links appear on English pages only.
- **Extra phrases.** One per line, "phrase | address". An address beginning with / is on this site; a full address must be on this site or a sister site. Lines here are read before the shared map, so they win. Malay pages have their own field, for addresses on this site only.
- **Pages without links.** Page slugs that never receive contextual links.

## Images
- **Featured images.** When setup runs, set each page's lead image as its featured image. An image an editor chose is never replaced, and one an editor removed is not put back.

## Security
- **Remote publishing.** Turn off XML-RPC (xmlrpc.php answers 403; on Apache a "Block xmlrpc.php" section is kept in .htaccess), turn off application passwords, and allow REST API changes only from a logged-in browser session with a valid nonce. Anonymous requests and all reads are unchanged. Passwords sent through XML-RPC are refused even when XML-RPC is switched back on. `define( 'LQ_SECURITY_LOCK', false );` in wp-config.php turns these three off.
- **New content.** Hold suspected gambling spam, and anything published from outside the editor, as Pending; answer 410 Gone on the address of spam trashed from this tab. The guide pages the theme seeds (`_lq_lang`, `_lq_key` or `_lq_seed_hash`, or a page created by the theme's own setup run) are exempt. A title or slug alone never exempts a post. Content added through WP-CLI counts as published from outside the editor. Private posts are logged but not held.
- **Clean up spam.** Scans posts and pages 100 at a time and moves the selected ones to the trash, never deleting them. Empty spam categories are deleted only when the button is pressed.
- **Passwords and sessions.** Revoke all application passwords; log out all sessions. Lists administrators with last login and application passwords.
- **Attacks seen, Activity, Security log.** XML-RPC requests counted per day, failed logins counted by account name, posts per day, burst publishing, guide pages with gambling links, and the log.

## Tools
- **Run setup.** Creates missing guide pages, links translations, sets the front page, adds the Malay site title if none exists, renames guide pages that are still empty drafts, and fills empty title tags and meta descriptions.
- **Reset settings.** Returns every setting to its default. Pages are untouched.
