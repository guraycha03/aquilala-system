export default async function run(page) {
  await page.route('**/*', (route) => {
    const u = route.request().url();
    if (u.startsWith('http://127.0.0.1:8137')) return route.continue();
    if (u.includes('cdn.jsdelivr.net')) {
      return route.fulfill({ path: 'C:/my-projects/aquilala-system/tmp_chart.js', contentType: 'application/javascript' });
    }
    return route.abort();
  });

  const ensureLoginForm = async () => {
    for (let i = 0; i < 3; i++) {
      try { await page.waitForSelector('#login-email', { timeout: 15000 }); return; }
      catch { await page.reload({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {}); }
    }
    throw new Error('login form never appeared');
  };
  await ensureLoginForm();

  await page.evaluate(() => {
    const set = (sel, v) => {
      const el = document.querySelector(sel);
      const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
      setter.call(el, v);
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    set('#login-email', 'darkqa_admin@example.com');
    set('#login-password', 'password123');
    document.querySelector('form button[type="submit"]').click();
  });

  await page.waitForURL((u) => !u.includes('/login'), { timeout: 30000 }).catch(() => {});
  await page.waitForFunction(() => document.readyState === 'complete', null, { timeout: 45000 }).catch(() => {});
  await page.waitForSelector('#harvestChart', { timeout: 30000 });
  await page.waitForFunction(() => typeof window.Chart !== 'undefined', null, { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(2500);

  const checks = await page.evaluate(() => {
    const c = document.getElementById('harvestChart');
    const ctx = c.getContext('2d');
    const { width: w, height: h } = c;
    const d = ctx.getImageData(0, 0, w, h).data;
    let mint = 0, opaque = 0;
    for (let i = 0; i < d.length; i += 4) {
      if (d[i + 3] < 200) continue;
      opaque++;
      if (d[i] > 190 && d[i + 1] > 230 && d[i + 2] > 200) mint++;
    }
    const empty = document.getElementById('harvestEmpty');
    const activeA = document.querySelector('.nav-links a.active');
    const li = activeA && activeA.closest('li');
    const before = li && getComputedStyle(li, '::before');
    const after = li && getComputedStyle(li, '::after');
    return {
      theme: document.documentElement.getAttribute('data-theme'),
      emptyHidden: empty ? empty.hidden : 'no-el',
      emptyDisplay: empty ? getComputedStyle(empty).display : 'no-el',
      mint, opaque,
      activeText: activeA ? activeA.textContent.trim().slice(0, 20) : 'no-active',
      beforeShadow: before ? before.boxShadow : null,
      afterShadow: after ? after.boxShadow : null,
    };
  });

  await page.screenshot({ path: 'qa-full-dark.png', fullPage: false });
  const panel = page.locator('.dash-panel').filter({ hasText: 'Harvest Reports' }).first();
  await panel.screenshot({ path: 'qa-chart-dark.png' }).catch(() => {});
  return checks;
}
