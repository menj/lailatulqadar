# Settings reference

Appearance > Theme Options. Each tab saves only its own fields.

## General
- **Footer note (English, Bahasa Melayu).** One line above the copyright notice in the footer. Empty hides it.
- **Guide pages.** Read-only table showing whether each page exists and is published in each language, with edit links.

## Languages
- **How the two languages work.** Explains the arrangement and shows the Malay home address (/ms/).
- **Site name on Malay pages.** Default "Lailatulqadar".
- **Switcher label.** Text shown in the language switcher.

## Colours
- **Login tab.** Tagline and note on the login screen, in English and Malay. An empty tagline uses the default; an empty note shows nothing.
- **Search tab: verification.** Google Search Console and Bing Webmaster Tools codes, printed as meta tags on the front page.
- **General tab: logo calligraphy.** A transparent PNG from the Media Library, recoloured to the scheme's gold; empty means typeset calligraphy.
- **Colour scheme.** Night, dawn, or follow the visitor's device. This is what first-time visitors see.
- **Visitor switch.** Show or hide the light and dark switch in the header. A visitor's choice is stored in their own browser.
- **Night accent, Dawn accent.** Colour for the Arabic verse, links and buttons. Keep a contrast ratio of at least 4.5:1 against the background.

## Search
- **Search appearance.** Every guide page in both languages with its title tag and meta description and a length badge (green within 60 and 130 characters, red over). Edit values in the Search appearance box on each page. When an SEO plugin is active the theme stops printing these tags and says so here.

## Contextual links
- **How contextual links work.** Explains the rules and shows how many pages the shared map lists here and on the sister sites.
- **This site / Sister sites.** Turn each kind of link on or off, and set the most links of each kind on one page (defaults 4 and 3). Sister-site links appear on English pages only.
- **Extra phrases.** One per line, "phrase | address". An address beginning with / is on this site; a full address must be on this site or a sister site. Lines here are read before the shared map, so they win. Malay pages have their own field, for addresses on this site only.
- **Pages without links.** Page slugs that never receive contextual links.

## Security
- **Remote publishing.** Turn off XML-RPC (xmlrpc.php answers 403; on Apache a "Block xmlrpc.php" section is kept in .htaccess), turn off application passwords, and allow REST API changes only from a logged-in browser session with a valid nonce. Anonymous requests and all reads are unchanged. Passwords sent through XML-RPC are refused even when XML-RPC is switched back on. `define( 'LQ_SECURITY_LOCK', false );` in wp-config.php turns these three off.
- **New content.** Hold suspected gambling spam, and anything published from outside the editor, as Pending; answer 410 Gone on the address of spam trashed from this tab. The guide pages the theme seeds (`_lq_lang`, `_lq_key`, `_lq_seed_hash`, or a title and slug from the page registry) are exempt.
- **Clean up spam.** Scans posts and pages 100 at a time and moves the selected ones to the trash, never deleting them. Empty spam categories are deleted only when the button is pressed.
- **Passwords and sessions.** Revoke all application passwords; log out all sessions. Lists administrators with last login and application passwords.
- **Attacks seen, Activity, Security log.** XML-RPC requests counted per day, failed logins counted by account name, posts per day, burst publishing, guide pages with gambling links, and the log.

## Tools
- **Run setup.** Creates missing guide pages, links translations, sets the front page, adds the Malay site title if none exists, renames guide pages that are still empty drafts, and fills empty title tags and meta descriptions.
- **Reset settings.** Returns every setting to its default. Pages are untouched.
