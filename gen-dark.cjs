// Generates public/css/dark-mode.css.
//  Part 1 (handcrafted): base surfaces, forms, tables, scrollbars, modals.
//  Part 2 (generated): inline style="..." color overrides.
//  Part 3 (generated): class rules scraped from every <style> block in views.
// Mapping: light backgrounds -> frosted dark glass; dark text -> light text;
// light borders -> dark borders (green tints keep a green border).
const fs = require('fs');
const path = require('path');

const VIEWS = path.join(__dirname, 'resources', 'views');
const OUT = path.join(__dirname, 'public', 'css', 'dark-mode.css');

const FROST_BG = 'rgba(15, 23, 42, 0.72)';
const BLUR = 'backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px)';

// ── color helpers ──────────────────────────────────────────────────────────
function parseColor(v) {
    v = String(v).trim().toLowerCase();
    if (v === 'white') return { r: 255, g: 255, b: 255, a: 1 };
    if (v === 'black') return { r: 0, g: 0, b: 0, a: 1 };
    if (v === 'transparent') return null;
    let m = v.match(/^#([0-9a-f]{3})$/);
    if (m) return { r: parseInt(m[1][0]+m[1][0],16), g: parseInt(m[1][1]+m[1][1],16), b: parseInt(m[1][2]+m[1][2],16), a: 1 };
    m = v.match(/^#([0-9a-f]{6})$/);
    if (m) return { r: parseInt(m[1].slice(0,2),16), g: parseInt(m[1].slice(2,4),16), b: parseInt(m[1].slice(4,6),16), a: 1 };
    m = v.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/);
    if (m) return { r: +m[1], g: +m[2], b: +m[3], a: m[4] === undefined ? 1 : +m[4] };
    return null;
}

function firstColorToken(value) {
    const m = String(value).match(/(^|[\s(,])(#(?:[0-9a-f]{3}|[0-9a-f]{6})\b|rgba?\([^)]*\)|white|black|transparent)(?=$|[\s;)])/i);
    return m ? m[2] : null;
}

function lightness(c) {
    const mx = Math.max(c.r, c.g, c.b), mn = Math.min(c.r, c.g, c.b);
    return (mx + mn) / 2 / 255;
}

function saturation(c) {
    const mx = Math.max(c.r, c.g, c.b), mn = Math.min(c.r, c.g, c.b);
    if (mx === mn) return 0;
    const l = (mx + mn) / 2 / 255;
    const d = (mx - mn) / 255;
    return l > 0.5 ? d / (2 - (mx + mn) / 255) : d / ((mx + mn) / 255);
}

function hue(c) {
    const r = c.r/255, g = c.g/255, b = c.b/255;
    const mx = Math.max(r, g, b), mn = Math.min(r, g, b);
    if (mx === mn) return 0;
    const d = mx - mn;
    let h;
    if (mx === r) h = ((g - b) / d) % 6;
    else if (mx === g) h = (b - r) / d + 2;
    else h = (r - g) / d + 4;
    return ((h * 60) + 360) % 360;
}

// 0.66 (not 0.72): HSL lightness 0.71 colors like rgb(172,242,121) still read
// as light to the eye (perceptual lum ~0.74) and must be converted.
const isLightBg = c => c.a > 0.25 && lightness(c) > 0.66;
const isDarkText = c => c.a > 0.25 && lightness(c) < 0.62;
const chroma = c => (Math.max(c.r, c.g, c.b) - Math.min(c.r, c.g, c.b)) / 255;

function darkTintFor(c) {
    const h = hue(c), s = saturation(c);
    return `hsl(${Math.round(h)}, ${Math.round(s * 100)}%, 17%)`;
}

function textColorFor(c) {
    const h = hue(c), s = saturation(c);
    if (s < 0.12) return '#e2e8f0';
    if (h >= 70 && h < 170) return '#a7f3d0';   // greens / brand headings
    if (h < 20 || h >= 340) return '#fca5a5';   // reds
    if (h >= 20 && h < 68) return '#fcd34d';    // ambers / oranges
    if (h >= 190 && h < 265) return '#93c5fd';  // blues
    if (h >= 265 && h < 320) return '#c4b5fd';  // purples
    return '#e2e8f0';
}

function borderColorFor(c) {
    const h = hue(c), s = saturation(c);
    if (s > 0.15 && h >= 70 && h < 170) return '#166534';
    if (s > 0.15 && (h < 20 || h >= 340)) return '#7f1d1d';
    return '#334155';
}

// ── map a declaration → output declarations (or null) ─────────────────────
function mapDecl(prop, value) {
    prop = prop.trim().toLowerCase();
    value = value.trim();
    if (!value || value.includes('{{') || value.includes('{!!')) return null;
    if (prop.startsWith('--')) return null;
    if (value.includes('!important')) value = value.replace(/\s*!important\s*$/i, '');

    const bgProps = ['background', 'background-color'];
    const borderProps = ['border', 'border-color', 'border-top', 'border-right', 'border-bottom', 'border-left'];
    const textProps = ['color', 'fill', 'stroke'];
    const borderOnly = ['border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'];

    if (bgProps.includes(prop)) {
        if (/gradient\(/i.test(value)) {
            const tok = firstColorToken(value);
            const c = tok ? parseColor(tok) : null;
            if (c && isLightBg(c)) return { 'background-image': 'none', 'background-color': FROST_BG, 'backdrop-filter': 'blur(14px)', '-webkit-backdrop-filter': 'blur(14px)' };
            return null;
        }
        const tok = firstColorToken(value);
        if (!tok) return null;
        const c = parseColor(tok);
        if (!c || !isLightBg(c)) return null;
        // Saturated light tints (badges, alerts) keep their hue as a dark tint;
        // near-white surfaces become frosted dark glass.
        const out = chroma(c) > 0.08 ? darkTintFor(c) : FROST_BG;
        const rule = { 'background-color': out };
        if (out === FROST_BG) {
            rule['backdrop-filter'] = 'blur(14px)';
            rule['-webkit-backdrop-filter'] = 'blur(14px)';
        }
        return rule;
    }

    if (borderProps.includes(prop) || borderOnly.includes(prop)) {
        const tok = firstColorToken(value);
        if (!tok) return null;
        const c = parseColor(tok);
        if (!c) return null;
        if (prop === 'border' || prop.startsWith('border-') && !prop.endsWith('color')) {
            // keep width/style, only if a light color is present
            if (lightness(c) > 0.68) return { 'border-color': borderColorFor(c) };
            return null;
        }
        if (lightness(c) > 0.68) return { 'border-color': borderColorFor(c) };
        return null;
    }

    if (textProps.includes(prop)) {
        const tok = firstColorToken(value);
        if (!tok) return null;
        const c = parseColor(tok);
        if (!c || !isDarkText(c)) return null;
        const out = textColorFor(c);
        return { [prop]: out };
    }

    if (prop === 'accent-color') {
        const c = parseColor(firstColorToken(value) || '');
        if (c && isDarkText(c)) return { 'accent-color': '#4ade80' };
        return null;
    }

    return null;
}

// ── parse a stylesheet into rules (handles @media/@supports nesting) ──────
function stripComments(css) { return css.replace(/\/\*[\s\S]*?\*\//g, ''); }

function parseRules(css, atChain = []) {
    const rules = [];
    css = stripComments(css);
    let i = 0;
    while (i < css.length) {
        while (i < css.length && /\s/.test(css[i])) i++;
        if (i >= css.length) break;
        const open = css.indexOf('{', i);
        if (open === -1) break;
        const semi = css.indexOf(';', i);
        if (semi !== -1 && semi < open) { // at-rule without block (@import;)
            i = semi + 1; continue;
        }
        const selector = css.slice(i, open).trim();
        let depth = 1, j = open + 1;
        while (j < css.length && depth > 0) {
            if (css[j] === '{') depth++;
            else if (css[j] === '}') depth--;
            j++;
        }
        const body = css.slice(open + 1, j - 1);
        if (/^@(media|supports|layer|container)\b/i.test(selector)) {
            rules.push(...parseRules(body, [...atChain, selector]));
        } else if (/^@/.test(selector)) {
            // @keyframes, @font-face, ... skip
        } else if (!selector.includes('{{') && !selector.includes('{!!')) {
            rules.push({ at: atChain, selector, body });
        }
        i = j;
    }
    return rules;
}

function declsOf(body) {
    const out = [];
    for (const piece of body.split(';')) {
        const idx = piece.indexOf(':');
        if (idx === -1) continue;
        const prop = piece.slice(0, idx).trim();
        const value = piece.slice(idx + 1).trim();
        if (prop) out.push([prop, value]);
    }
    return out;
}

const SKIP_SELECTOR = new Set(['body', 'html', '*', ':root', 'html, body', 'body, html']);

// ── collect ────────────────────────────────────────────────────────────────
const classRules = new Map();   // key -> { at, sel, decls }
const attrRules = new Map();    // raw substring -> merged decls

function addClass(key, at, sel, decls, selParts) {
    const cur = classRules.get(key) || { at, sel, decls: {}, selParts };
    Object.assign(cur.decls, decls);
    classRules.set(key, cur);
}

function addAttr(raw, decls) {
    const cur = attrRules.get(raw) || {};
    Object.assign(cur, decls);
    attrRules.set(raw, cur);
}

function walk(dir) {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, e.name);
        if (e.isDirectory()) walk(full);
        else if (e.name.endsWith('.blade.php')) scan(fs.readFileSync(full, 'utf8'));
    }
}

function scan(src) {
    // 1) inline style attributes
    const attrRe = /style\s*=\s*"([^"]*)"|style\s*=\s*'([^']*)'/g;
    let m;
    while ((m = attrRe.exec(src))) {
        const css = m[1] !== undefined ? m[1] : m[2];
        const declRe = /([a-zA-Z-]+)(\s*:\s*)([^;"']+)/g;
        let d;
        while ((d = declRe.exec(css))) {
            const raw = d[0].trim();
            const decls = mapDecl(d[1], d[3]);
            if (!decls) continue;
            addAttr(raw, decls);
        }
    }
    // 2) <style> blocks
    const styleRe = /<style[^>]*>([\s\S]*?)<\/style>/gi;
    while ((m = styleRe.exec(src))) {
        for (const rule of parseRules(m[1])) {
            const parts = rule.selector.split(',').map(s => s.trim())
                .filter(s => s && !SKIP_SELECTOR.has(s));
            if (!parts.length) continue;
            const merged = {};
            let any = false;
            for (const [prop, value] of declsOf(rule.body)) {
                const mapped = mapDecl(prop, value);
                if (mapped) { Object.assign(merged, mapped); any = true; }
            }
            if (any) addClass(JSON.stringify(rule.at) + '|' + parts.join(','), rule.at, parts.join(', '), merged, parts);
        }
    }
}

walk(VIEWS);

// ── part 1: handcrafted base ───────────────────────────────────────────────
const base = `/* ==========================================================================
   Aquilala display theme — dark mode (generated: do not hand-edit; run
   gen-dark.cjs to regenerate). Every rule is scoped to [data-theme="dark"]
   on <html>, so light mode is untouched.
   ========================================================================== */

html[data-theme="dark"] { color-scheme: dark; }

/* Base surfaces */
[data-theme="dark"] body { background: #0b1220 !important; color: #cbd5e1 !important; }
[data-theme="dark"] .main-content { background: #0b1220 !important; color: #cbd5e1 !important; }
[data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3,
[data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6 { color: #d6f5e3 !important; }
[data-theme="dark"] a { color: #6ee7a0; }
[data-theme="dark"] hr { border-color: #334155 !important; }
[data-theme="dark"] p { color: #b6c2bb; }

/* Forms */
[data-theme="dark"] input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
[data-theme="dark"] select,
[data-theme="dark"] textarea {
  background: #111827 !important;
  background-image: none !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
  border-color: #334155 !important;
  color: #e5e7eb !important;
}
[data-theme="dark"] input::placeholder,
[data-theme="dark"] textarea::placeholder { color: #6b7280 !important; }
[data-theme="dark"] input:focus, [data-theme="dark"] select:focus,
[data-theme="dark"] textarea:focus { border-color: #4ade80 !important; }
[data-theme="dark"] option { background: #111827; color: #e5e7eb; }
[data-theme="dark"] label { color: #9fb3a6; }

/* Tables */
[data-theme="dark"] table { background: #111827 !important; color: #cbd5e1 !important; }
[data-theme="dark"] thead { background: #1f2937 !important; }
[data-theme="dark"] th { background: #1f2937 !important; color: #9ca3af !important; border-color: #334155 !important; }
[data-theme="dark"] td { border-color: #334155 !important; }
[data-theme="dark"] tbody tr { border-color: #334155 !important; }
[data-theme="dark"] tbody tr:nth-child(even) { background: #0f172a !important; }
[data-theme="dark"] tbody tr:hover { background: #1e293b !important; }

/* Scrollbars */
[data-theme="dark"] ::-webkit-scrollbar { width: 10px; height: 10px; }
[data-theme="dark"] ::-webkit-scrollbar-track { background: #0b1220; }
[data-theme="dark"] ::-webkit-scrollbar-thumb { background: #334155; border-radius: 8px; }
[data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: #475569; }

/* Generic buttons & modals */
[data-theme="dark"] .btn-secondary,
[data-theme="dark"] .btn-outline { background: #1f2937 !important; border-color: #334155 !important; color: #cbd5e1 !important; }
[data-theme="dark"] .modal-content,
[data-theme="dark"] .modal-card,
[data-theme="dark"] .dialog-card { background: #111827 !important; color: #cbd5e1 !important; border-color: #334155 !important; }
[data-theme="dark"] .modal-header,
[data-theme="dark"] .modal-footer { border-color: #334155 !important; }

/* Chart-containing panels: solid dark instead of frosted — the frost's
   backdrop-filter composites the layer and can blur <canvas> charts.
   (Color matches frost-over-body so the look is unchanged.) */
html[data-theme="dark"] .dash-panel,
html[data-theme="dark"] .ana-panel {
  background: #0e1627 !important;
  background-image: none !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
  border-color: #334155 !important;
}

/* Frosted popovers / floating panels keep a solid shadow for depth */
[data-theme="dark"] .cal-pop { box-shadow: 0 14px 34px rgba(0,0,0,.5) !important; }

/* Sidebar active-item curve wedges: the ::before/::after box-shadow paints the
   page background so the pill blends into content. In dark mode it must match
   the dark body bg (#0b1220), not the light #f3f6f4, for both the base rules
   and any sidebar-theme --sb-active-bg override. */
html[data-theme="dark"] .nav-links li:not(.nav-dropdown):has(a.active)::before {
  box-shadow: 10px 10px 0 10px #0b1220 !important;
}
html[data-theme="dark"] .nav-links li:not(.nav-dropdown):has(a.active)::after {
  box-shadow: 10px -10px 0 10px #0b1220 !important;
}

/* Active nav pill: same solid color as the dark page background (not frost —
   frost over the green sidebar reads as a lighter muddy dark). The pill and
   its curve wedges then look like the content bg carved into the sidebar. */
html[data-theme="dark"] .nav-links li a.active {
  background-color: #0b1220 !important;
  background-image: none !important;
  backdrop-filter: none !important;
  -webkit-backdrop-filter: none !important;
  color: #a7f3d0 !important;
}
`;

// ── part 2/3: emit ─────────────────────────────────────────────────────────
const lines = [base, '\n/* ── Generated: inline style attributes ─────────────────────────────── */\n'];
for (const [raw, decls] of [...attrRules.entries()].sort()) {
    const body = Object.entries(decls).map(([p, v]) => `${p}: ${v} !important`).join('; ');
    lines.push(`[data-theme="dark"] [style*="${raw.replace(/"/g, '\\"')}" i] { ${body} }\n`);
}
lines.push('\n/* ── Generated: class rules from view <style> blocks ───────────────── */\n');
for (const { at, sel, decls, selParts } of [...classRules.values()].sort((a, b) => a.sel.localeCompare(b.sel))) {
    const body = Object.entries(decls).map(([p, v]) => `${p}: ${v} !important`).join('; ');
    // Scope EVERY selector in the list, not just the first — otherwise the
    // unprefixed tail (e.g. ".cf-select" in "[data-theme="dark"] .cf-input,
    // .cf-select") applies in light mode too.
    const scoped = (selParts && selParts.length ? selParts : [sel])
        .map(p => `[data-theme="dark"] ${p}`).join(', ');
    let out = `${scoped} { ${body} }`;
    for (let k = at.length - 1; k >= 0; k--) out = `${at[k]} { ${out} }`;
    lines.push(out + '\n');
}

fs.writeFileSync(OUT, lines.join(''), 'utf8');
console.log(`Wrote ${OUT}`);
console.log(`  attr rules: ${attrRules.size}, class rules: ${classRules.size}, bytes: ${fs.statSync(OUT).size}`);
