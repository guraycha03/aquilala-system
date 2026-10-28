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

## Store owners — typed in by hand

`store-info/` holds three copy-paste sheets (`01-maya-santos.txt`,
`02-gina-gime.txt`, `03-alo-mantes.txt`) — one per owner, with every value
sitting on its own line so it can be selected and pasted straight into
**Admin → Stores → Add Store**. Dropdown choices are spelled exactly as the
form's options show them (barangay, ownership, status, paper types), the
phone is given as the 9 digits that follow the fixed `+639` prefix, and each
owner's three sample PDFs are matched to their paper-type slots.

`store-owners.txt` is the longer paper version of the same thing: the three
owners field by field with rules and a paper trail, for reference. Store-owner
accounts are never imported or seeded — each one is transcribed from its
offline application, and the owner sets their own password with the emailed
activation code.

Nine PDFs — three per owner (`-maya-santos`, `-gina-gime`, `-alo-mantes`
suffixes on `mayors-permit`, `barangay-clearance`, `dti-certificate`) — act as
each owner's sample paperwork. They are specimen documents marked "SAMPLE —
NOT VALID" with a watermark, a red banner and a footnote, signed by invented
officials in a decorative script font (not any real person's signature). The
dates tell one deliberately old, consistent story per owner: the DTI
certificate carries the year the store was established (2020 / 2014 / 2011),
and the barangay clearance and expired CY2023 mayor's permit were filed
together in January 2023, cross-referencing each other's numbers. They are
not real permits and must be replaced with the owners' actual scans.

## Regenerating

```bash
php demo-data/generate.php
```

The generator is seeded, so it always produces the same files — the harvest
names will always line up with the roster. Crop names and economics mirror
`config/crop_library.php` and the system's own demo seeder, so imported
reports drop straight into the reports, analytics and AI insight pages.
