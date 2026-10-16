export default async function run(page, ui) {
  const out = {};
  await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 15000 });
  await page.fill('input[type="email"], input[name="email"]', 'cruz@gmail.com');
  await page.fill('input[type="password"], input[name="password"]', 'qwerty_Farmer01');
  await Promise.all([
    page.waitForURL(u => !String(u).includes('/login'), { timeout: 30000 }),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForSelector('.sidebar', { timeout: 15000 });

  await page.goto('http://127.0.0.1:8000/farmer/products/browse', { waitUntil: 'load', timeout: 40000 });
  await page.waitForSelector('[data-cf-root]', { timeout: 15000 });
  await page.waitForTimeout(1000);

  const read = () => page.evaluate(() => {
    const info = (el) => {
      if (!el) return null;
      const cs = getComputedStyle(el);
      return { bg: cs.backgroundColor, color: cs.color, border: cs.borderColor };
    };
    return {
      theme: document.documentElement.getAttribute('data-theme'),
      bar: info(document.querySelector('.cf-bar')),
      selects: [...document.querySelectorAll('.cf-select')].map(s => info(s)),
      search: info(document.querySelector('[data-cf-search-input]')),
      price: info(document.querySelector('[data-cf-price]')),
    };
  });

  out.light = await read();
  await page.screenshot({ path: 'tmp_browse_light.png', fullPage: false });

  await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
  await page.waitForTimeout(300);
  out.dark = await read();

  await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'light'));
  out.restored = (await read()).theme;
  return out;
}
