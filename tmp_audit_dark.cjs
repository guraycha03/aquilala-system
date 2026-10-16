const fs = require('fs');
const css = fs.readFileSync('public/css/dark-mode.css', 'utf8');
const idx = css.indexOf('Generated: class rules');
const section = css.slice(css.indexOf('\n', idx) + 1);

function walk(css) {
  const out = [];
  css = css.replace(/\/\*[\s\S]*?\*\//g, '');
  let i = 0;
  while (i < css.length) {
    while (i < css.length && /\s/.test(css[i])) i++;
    if (i >= css.length) break;
    const open = css.indexOf('{', i);
    if (open === -1) break;
    const selector = css.slice(i, open).trim();
    let depth = 1, j = open + 1;
    while (j < css.length && depth > 0) {
      if (css[j] === '{') depth++;
      else if (css[j] === '}') depth--;
      j++;
    }
    const body = css.slice(open + 1, j - 1);
    if (/^@(media|supports|layer|container)\b/i.test(selector)) out.push(...walk(body));
    else if (!/^@/.test(selector)) out.push(selector);
    i = j;
  }
  return out;
}

const bad = [];
const all = walk(section);
for (const sel of all) {
  for (const p of sel.split(',').map(s => s.trim()).filter(Boolean)) {
    if (!p.startsWith('[data-theme="dark"]')) bad.push(p);
  }
}
console.log('class rules:', all.length, '| UNSCOPED:', bad.length);
if (bad.length) console.log(bad.join('\n'));
