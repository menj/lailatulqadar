#!/usr/bin/env python3
"""Checks the article seeds in content/<lang>/<key>.json and the page
registry in inc/data/pages.json.

Setup seeds guide pages only from JSON files, read with json_decode() and
checked by lq_article_data() in inc/setup.php, which refuses a file whole if
it is not shaped as expected. This script applies the same rules before a
change is merged, so a seed that setup would silently skip fails here:

  - content/ holds only <lang>/<key>.json files (lang: two letters; key:
    lower-case letters and digits), nothing else;
  - each file is valid UTF-8 JSON: an object with "content" (a string) and,
    optionally, "footnotes" (a list of objects with string "id" and
    "content");
  - every footnote marker (data-fn="...") in the content has its note, and
    every note has a marker.

The registry is read by lq_page_definitions() and used by setup, menus and
the settings screens, which expect every page to carry both language roles
with all their fields; a malformed entry there breaks them. So it must be a
JSON object of page keys, each with "sets" (header, path, footer) and a
"primary" and "secondary" role holding string title, slug, short, seo, meta
and focus. It also enforces two house rules from SSOT.md:

  - a title tag's page part ("seo") is at most 43 characters, counting
    [lq_ramadan] as its four-digit year, so "<page part> | Lailatulqadar"
    stays under 60;
  - a slug has no word a, an or the, and no two pages of a role share one.

    python3 bin/check-content.py    # exit status 1 on any problem
"""
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONTENT = os.path.join(ROOT, 'content')
NAME = re.compile(r'^[a-z]{2}/[a-z0-9]+\.json$')


def check(rel, path):
    try:
        with open(path, encoding='utf-8') as f:
            data = json.load(f)
    except (UnicodeDecodeError, ValueError) as e:
        return ['not valid UTF-8 JSON: %s' % e]
    if not isinstance(data, dict) or not isinstance(data.get('content'), str):
        return ['needs an object with a "content" string']
    notes = data.get('footnotes', [])
    if not isinstance(notes, list):
        return ['"footnotes" must be a list']
    problems = []
    ids = []
    for i, note in enumerate(notes):
        if not (isinstance(note, dict) and isinstance(note.get('id'), str) and isinstance(note.get('content'), str)):
            problems.append('footnote %d needs string "id" and "content"' % (i + 1))
        else:
            ids.append(note['id'])
    marks = set(re.findall(r'\bdata-fn="([^"]+)"', data['content']))
    for fid in sorted(marks - set(ids)):
        problems.append('marker %s has no note' % fid)
    for fid in sorted(set(ids) - marks):
        problems.append('note %s has no marker' % fid)
    return problems


PAGES = os.path.join(ROOT, 'inc', 'data', 'pages.json')
FIELDS = ('title', 'slug', 'short', 'seo', 'meta', 'focus')
SETS = {'header', 'path', 'footer'}
PART_LIMIT = 59 - len(' | Lailatulqadar')  # LQ_TITLE_LIMIT less the site name, as in inc/seo.php.
META_LIMIT = 130


def check_pages():
    try:
        with open(PAGES, encoding='utf-8') as f:
            pages = json.load(f)
    except (OSError, UnicodeDecodeError, ValueError) as e:
        return ['cannot be read as UTF-8 JSON: %s' % e]
    if not isinstance(pages, dict) or not pages:
        return ['needs a non-empty object of page keys']
    problems = []
    slugs = {'primary': {}, 'secondary': {}}
    for key, page in pages.items():
        if not re.match(r'^[a-z0-9]+$', key):
            problems.append('page key %r: lower-case letters and digits only' % key)
        if not isinstance(page, dict):
            problems.append('%s: must be an object' % key)
            continue
        sets = page.get('sets')
        if not isinstance(sets, list) or not set(sets) <= SETS:
            problems.append('%s: "sets" must be a list drawn from %s' % (key, ', '.join(sorted(SETS))))
        for role in ('primary', 'secondary'):
            entry = page.get(role)
            if not isinstance(entry, dict):
                problems.append('%s: missing the "%s" role' % (key, role))
                continue
            missing = [f for f in FIELDS if not isinstance(entry.get(f), str)]
            if missing:
                problems.append('%s/%s: needs string %s' % (key, role, ', '.join(missing)))
                continue
            slug = entry['slug']
            if not re.match(r'^[a-z0-9]+(-[a-z0-9]+)*$', slug):
                problems.append('%s/%s: slug %r is not a lower-case, hyphenated slug' % (key, role, slug))
            elif {'a', 'an', 'the'} & set(slug.split('-')):
                problems.append('%s/%s: slug %r contains a, an or the' % (key, role, slug))
            if slug in slugs[role]:
                problems.append('%s/%s: slug %r is also used by %s' % (key, role, slug, slugs[role][slug]))
            slugs[role][slug] = key
            part = re.sub(r'\[lq_ramadan[^\]]*\]', '0000', entry['seo'])
            if len(part) > PART_LIMIT:
                problems.append('%s/%s: title tag page part is %d characters, %d at most: %s' % (key, role, len(part), PART_LIMIT, entry['seo']))
            meta = re.sub(r'\[lq_ramadan[^\]]*\]', '0000', entry['meta'])
            if len(meta) > META_LIMIT:
                problems.append('%s/%s: meta description is %d characters, %d at most' % (key, role, len(meta), META_LIMIT))
    return problems


def main():
    failed = 0
    count = 0
    for base, dirs, files in os.walk(CONTENT):
        dirs.sort()
        for name in sorted(files):
            path = os.path.join(base, name)
            rel = os.path.relpath(path, CONTENT).replace(os.sep, '/')
            problems = ['not a seed file: use content/<lang>/<key>.json'] if not NAME.match(rel) else check(rel, path)
            count += 1
            for problem in problems:
                print('content/%s: %s' % (rel, problem))
            failed += bool(problems)
    print('%d file(s) checked, %d with problems' % (count, failed))
    registry = check_pages()
    for problem in registry:
        print('inc/data/pages.json: %s' % problem)
    print('inc/data/pages.json: %s' % ('%d problem(s)' % len(registry) if registry else 'ok'))
    return 1 if failed or registry or not count else 0


if __name__ == '__main__':
    sys.exit(main())
