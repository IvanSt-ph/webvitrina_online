"""Offline artifact build only. Requires fonttools==4.55.3 and brotli==1.1.0.
Run from any directory; never boots Laravel or reads database files.
"""
from pathlib import Path
import hashlib
import json
import re
import shutil
import subprocess
from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.pens.recordingPen import DecomposingRecordingPen

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'artifacts/remix-icons'
OUT.mkdir(parents=True, exist_ok=True)
TOKEN = re.compile(r'(?<![a-zA-Z0-9_-])ri-[a-z0-9-]+')
RULE = re.compile(r'\.(ri-[a-z0-9-]+):before\s*\{\s*content:\s*"\\([0-9a-f]+)";\s*\}')
sources = {}
fragments = []
dynamic = []
for directory in ('app', 'resources', 'routes', 'database', 'config', 'public'):
    for path in sorted((ROOT / directory).rglob('*')):
        if not path.is_file() or path.suffix not in ('.php', '.js', '.ts', '.css', '.html'):
            continue
        if directory == 'public' and any(p in ('build', 'vendor', 'storage') for p in path.parts):
            continue
        for number, line in enumerate(path.read_text(encoding='utf-8-sig').splitlines(), 1):
            location = f'{path.relative_to(ROOT).as_posix()}:{number}'
            for token in TOKEN.findall(line):
                if token.endswith('-'):
                    fragments.append({'location': location, 'token': token, 'line': line.strip()})
                else:
                    sources.setdefault(token, []).append(location)
            if re.search(r'<i\b.*(?:\{\{|:class|x-bind:class)|iconClass|className|classList|innerHTML', line):
                dynamic.append({'location': location, 'line': line.strip()})

# Exhaustive outcomes of the two partial class expressions in seller/show.
expanded = ['ri-mail-check-fill', 'ri-mail-line', 'ri-smartphone-fill', 'ri-smartphone-line']
for name in expanded:
    sources.setdefault(name, []).append('dynamic: resources/views/seller/show.blade.php:294,300')
expected_fragments = {'ri-mail-', 'ri-smartphone-'}
assert {x['token'] for x in fragments} <= expected_fragments, 'Review new dynamic prefixes before building'
supplied = set(TOKEN.findall((ROOT / 'remix-icons-used.txt').read_text(encoding='utf-8-sig')))
# The checked-in inventory is retained as historical evidence. Build from the
# current source scan so names replaced by compatibility fixes cannot keep
# stale, unavailable glyphs in the required set.
required = set(sources)
report = {
    'commit': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip(),
    'supplied_tokens': len(supplied), 'supplied_complete': len([n for n in supplied if not n.endswith('-')]),
    'required_count': len(required), 'added_to_supplied': sorted(required - supplied),
    'supplied_not_in_source': sorted(supplied - set(sources)),
    'dynamic_expansions': expanded, 'fragments': fragments,
    'sources': dict(sorted(sources.items())), 'dynamic_review_locations': dynamic, 'versions': {},
}

def signature(font, cp):
    name = font.getBestCmap()[cp]
    glyphs = font.getGlyphSet()
    pen = DecomposingRecordingPen(glyphs)
    glyphs[name].draw(pen)
    return pen.value, font['hmtx'][name]

for version, package in [('3.5.0', 'remixicon-legacy'), ('4.1.0', 'remixicon')]:
    base = ROOT / 'node_modules' / package
    assert json.loads((base / 'package.json').read_text())['version'] == version
    css_path = base / 'fonts/remixicon.css'
    font_path = base / 'fonts/remixicon.woff2'
    css = css_path.read_text(encoding='utf-8')
    rules = list(RULE.finditer(css))
    mapping = {m[1]: int(m[2], 16) for m in rules}
    kept = sorted(required & mapping.keys())
    dest = OUT / version
    dest.mkdir(exist_ok=True)
    # Preserve selectors, utility classes, font-family, display and smoothing.
    header = css[:rules[0].start()]
    header = re.sub(r'@font-face\s*\{.*?\}', '@font-face {font-family:"remixicon";src:url("remixicon-subset.woff2") format("woff2");font-display:swap;}', header, flags=re.S)
    subset_css = header + '\n'.join(m[0] for m in rules if m[1] in kept) + '\n'
    (dest / 'remixicon-subset.css').write_text(subset_css, encoding='utf-8', newline='\n')
    original = TTFont(font_path)
    font = TTFont(font_path, recalcTimestamp=False, recalcBBoxes=False)
    options = subset.Options()
    options.hinting = True
    options.notdef_glyph = True
    options.notdef_outline = True
    options.recalc_timestamp = False
    options.recalc_bounds = False
    options.name_IDs = ['*']
    options.name_languages = ['*']
    sub = subset.Subsetter(options=options)
    sub.populate(unicodes=[mapping[n] for n in kept])
    sub.subset(font)
    font.flavor = 'woff2'
    font.save(dest / 'remixicon-subset.woff2')
    rebuilt = TTFont(dest / 'remixicon-subset.woff2')
    for name in kept:
        cp = mapping[name]
        assert signature(original, cp) == signature(rebuilt, cp), name
    for table, attrs in {'head': ['unitsPerEm'], 'hhea': ['ascent', 'descent', 'lineGap'], 'OS/2': ['sTypoAscender', 'sTypoDescender', 'sTypoLineGap', 'usWinAscent', 'usWinDescent']}.items():
        for attr in attrs:
            assert getattr(original[table], attr) == getattr(rebuilt[table], attr), (table, attr)
    assert {m[1]: int(m[2], 16) for m in RULE.finditer(subset_css)} == {n: mapping[n] for n in kept}
    # Full fallback preserves every original CSS rule and the exact original WOFF2.
    full_css = re.sub(r'@font-face\s*\{.*?\}', '@font-face {font-family:"remixicon";src:url("remixicon-full.woff2") format("woff2");font-display:swap;}', css, flags=re.S)
    (dest / 'remixicon-full.css').write_text(full_css, encoding='utf-8', newline='\n')
    shutil.copyfile(font_path, dest / 'remixicon-full.woff2')
    shutil.copyfile(base / 'License', dest / 'LICENSE.txt')
    assert (dest / 'remixicon-full.woff2').read_bytes() == font_path.read_bytes()
    assert dict((m[1], m[2]) for m in RULE.finditer(full_css)) == dict((m[1], m[2]) for m in rules)
    report['versions'][version] = {
        'original_icons': len(mapping), 'included': len(kept), 'missing': sorted(required - mapping.keys()),
        'codepoints': {n: f'U+{mapping[n]:04X}' for n in kept},
        'bytes': {'original_css': css_path.stat().st_size, 'original_woff2': font_path.stat().st_size,
                  **{p.name: p.stat().st_size for p in dest.iterdir() if p.suffix in ('.css', '.woff2')}},
        'source_sha256': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in (css_path, font_path)},
        'verified': 'CSS codepoints, cmap coverage, decomposed outlines, advance/side bearings, vertical metrics; full fallback byte-identical WOFF2',
    }
    # File preview: no application, database or network required.
    cards = ''.join(f'<div><i class="{n}"></i><span>{n}</span></div>' for n in kept)
    for mode in ('subset', 'full'):
        (dest / f'preview-{mode}.html').write_text('<!doctype html><meta charset="utf-8"><title>Remix '+version+' '+mode+'</title><link rel="stylesheet" href="remixicon-'+mode+'.css"><style>body{font:14px sans-serif}main{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}main div{border:1px solid #ddd;padding:12px}i{font-size:32px;display:block}span{display:block}</style><h1>Remix '+version+' '+mode+'</h1><main>'+cards+'</main>', encoding='utf-8')
(OUT / 'audit.json').write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
(OUT / 'required-icons.txt').write_text('\n'.join(sorted(required)) + '\n', encoding='utf-8')
print(json.dumps({v: {k: data[k] for k in ('included', 'missing', 'bytes')} for v, data in report['versions'].items()}, indent=2))
