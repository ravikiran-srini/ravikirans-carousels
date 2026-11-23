"""Render a carousel PDF from a JSON spec.
Usage: python3 tools/render.py spec.json out.pdf [--png-dir DIR]
Spec: {"label": "Red Hat", "cta": "ravikirans.com/category/red-hat", "slides": [...]}
Slide types: cover, stat, cards, steps, code, cta (cta is always last and is added automatically).
In any text, *word* is shown in red."""
import sys, json, html, re, pathlib
sys.path.insert(0, str(pathlib.Path(__file__).parent))
from brand import icon_svg
from playwright.sync_api import sync_playwright

W, H = 1080, 1440
HERE = pathlib.Path(__file__).parent
CSS = (HERE / 'style.css').read_text()
DOTS = '<div class="hd"><i style="background:#FF5F57"></i><i style="background:#FEBC2E"></i><i style="background:#28C840"></i></div>'
NAME = 'Ravikiran Srinivasulu · ravikirans.com'

def t(s):
    return re.sub(r'\*(.+?)\*', r'<span class="red">\1</span>', html.escape(s)).replace('\n', '<br>')

def chrome(n, total, label):
    return (f'<div class="top"><span class="lg">{icon_svg(52)}{html.escape(label)}</span><span>{n:02d} / {total:02d}</span></div>'
            f'<div class="foot"><span>{NAME}</span><span class="sw">{"Swipe →" if n < total else ""}</span></div>'
            f'<div class="bar" style="width:{round(n / total * 100)}%"></div>')

def code_block(lines):
    out = []
    for ln in lines:
        e = html.escape(ln)
        if ln.startswith('$ '): e = '<span class="p">$</span>' + e[1:]
        elif ln.startswith('#'): e = f'<span class="d">{e}</span>'
        elif ln.strip() and not ln.startswith(' '): e = f'<span class="c">{e}</span>'
        out.append(e)
    return f'<div class="term">{DOTS}<pre>' + '\n'.join(out) + '</pre></div>'

def body(s, spec):
    k = s['type']
    if k == 'cover':
        x = f'<h1>{t(s["title"])}</h1>'
        if s.get('sub'): x += f'<div class="sub">{t(s["sub"])}</div>'
        if s.get('code'): x += code_block(s['code'])
        return x
    if k == 'stat':
        return (f'<h2>{t(s["title"])}</h2><div class="big">{html.escape(s["value"])}'
                f'<span style="font-size:110px;color:#9AA4B8;-webkit-text-fill-color:#9AA4B8">{html.escape(s.get("suffix",""))}</span></div>'
                f'<div class="sub">{t(s["text"])}</div>')
    if k == 'cards':
        c = ''.join(f'<div class="card"><h3>{html.escape(g["title"])}</h3>' +
                    ''.join(f'<div class="row"><code>{html.escape(a)}</code>{html.escape(b)}</div>' for a, b in g['rows']) + '</div>'
                    for g in s['groups'])
        return f'<h2 style="font-size:74px">{t(s["title"])}</h2>{c}'
    if k == 'steps':
        c = ''.join(f'<div class="step"><div class="n">{i}</div><p>{html.escape(a)}<span>{html.escape(b)}</span></p></div>'
                    for i, (a, b) in enumerate(s['steps'], 1))
        return f'<h2>{t(s["title"])}</h2>{c}'
    if k == 'code':
        x = f'<h2>{t(s["title"])}</h2>' + code_block(s['code'])
        if s.get('note'): x += f'<div class="sub">{t(s["note"])}</div>'
        return x
    raise ValueError(k)

SOC = ('<div class="soc"><svg width="64" height="64" viewBox="0 0 64 64"><rect width="64" height="64" rx="15" fill="#0A0E1A"/><text x="32" y="44" font-family="Inter" font-weight="800" font-size="30" text-anchor="middle" fill="#fff">in</text></svg>linkedin.com/company/ravikirans</div>'
       '<div class="soc"><svg width="64" height="64" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="#0A0E1A"/><text x="35" y="52" font-family="Inter" font-weight="800" font-size="46" text-anchor="middle" fill="#fff">f</text></svg>facebook.com/ravikiran.srinivasulu</div>'
       '<div class="soc"><svg width="64" height="64" viewBox="0 0 64 64"><rect y="8" width="64" height="48" rx="14" fill="#0A0E1A"/><polygon points="26,20 26,44 46,32" fill="#fff"/></svg>youtube.com/c/ravikirans</div>')

def build_html(spec):
    slides = spec['slides']
    total = len(slides) + 1
    parts = []
    for i, s in enumerate(slides, 1):
        if i == 1:
            parts.append(f'<section class="slide"><div class="top"><span class="lg">{icon_svg(52)}{html.escape(spec["label"])}</span><span>01 / {total:02d}</span></div>'
                         f'<div class="body" style="gap:50px">{body(s, spec)}</div>'
                         f'<div class="foot"><span>{NAME}</span><span class="sw">Swipe →</span></div>'
                         f'<div class="bar" style="width:{round(100 / total)}%"></div></section>')
        else:
            parts.append(f'<section class="slide l">{chrome(i, total, s.get("label", spec["label"]))}<div class="body" style="gap:44px">{body(s, spec)}</div></section>')
    parts.append(f'<section class="slide l">{chrome(total, total, "Free study guides")}<div class="body" style="gap:40px">'
                 f'<h2 style="font-size:78px">{t(spec.get("cta_title", "Free study guides on ravikirans.com."))}</h2>'
                 f'<div class="cta" style="font-size:44px">{html.escape(spec["cta"])}</div>'
                 f'<div class="card"><h3>Follow Ravikiran Srinivasulu</h3>{SOC}</div></div></section>')
    return f"<!doctype html><html><head><meta charset='utf-8'><style>{CSS}</style></head><body>{''.join(parts)}</body></html>"

def main():
    spec = json.loads(pathlib.Path(sys.argv[1]).read_text())
    out = pathlib.Path(sys.argv[2])
    png = pathlib.Path(sys.argv[sys.argv.index('--png-dir') + 1]) if '--png-dir' in sys.argv else None
    hp = out.with_suffix('.html')
    hp.write_text(build_html(spec))
    with sync_playwright() as p:
        b = p.chromium.launch(executable_path='/usr/bin/google-chrome', args=['--no-sandbox'])
        pg = b.new_page(viewport={'width': W, 'height': H})
        pg.goto(f'file://{hp.resolve()}')
        pg.wait_for_timeout(500)
        pg.pdf(path=str(out), width=f'{W}px', height=f'{H}px', print_background=True)
        if png:
            png.mkdir(parents=True, exist_ok=True)
            for i, el in enumerate(pg.query_selector_all('.slide'), 1):
                el.screenshot(path=str(png / f'{i}.png'))
        b.close()
    hp.unlink()

if __name__ == '__main__':
    main()
