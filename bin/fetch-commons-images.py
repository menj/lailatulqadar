#!/usr/bin/env python3
"""Fetches the guide pages' images and their credits, from Wikimedia
Commons, Pixabay and Pexels.

bin/commons-images.json (named for its first source) lists, for each page
key, the images chosen for it, in order (the first is the lead image), with
alt text and caption. A Commons entry names its file ("title"); a Pixabay or
Pexels entry gives "source" ("pixabay" or "pexels") and the photo's "id".
An entry may give "artist" where the file page names none (museum
scans). For each file this script asks the Commons API for its own licence
metadata (action=query&prop=imageinfo&iiprop=url|extmetadata|size), and
refuses it unless:

  - the licence is public domain, CC0, CC BY or CC BY-SA;
  - the file is not tagged as a copyright violation or up for deletion.

A Pixabay or Pexels photo is read through that site's API, which needs a
key in the environment: PIXABAY_API_KEY or PEXELS_API_KEY. Never put a key
in a file in the repository. Without its key, an entry is reported as
pending and the rest still run. Its licence is the site's own (Pixabay
Content License, Pexels License), recorded with a link to the terms.

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


def theme_version():
    with open(os.path.join(ROOT, 'style.css'), encoding='utf-8') as f:
        found = re.search(r'^Version:\s*(\S+)', f.read(), re.M)
    return found.group(1) if found else 'dev'


UA = {'User-Agent': 'LailatulqadarTheme/%s (https://lailatulqadar.guide; remotiveasia@gmail.com) python-urllib' % theme_version()}
API = 'https://commons.wikimedia.org/w/api.php?'
OUT = os.path.join(ROOT, 'images', 'commons')
DATA = os.path.join(ROOT, 'inc', 'data', 'images.json')
WIDTHS = (640, 1200)
LIMIT = 200 * 1024
LICENCE = re.compile(r'^(public domain|pd\b|cc0|cc[ -]by(-sa)? \d\.\d)', re.I)
BAD_CATS = re.compile(r'copyright violation|deletion requests|copyvio|speedy deletion', re.I)


def get(url, headers=None):
    for attempt in range(8):
        try:
            return urllib.request.urlopen(urllib.request.Request(url, headers=dict(UA, **(headers or {}))), timeout=60).read()
        except urllib.error.HTTPError as e:
            if e.code in (400, 404, 410):
                return None  # permanent: retrying only delays the run
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


STOCK = {
    'pixabay': ('PIXABAY_API_KEY', 'Pixabay Content License', 'https://pixabay.com/service/license-summary/'),
    'pexels': ('PEXELS_API_KEY', 'Pexels License', 'https://www.pexels.com/license/'),
}


def stock(item):
    """Return (artist, page, image url) for a Pixabay or Pexels entry, or a reason it cannot be read."""
    source, pid = item['source'], int(item['id'])
    key = os.environ.get(STOCK[source][0], '')
    if not key:
        return 'no %s in the environment' % STOCK[source][0]
    if source == 'pixabay':
        raw = get('https://pixabay.com/api/?' + urllib.parse.urlencode({'key': key, 'id': pid}))
        hits = json.loads(raw).get('hits', []) if raw else []
        if not hits:
            return 'not found'
        hit = hits[0]
        return hit['user'], hit['pageURL'], hit['largeImageURL']
    raw = get('https://api.pexels.com/v1/photos/%d' % pid, {'Authorization': key})
    if not raw:
        return 'not found'
    photo = json.loads(raw)
    return photo['photographer'], photo['url'], photo['src']['original'] + '?auto=compress&cs=tinysrgb&w=%d' % max(WIDTHS)


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
    with open(os.path.join(ROOT, 'bin', 'commons-images.json'), encoding='utf-8') as f:
        plan = json.load(f)
    data = {}
    if os.path.exists(DATA) and not force:
        with open(DATA, encoding='utf-8') as f:
            data = json.load(f)
    os.makedirs(OUT, exist_ok=True)
    for items in plan.values():
        for item in items:
            if item.get('source') in STOCK:
                item['title'] = '%s:%s' % (item['source'], item['id'])
    titles = [item['title'] for items in plan.values() for item in items if item.get('source') not in STOCK]
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
            if item.get('source') in STOCK:
                got = stock(item)
                if isinstance(got, str):
                    failed.append('%s (%s)' % (item['title'], got))
                    continue
                artist, page_url, image_url = got
                blob = get(image_url)
                if blob is None:
                    failed.append(item['title'] + ' (download)')
                    continue
                sizes = save(blob, base)
                big = sizes[max(sizes)]
                result[key].append({
                    'file': base,
                    'title': item['title'],
                    'lead': n == 1,
                    'alt': item['alt'],
                    'caption': item.get('caption', ''),
                    'alt_ms': item.get('alt_ms', ''),
                    'caption_ms': item.get('caption_ms', ''),
                    'artist': (item.get('artist') or artist)[:120],
                    'licence': STOCK[item['source']][1],
                    'licence_url': STOCK[item['source']][2],
                    'source': page_url,
                    'widths': sorted(sizes),
                    'width': big[0],
                    'height': big[1],
                })
                print('ok', base, item['title'], flush=True)
                write(dict(data, **result))
                time.sleep(2)
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
                'alt_ms': item.get('alt_ms', ''),
                'caption_ms': item.get('caption_ms', ''),
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
