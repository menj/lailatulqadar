# Languages

The theme serves English and Malay without a plugin.

## Addresses

| Page | English | Malay |
|---|---|---|
| Home | `/` (the static front page) | `/ms/` (a page with the "Language home page" template) |
| Any guide page | `/<slug>/` | `/ms/<slug>/`, a child page of the Malay home |

WordPress builds the Malay addresses from the page hierarchy, so no rewrite rules are involved.

## How a page knows its language

- `_lq_lang` post meta: `en` or `ms`, set by Run setup.
- A page beneath the Malay home is Malay even without the meta.
- Search, archive and not-found views follow the address (`/ms/...`) or `?lang=ms`.

## Pairing

The page map (option `lailatulqadar_pages`) pairs each guide page with its translation, and `_lq_translation` post meta stores the pair on both pages. The language switcher and the hreflang links read the pairing.

## What changes on Malay pages

- `<html lang="ms-MY">`
- Site name from the Languages tab (default "Lailatulqadar")
- Menus, reading path, footer, planner and messages in Malay
- Search forms add `?lang=ms`; the results screen uses Malay wording

## Adding a Malay page outside the guide

Create the page with the Malay home as its parent. It is treated as Malay automatically. To pair it with an English page, set `_lq_translation` on both pages to each other's ID.
