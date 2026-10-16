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
  await page.waitForTimeout(1200);

  out.state = await page.evaluate(() => {
    const info = (el) => {
      if (!el) return null;
      const cs = getComputedStyle(el);
      return {
        cls: el.className,
        bg: cs.backgroundColor,
        color: cs.color,
        border: cs.borderColor,
        appearance: cs.appearance,
        scheme: cs.colorScheme,
        inline: el.getAttribute('style'),
      };
    };
    const sels = [...document.querySelectorAll('.cf-select')];
    const inputs = [...document.querySelectorAll('.cf-input')];
    return {
      theme: document.documentElement.getAttribute('data-theme'),
      htmlScheme: getComputedStyle(document.documentElement).colorScheme,
      bodyScheme: getComputedStyle(document.body).colorScheme,
      darkCssLoaded: [...document.styleSheets].some(s => (s.href || '').includes('dark-mode')),
      bar: info(document.querySelector('.cf-bar')),
      selects: sels.map(s => ({ id: s.id, ...info(s) })),
      searchInput: info(document.querySelector('[data-cf-search-input]')),
      priceInput: info(document.querySelector('[data-cf-price]')),
      // any other select-like dropdowns on the page
      otherSelects: [...document.querySelectorAll('select')].filter(s => !s.classList.contains('cf-select')).map(s => ({ id: s.id, cls: s.className, ...info(s) })),
    };
  });
  await page.screenshot({ path: 'tmp_browse_light.png', fullPage: false });
  return out;
}
