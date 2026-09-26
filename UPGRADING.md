# Upgrading

## From 1.8.1 to 1.9.0

1. If Polylang is installed, deactivate and delete it.
2. Upload the new zip over 1.8.1.
3. Press Run setup (Appearance > Lailatulqadar > Tools). It creates the Malay home at /ms/ and every Malay page beneath it, and pairs each page with its English version.
4. On the Tools tab, publish the drafts marked ready. The Malay articles arrive as they are translated.
5. If Rank Math is active, submit the sitemap again in Search Console once the Malay pages are published.

## From 1.8.0 to 1.8.1

1. Upload the new zip over 1.8.0.
2. Open Appearance > Lailatulqadar > Tools. Review each page marked "ready to publish" in the editor, then press "Publish written drafts".
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

Upload the new zip over 1.7.5. The lamp and the typeset calligraphy appear at once. To use a calligraphy image, upload a licensed transparent PNG and choose it under Appearance > Lailatulqadar > General > Logo calligraphy. If a Site Icon is set in Settings > General, replace it with images/lailatulqadar-mark-512.png or your own lamp artwork, since the Site Icon takes precedence over the theme's favicon.

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
3. Optionally set the author name and profile URL in Appearance > Lailatulqadar > General.
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
2. Check Appearance > Lailatulqadar > Ramadan Dates. The defaults are 8 and 9 February 2027 for Ramadan 1448.
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
2. Open Appearance > Lailatulqadar > Tools and press Run setup. This creates the six new pages as drafts, fills empty title tags and meta descriptions, and renames any guide page that is still an empty draft.
3. Pages that already have content keep their titles and slugs. If the old Worship and Duʿāʾ page already holds content, rename it to "Prayer and Worship on Laylat al-Qadr" with the slug `laylat-al-qadr-prayer` (Malay: "Amalan Malam Lailatulqadar", `amalan-malam-lailatulqadar`) and move any duʿāʾ text to the new Duʿāʾ page. Do the same for Sources ("Hadith and Sources", `hadith-and-sources`).
4. Check the Search tab: every row should show green length badges.

## From 1.0.0 to 1.0.1

Upload the new zip over 1.0.0. No settings change. The front pages pick up the corrected verse automatically because they reference the theme patterns.

## From nothing to 1.9.0 (first install)

1. Install and activate Polylang. In Languages > Languages, add English (code `en`) and Bahasa Melayu (code `ms`). Set English as the default language.
2. In Languages > Settings > URL modifications, tick "The front page URL contains the language code instead of the page name or page id". Without it, `/ms/` redirects to `/ms/utama/`.
3. Make sure Twenty Twenty-Five is installed. Upload `lailatulqadar-1.9.0.zip` in Appearance > Themes > Add New Theme and activate it.
4. Visit any admin screen. Setup runs once and reports the result in Appearance > Lailatulqadar.
5. If Polylang was not ready at step 4, finish steps 1 and 2 and press Run setup in the Tools tab. Existing pages are kept.

The live site currently runs Twenty Twenty-Four with its demonstration content. That content lives in the Twenty Twenty-Four templates and disappears when this theme is activated. The default "Sample Page" remains and can be deleted.

## General rules for later versions

- Upload the new zip over the existing theme. Settings and pages are preserved.
- Read the matching entry in CHANGELOG.md before upgrading.
- Setup never overwrites an existing page. Running it again only fills gaps.
