#!/usr/bin/env python3
"""Checks the article seeds in content/<lang>/<key>.json.

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
    return 1 if failed or not count else 0


if __name__ == '__main__':
    sys.exit(main())
