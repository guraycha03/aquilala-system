# demo-data

Ready-made files for loading the association's real working data into the
system — no demo accounts and no seeders involved. The officer uploads these
from the admin pages, reviews the preview, and confirms.

## The files

| File | What it holds | Rows |
|---|---|---|
| `members.csv` / `members.xlsx` | The association's member roster | 50 members |
| `harvest-reports.csv` / `harvest-reports.xlsx` | Harvest history from 2020 onward | ~718 reports |

**Members** — realistic Filipino names, `@gmail.com` addresses, and the
barangay as the address (Inararan, J.P. Laurel, Aquino — all in Bulan,
Sorsogon). Member numbers follow `AQ-2012-001` … `AQ-2012-050`, the year the
association was registered.

**Harvest reports** — one row per harvest, member names taken **exactly**
from the members file (the importer matches by name, so any typo fails the
row for review). Each row carries a **Seed Variety** drawn from the system's
own crop registry (`config/crop_library.php`) — `NSIC Rc 222 (Tubigan 18)`
for rice, `Golden Baby F1` for corn, `VSP-15 (orange-fleshed)` for camote —
with a slice of rows on `Local / General variety` for farm-saved seed. The
history follows the pattern the association described: each member farms
1–2 harvests in either or both seasons (wet = Oct–Dec, dry = Feb–May),
roughly 80% of seasons are farmed, rice dominates with a realistic spread of
vegetables, and whole seasons occasionally align on one crop — the
free-seed-release effect.

## Upload order

1. **Members first** — Admin → Members → *Import Members*. Preview, then
   confirm. Importing members does not create any login accounts.
2. **Harvest reports second** — Admin → Harvest Reports → *Import Records*.
   Preview shows which rows matched a member (Ready) and which failed with a
   "did you mean" hint (Rows to fix). Only Ready rows are imported.

Both importers accept CSV and XLSX, and both stop before writing anything
until you confirm the preview.

## Regenerating

```bash
php demo-data/generate.php
```

The generator is seeded, so it always produces the same files — the harvest
names will always line up with the roster. Crop names and economics mirror
`config/crop_library.php` and the system's own demo seeder, so imported
reports drop straight into the reports, analytics and AI insight pages.
