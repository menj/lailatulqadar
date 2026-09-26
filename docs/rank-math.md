# Rank Math

Rank Math is the site's SEO plugin. When it is active, the theme hands over everything Rank Math controls and keeps only what Rank Math does not do.

## Who controls what

| Area | With Rank Math active | Theme-only (no SEO plugin) |
|---|---|---|
| Title tag | Rank Math | Theme (`_lq_seo_title`) |
| Meta description | Rank Math | Theme (`_lq_meta_description`) |
| Robots meta, canonical, Open Graph, Twitter cards | Rank Math | WordPress core (robots, canonical) |
| Structured data (JSON-LD) | Rank Math | Theme (Organization, WebSite, WebPage, BreadcrumbList, Article, ProfilePage) |
| Breadcrumb trail on the page | Rank Math's trail when its breadcrumbs are on; otherwise the theme's plain trail with no structured data | Theme |
| XML sitemap | Rank Math (`/sitemap_index.xml`; the WordPress sitemap redirects to it) | WordPress core, trimmed to pages |
| Search Console and Bing verification | Rank Math (General Settings > Webmaster Tools) | Theme (Search tab) |
| Search appearance box in the editor | Hidden; Rank Math's box is used | Theme |
| Favicon, logo mark, hreflang, language switcher, Ramadan dates, night window | Theme, unchanged | Same |

## What Run setup does with Rank Math fields

It fills `rank_math_title`, `rank_math_description` and `rank_math_focus_keyword` only when a field is empty, or still holds a default the theme wrote in an earlier release. Anything edited in Rank Math stays as it is.

Dated wording uses two Rank Math variables the theme registers, so the year rolls over inside Rank Math and appears in its snippet preview:

- `%lq_ramadan_year%`: Gregorian year of the current or next Ramadan (2027, then 2028 from the day after Eid)
- `%lq_ramadan_hijri%`: Hijri year (1448, then 1449)

## Recommended Rank Math settings

- **General Settings > Breadcrumbs:** on. Separator ›. Home label "Home"; the Malay breadcrumb label follows Rank Math's own setting, so keep "Home" or set a bilingual label.
- **Titles & Meta > Global > Schema:** leave FAQ and HowTo unused; Google no longer supports them.
- **Titles & Meta > Pages:** schema type Article for the ten guide articles; WebPage (or none) for the front page, HTML sitemap, contact and planner pages.
- **Titles & Meta > Local SEO / Knowledge Graph:** Organization; name as the site title; logo `wp-content/themes/lailatulqadar/images/lailatulqadar-mark-512.png` (or your own); author details on the About page.
- **Sitemap Settings:** include pages; exclude posts, categories, tags and authors while the site has no blog.
- **General Settings > Webmaster Tools:** Google and Bing codes.
- **Search results:** keep them noindexed.
