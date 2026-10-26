#!/usr/bin/env python3
"""Fetches the guide pages' Wikimedia Commons images and their credits.

bin/commons-images.json names, for each page key, the Commons files chosen
for it, in order (the first is the lead image), with alt text and caption.
An entry may give "artist" where the file page names none (museum
scans). For each file this script asks the Commons API for its own licence
metadata (action=query&prop=imageinfo&iiprop=url|extmetadata|size), and
refuses it unless:

  - the licence is public domain, CC0, CC BY or CC BY-SA;
  - the file is not tagged as a copyright violation or up for deletion.

It then downloads a 1200-pixel copy, saves it as WebP at 1200 and 640
pixels wide in images/commons/ (each under 200 KB), and writes the author,
licence, licence deed and file page to inc/data/images.json, which
inc/images.php reads with json_decode().

    python3 bin/fetch-commons-images.py          # fetch what is missing
    python3 bin/fetch-commons-images.py --force  # fetch everything again

Wikimedia asks for a descriptive User-Agent with contact details and for
clients to back off when rate limited; requests are spaced and a 429 waits
for Retry-After. Needs Pillow with WebP support.
"""
import html
import io
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
UA = {'User-Agent': 'LailatulqadarTheme/1.9.4 (https://lailatulqadar.guide; remotiveasia@gmail.com) python-urllib'}
API = 'https://commons.wikimedia.org/w/api.php?'
OUT = os.path.join(ROOT, 'images', 'commons')
DATA = os.path.join(ROOT, 'inc', 'data', 'images.json')
WIDTHS = (640, 1200)
LIMIT = 200 * 1024
LICENCE = re.compile(r'^(public domain|pd\b|cc0|cc[ -]by(-sa)? \d\.\d)', re.I)
BAD_CATS = re.compile(r'copyright violation|deletion requests|copyvio|speedy deletion', re.I)


def get(url):
    for attempt in range(8):
        try:
            return urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60).read()
        except urllib.error.HTTPError as e:
            wait = int(e.headers.get('Retry-After', '0') or 0) if e.code == 429 else 0
            time.sleep(max(wait, 15 * (attempt + 1)))
        except Exception:
            time.sleep(10)
    return None


def text(meta, name):
    raw = html.unescape(meta.get(name, {}).get('value', ''))
    return re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', '', raw)).strip()


def lookup(titles):
    """Return {title: (page, imageinfo)} for up to 50 titles in one request."""
    raw = get(API + urllib.parse.urlencode({
        'action': 'query', 'format': 'json', 'formatversion': '2', 'titles': '|'.join(titles),
        'prop': 'imageinfo|categories', 'cllimit': 'max', 'iiprop': 'url|extmetadata|size|sha1',
        'iiurlwidth': str(max(WIDTHS))}))
    if raw is None:
        return {}
    query = json.loads(raw)['query']
    alias = {n['to']: n['from'] for n in query.get('normalized', [])}
    out = {}
    for page in query['pages']:
        if 'imageinfo' in page:
            out[alias.get(page['title'], page['title'])] = (page, page['imageinfo'][0])
    return out


def save(blob, base):
    image = Image.open(io.BytesIO(blob)).convert('RGB')
    sizes = {}
    for width in WIDTHS:
        if width > image.width and width != min(WIDTHS) and sizes:
            continue  # never upscale; a small original keeps fewer sizes
        copy = image if image.width <= width else image.resize((width, round(image.height * width / image.width)), Image.LANCZOS)
        path = os.path.join(OUT, '%s-%d.webp' % (base, width))
        for quality in (78, 70, 62, 55, 48):
            copy.save(path, 'WEBP', quality=quality, method=6)
            if os.path.getsize(path) <= LIMIT:
                break
        sizes[width] = copy.size
    return sizes


def write(data):
    with open(DATA, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent='\t')
        f.write('\n')


def main():
    force = '--force' in sys.argv
    plan = json.load(open(os.path.join(ROOT, 'bin', 'commons-images.json'), encoding='utf-8'))
    data = json.load(open(DATA, encoding='utf-8')) if os.path.exists(DATA) and not force else {}
    os.makedirs(OUT, exist_ok=True)
    titles = [item['title'] for items in plan.values() for item in items]
    info = {}
    for i in range(0, len(titles), 50):
        info.update(lookup(titles[i:i + 50]))
        time.sleep(5)
    result, failed = {}, []
    for key, items in plan.items():
        result[key] = []
        for n, item in enumerate(items, 1):
            base = '%s-%d' % (key, n)
            old = next((x for x in data.get(key, []) if x.get('file') == base and x.get('title') == item['title']), None)
            if old and old.get('widths') and all(os.path.exists(os.path.join(OUT, '%s-%d.webp' % (base, w))) for w in old.get('widths', [])):
                result[key].append(old)
                continue
            found = info.get(item['title'])
            if not found:
                failed.append(item['title'] + ' (missing)')
                continue
            page, ii = found
            meta = ii['extmetadata']
            licence = text(meta, 'LicenseShortName')
            cats = ' '.join(c['title'] for c in page.get('categories', []))
            if not LICENCE.match(licence) or BAD_CATS.search(cats):
                failed.append('%s (licence %r)' % (item['title'], licence))
                continue
            blob = get(ii.get('thumburl') or ii['url'])
            if blob is None:
                failed.append(item['title'] + ' (download)')
                continue
            sizes = save(blob, base)
            big = sizes[max(sizes)]
            artist = item.get('artist') or text(meta, 'Artist') or 'Unknown author'
            result[key].append({
                'file': base,
                'title': item['title'],
                'lead': n == 1,
                'alt': item['alt'],
                'caption': item.get('caption', ''),
                'artist': artist[:120],
                'licence': 'Public domain' if re.match(r'^(public domain|pd\b)', licence, re.I) else licence,
                'licence_url': meta.get('LicenseUrl', {}).get('value', ''),
                'source': ii['descriptionurl'],
                'sha1': ii.get('sha1', ''),
                'widths': sorted(sizes),
                'width': big[0],
                'height': big[1],
            })
            print('ok', base, item['title'], flush=True)
            write(dict(data, **result))
            time.sleep(20)  # upload.wikimedia.org rate-limits bursts of downloads
    write(result)
    print('failed:', failed or 'none')
    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
