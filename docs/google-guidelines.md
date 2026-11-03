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

On 3 November 2026 the owner ruled that the site keeps every schema.org type that describes a page, whether Google lists it as a rich result or not. Each page gets one JSON-LD graph, and a type goes in only when what it describes is on the page.

| Type | Where | Source |
|---|---|---|
| Organization, WebSite with SearchAction | Every page | Site name, Site Icon or the 512 × 512 mark, the search form |
| WPHeader, SiteNavigationElement, WPFooter | Every page | The header guide menu and the footer menu, with each link |
| WebPage, BreadcrumbList | Every page except the front page has a breadcrumb | Title, description, language, lead photograph, `speakable` for the title and the "In brief" box |
| SearchResultsPage, CollectionPage | Search and archive views | The breadcrumb trail |
| Article | The ten guide articles and posts | Headline, dates, author, publisher, word count, abstract, citations, image, translation link |
| ProfilePage, AboutPage | About | The named author, else the organisation |
| ContactPage | Contact | |
| FAQPage, Question, Answer | Pages with three or more H2 questions: the FAQ | The heading and the text below it |
| HowTo, HowToStep | Ordered lists whose items open with a bold step name: start, worship | The list as shown |
| Event, ItemList | Pages that show Ramadan dates: when, last10, planner | `inc/ramadan.php`, the dates the page prints |
| ImageObject | Every photograph and SVG diagram placed | `inc/data/images.json`: credit, licence, source |
| Quotation, Chapter, Book, PropertyValue | Verses and hadith cards | The Arabic text, the translation shown, the footnote's collection, number and grading |
| CreativeWork | Footnotes that name a source | The core footnotes |
| Table | Captioned tables | Caption and header cells |

With Rank Math active, the theme adds these to Rank Math's graph through `rank_math/json_ld` and prints nothing itself. Under another SEO plugin the theme prints no structured data and leaves the XML sitemap alone.

The Events carry no location, because the pages name none, so Google shows no event result for them. The owner keeps them for the dates they record.

`php tests/schema-graph-test.php` checks the graph of every page in both languages.

Test each template with Google's Rich Results Test after launch.
