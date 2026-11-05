# Aquilala — Farming Knowledge Data Inventory

This document is the single index of where the system's farming knowledge and data
come from. Keep it updated whenever a new dataset is added.

## Runtime knowledge (used by the AI assistant and UI)

| Data | File | Role | Attribution |
|---|---|---|---|
| Crop library (yields, maturity days, seasons, varieties, aliases, categories) | `config/crop_library.php` | Single source of truth for crops and agronomy. | `meta.sources` in that file: NSIC/PhilRice registered rice varieties; DA-BPI crop production guides; Bicol climate calendar; Provincial agronomy references (Sorsogon/Bicol). Per-category URLs in `meta.sources_by_category` (e.g. https://buplant.da.gov.ph/production-guide/, https://bicol.da.gov.ph/, https://hvcdp.da.gov.ph/production-manuals/). |
| Crop library PHP accessor | `app/Services/CropLibraryService.php` | Typed accessor for the config above. | Service layer only. |
| Bulan address data (barangays) | `config/bulan.php` | 63 barangays of Bulan, Sorsogon. | PSGC 0506203000, cross-checked with PhilAtlas / brgy.to. |
| Philippine climate calendar for Bicol/Bulan | `app/Helpers/SeasonHelper.php` | Wet/dry season, sub-season, temperature, humidity, rainfall-based recommendations. | Embedded climate knowledge; aligned with PAGASA/Bicol monsoon calendar. |
| Farming knowledge for the assistant | `app/Services/FarmingKnowledgeService.php` | Facts, planting steps, general guidance and crop names used by Aquilala assistant. | Consumes `CropLibraryService` (config/crop_library.php). |
| AI assistant approved knowledge | `config/crop_library.php` + `SeasonHelper` | Only these two are approved sources for farming answers; everything else is stated as general practice. | Same attributions as above. |
| NSIC rice variety registry (source dataset) | `database/data/nsic-registered_rice.xlsx` | Source for NSIC-registered varieties mentioned in crop library meta. | DA-PhilRice National Seed Industry Council registry. |
| Seasonal crop suggestion engine | `app/Services/CropService.php` (`currentMonth()`, `cropsForSeason()`) | Turns month → seasonal crop list. | Consumes crop library + Bicol climate. |

## Population / seeding helpers

| Data | File | Role |
|---|---|---|
| Crop seeder | `database/seeders/CropSeeder.php` | Seeds the `crops` table from `config/crop_library.php`. |
| Planting stages / crop maturity | `config/crop_maturity.php` | Maturity data used by yield/estimator views. |
| Migrations | `database/migrations/*` | Schema only; no runtime dataset. |

## External reference links (new)

- `docs/bulan-sorsogon-farming-references.md` — curated, location-specific official links (PSA, DA, PhilRice, PAGASA, climate data) and an notes section used to guide AI behavior.

## Sample / demo data (NOT a knowledge source)

- `demo-data/*.csv`, `demo-data/*.xlsx` — sample Farmer/member/product/harvest data for local testing only.
- `storage/app/private/imports/...` — user uploads (ignored runtime data).
- These must never be cited as authoritative references.

## Documentation used during development (not runtime data)

- `agriculture-reference.txt` — supporting reference text gathered for the study.
- `product-data.txt` — product/category notes for the catalog.
- `project-context.txt` — thesis/project documentation; not consumed by the app or AI.

## Runtime DB tables generated from the above

- `decision_formulas` — links crops → Aquilala products and rates; feeds product recommendations and cost estimates.
- `crops` — widened from `config/crop_library.php` via `CropSeeder`.
- `products`, `farmers`, `harvest_reports` — operational system data, NOT agronomy knowledge.

## Where to point at during a defense

- **"Where do crop yields/maturity come from?"** → `config/crop_library.php` → `meta.sources` and `meta.sources_by_category`.
- **"Where is Bulan climate encoded?"** → `app/Helpers/SeasonHelper.php` plus `docs/bulan-sorsogon-farming-references.md`.
- **"What is the only approved source for the AI?"** → `FarmingKnowledgeService`, which reads only `CropLibraryService` (`config/crop_library.php`) and `SeasonHelper`.
- **"What is built-in vs. external?"** → Local curated library + climate helper; official links live in `meta.sources_by_category` and the `docs/bulan-sorsogon-farming-references.md` file.