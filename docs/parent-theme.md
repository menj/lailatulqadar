# Parent theme: Twenty Twenty-Five

Lailatulqadar is a child of Twenty Twenty-Five and is tested against version 1.5. Twenty Twenty-Five must stay installed; it need not be active.

## What the child takes from the parent

| From the parent | Status |
|---|---|
| Font sizes (small to xx-large) and spacing scale (20 to 80) | Used as they are; the child defines no sizes or spacing of its own |
| Colour slugs (base, contrast, accent-1 to accent-6) | Same slugs, the child's own colours |
| Stylesheet (`style.min.css`: link underline weight, focus outlines, navigation fixes) | Loaded before the child's `front.css`, with the parent's own version number |
| Block styles: Display, Subtitle, Annotation, post terms, Section 1 to 5; the checkmark list style | Available; they use presets the child defines |
| Patterns (98) and pattern categories | Available in the inserter |
| Template parts other than header and footer (vertical header, large-title header, footer variants, sidebar) | Available in the Site Editor |
| Post-format block binding | Available |

## What the child replaces or holds back

| Parent feature | What the child does | Why |
|---|---|---|
| Templates: front page, page, page without title, single, archive, search, home, index, 404; header and footer parts | Own versions of every one | So every view carries the child's design; before 1.8.0 the parent's `home.html` and `page-no-title.html` still applied |
| Fonts Manrope and Fira Code | Not loaded; body text uses EB Garamond and code blocks use the child's monospace stack | The child's font list replaces the parent's |
| Style variations (Evening, Noon, Dusk and five more), colour palettes and typography presets | Hidden from the Site Editor's Styles panel | Each replaces the child's palette or fonts, and the typography presets point at font files the child does not load. The filter `lq_allow_parent_variations` restores them |
| `twentytwentyfive_enqueue_styles()` | Replaced by a child version | The parent's version stamps its stylesheet with the active theme's version, which in a child theme is the child's; the child's version uses the parent's number, so browsers refresh the parent stylesheet only when the parent changes |

## After a parent update

1. Check the parent's changelog for new templates or template parts; add a child version of any new template a visitor can reach.
2. Check `theme.json` in the parent for new preset references (font families especially) and give the child a matching rule where the child lacks the preset.
3. Look at a page, a post, an archive and search results in both colour schemes.
