# Theme blocks

Both blocks render on the server and appear in the Theme category of the inserter.

## Guide menu (`lailatulqadar/guide-menu`)

Attribute `set`:
- `header`: short labels for the main pages, marking the current page.
- `path`: every guide page in reading order as a numbered list. Editors see a notice when no page is published; visitors see nothing.

Attribute `chapters` (boolean, default off; "Split into chapters" in the block settings): with `set` `path`, the list is split under the headings in `inc/data/path-chapters.json`, and the numbers run on from one chapter to the next. Each chapter names its page keys in reading order and a heading for each language role. If the path and the chapters do not line up, for instance after a page joins the path, the plain list is shown until the file is updated.
- `footer`: Sources, About, Sitemap and Contact, the footer note, and the copyright line.
- `sitemap`: every published guide page as a plain list; used on the HTML sitemap pages.

The list follows the visitor's language and includes published pages only.

### The front page as one story

The front page (`patterns/front-en.php`, `patterns/front-ms.php`) has two sections, in this order. The verse hero opens it with Q 97:3, the title and the lede: the claim that one night is better than a thousand months. "Read the guide" then carries the reader through the reading path in 4 chapters, which follow the order of `inc/data/pages.json`:

| Chapter | Pages | Job in the story |
|---|---|---|
| What the night is (Mengenal Lailatulqadar) | Start Here, What Is, Sūrat al-Qadr | The question, answered from the Qurʾān |
| When to look for it (Waktu untuk mencarinya) | When Is, The Last Ten Nights, Signs | The evidence on its timing |
| How to keep it (Cara menghidupkannya) | Prayer and Worship, Duʿāʾ, Night Planner | The reader's next step |
| Questions and evidence (Soalan dan dalil) | FAQ, Hadith and Sources, The Islamic Dilemma | Objections and the sources to test the guide by |

The reading order itself is fixed in `SSOT.md`; the chapters only group it.

## Language switcher (`lailatulqadar/language-switcher`)

Links to the current page in the other language, using the labels from the Languages tab, or to the other language's home when the page has no published translation. Renders nothing until the Malay home exists. The footer part sets `context` to `footer`, which names its landmark "Footer language links" ("Pautan bahasa di kaki halaman") so it differs from the header's "Language" ("Bahasa").

## Logo mark (`lailatulqadar/logo-mark`)

The logo lockup beside the title: a Ramadan lantern with an onion dome and arched window, and the Arabic calligraphy of ليلة القدر, then a thin rule. The calligraphy is the image chosen on the General tab, recoloured to the scheme's gold, or the words typeset in Arslan Wessam B when no image is chosen.

## Light and dark switch (`lailatulqadar/scheme-toggle`)

A round button in the header that switches between the night and dawn schemes and remembers the choice in the visitor's browser. Hidden when the Colours tab says so.

## Breadcrumbs (`lailatulqadar/breadcrumbs`)

Home (Utama in Malay), then the current page. Placed above the title in the page template; its trail matches the BreadcrumbList structured data.

## Odd nights dates (`lailatulqadar/odd-nights`)

Evenings of the odd nights for both possible starts of the current or next Ramadan, each named by its Hijri date. Option: add the expected Eid row. Updates itself every year.

## Arabic text

Three Arabic paragraph styles keep the text types apart:

- **Qurʾān verse**: KFGQPC Hafs only.
- **Hadith or supplication**: Arslan Wessam A.
- **Arabic quotation (display)**: Arslan Wessam B, centred, for a single quotation set large.

Any other Arabic in body text renders in Arslan Wessam A automatically.

Qurʾānic text renders in KFGQPC Hafs and in no other face. In the editor, choose the "Qurʾān verse" style on a paragraph; in HTML, use the class `lq-quran` with `lang="ar" dir="rtl"`. Copy the verse from the KFGQPC Hafs text (quran.com `text_qpc_hafs`); ordinary Arabic typing lacks the Mushaf orthography this face expects.
