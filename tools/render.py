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

def soc(url, name, icon):
    return f'<a class="soc" href="https://{url}" title="{name}">{icon}</a>'

LI_PATH = "M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
FB_PATH = "M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"
SOC = ('<div class="socrow">'
       + soc('linkedin.com/company/ravikirans', 'LinkedIn',
             f'<svg width="120" height="120" viewBox="0 0 24 24"><rect width="24" height="24" rx="3.6" fill="#fff"/><path fill="#0A66C2" d="{LI_PATH}"/></svg>')
       + soc('facebook.com/ravikiran.srinivasulu', 'Facebook',
             f'<svg width="120" height="120" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11.9" fill="#fff"/><path fill="#1877F2" d="{FB_PATH}"/></svg>')
       + soc('youtube.com/c/ravikirans', 'YouTube',
             '<svg width="120" height="120" viewBox="0 0 64 64"><rect y="8" width="64" height="48" rx="14" fill="#FF0000"/><polygon points="26,20 26,44 46,32" fill="#fff"/></svg>')
       + '</div>')

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
                 f'<a class="cta" href="https://{html.escape(spec["cta"])}" style="font-size:44px">{html.escape(spec["cta"])}</a>'
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
