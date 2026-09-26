# Google guidelines audit

Sources supplied by the site owner on 25 September 2026: Google's *Search Engine Optimization Starter Guide* (2010 edition, Creative Commons Attribution 3.0) and *Structured data markup that Google Search supports* (Google Search Central, last updated 15 June 2026).

## Starter Guide checklist

| Guide section | Status on lailatulqadar.guide |
|---|---|
| Unique, accurate page titles | Done: a title tag per page in both languages, 60 characters at most |
| Description meta tag | Done: unique per page, 130 characters at most, ending with a call to action |
| URL structure | Done: short keyword slugs, one URL per document, lower case; each language keeps its own path |
| Easier navigation, breadcrumbs | Done in 1.7.0: visible breadcrumbs on every page, matched by BreadcrumbList data |
| HTML site map and XML Sitemap | Done: /sitemap/ and /ms/peta-laman/; WordPress core XML sitemap, trimmed in 1.7.0 to pages only |
| Useful 404 page | Done: bilingual message and the reading path |
| Quality content | In progress: articles of 1,200 words or more with verified evidence |
| Anchor text | Done: descriptive internal links, underlined in text; 1.7.3 replaced a generic "Read more" column heading and linked the one unlinked entry in the Last Ten Nights table |
| Images: alt text | Done for the theme's illustrations (SVG title and label); apply to any photographs added later |
| Heading tags | Done: one H1 per page (the page title), H2 and H3 for sections |
| robots.txt | Done: WordPress default, with the sitemap line |
| rel="nofollow" | Not needed: comments are closed on guide pages |
| Mobile | Done: responsive layouts tested at phone, tablet and desktop widths |
| Promotion and webmaster tools | From 1.7.3: fields on the Search tab for the Google Search Console and Bing Webmaster Tools verification codes; setup itself happens at launch |
| One URL per document | Done: canonical link on every page; hreflang alternates from the theme between English and Malay |
| 404 status | Done: missing pages return HTTP 404 with the custom page |
| Search result pages kept out of the index | Done: WordPress marks internal search results noindex |

Parts of the 2010 guide no longer apply: the Open Directory Project closed in 2017, Webmaster Tools became Search Console, Google Places became Google Business Profile, and separate mobile sites with mobile DTDs gave way to responsive design, which this theme uses.

## Structured data

Of the types Google lists as supported, three fit a guide site. The theme emits them as one JSON-LD graph per page:

| Type | Where | Notes |
|---|---|---|
| Organization | Every page | Name, URL and logo: the Site Icon when set, otherwise the theme's 512 × 512 mark |
| Breadcrumb | Every page except the front page | Home, then the page, in the page's language |
| Article | The ten guide articles | Headline, description, dates, author (Person), publisher, language; image from the featured image when one is set |
| Profile page | The About page, both languages | From 1.7.3: about the named author (General tab), or the organisation when none is named |

WebSite and WebPage nodes connect them. Types not used, and why:

- **FAQPage and HowTo:** absent from Google's June 2026 list of supported features, so they would produce no rich result. The FAQ page stays a normal article.
- **Q&A:** meant for pages where users submit answers to one question.
- **Event:** meant for events people attend at a place and time; the night is a religious observance without a venue.
- **Speakable:** limited to news content.
- **Image metadata and Image Sitemap:** the site's illustrations are inline SVG drawn by the theme, which Google Images does not index as image files. Revisit if photographs are added.
- **Video:** no video content.

Checked on the test site (1.7.3): every page has one H1, a canonical link and hreflang alternates; images all carry alt text; the missing-page test returned 404; search results carry noindex.

When Yoast, Rank Math or another SEO plugin is active, the theme prints no structured data and leaves the XML sitemap alone, so nothing is duplicated.

Test each template with Google's Rich Results Test after launch.
