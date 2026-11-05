# NSIC Variety Proof Files (Downloaded Official Datasets)

Location of downloaded official NSIC (National Seed Industry Council) variety registries:

- `database/data/proof/nsic/` — 11 `.xlsx` files, downloaded directly from https://nsic.buplant.da.gov.ph/

## Mapping to Aquilala crop library categories

| Crop library category | Aquilala crops | NSIC proof file | NSIC registry page |
|---|---|---|---|
| Cereal | Rice, Corn | `nsic-rice.xlsx`, `nsic-corn.xlsx` | Rice: https://nsic.buplant.da.gov.ph/downloadables/nsic-registered_rice.xlsx ; Corn/Sorghum/Wheat: https://nsic.buplant.da.gov.ph/downloadables/nsic%20-%20registered%20corn_sorghum_wheat.xlsx |
| Root Crop | Sweet Potato, Cassava, Gabi/Taro, Potato, Carrot | `nsic-rootcrops.xlsx` (and PhilRice/DA guidance) | https://nsic.buplant.da.gov.ph/downloadables/nsic-rootcrops.xlsx |
| Fiber Crop | Abaca | `nsic-fiber.xlsx` | https://nsic.buplant.da.gov.ph/downloadables/nsic-fiber.xlsx |
| Legume | Mungbean, Soybean, Peanut | `nsic-fieldlegume.xlsx` | https://nsic.buplant.da.gov.ph/downloadables/nsic-fieldlegume.xlsx |
| Fruit | Coconut, Banana, Mango, Pineapple, Papaya, Jackfruit, Calamansi, Watermelon, Cacao, Coffee | `nsic-fruit.xlsx`, `nsic-plantation.xlsx`, `nsic-vegetable.xlsx` (watermelon patch also in root/field) | Fruit: https://nsic.buplant.da.gov.ph/downloadables/nsic-registered_fruit_crops.xlsx ; Plantation (cacao/coffee/coconut): https://nsic.buplant.da.gov.ph/downloadables/nsic-plantation.xlsx |
| Cash Crop | Sugarcane | `nsic-sugarcane.xlsx` | https://nsic.buplant.da.gov.ph/downloadables/nsic-sugarcane.xlsx |
| Ornamental | (used only in variety lists?): | `nsic-ornamental.xlsx` | https://nsic.buplant.da.gov.ph/downloadables/nsic-registered_ornamental_crops.xlsx |
| Vegetable |  | `nsic-vegetable.xlsx` | https://nsic.buplant.da.gov.ph/downloadables/nsic-vegetable.xlsx (fallback URL) |

## Notes for reviewers

1. Aquilala’s varieties (inside `config/crop_library.php`) are a **subset** of the NSIC lists, curated for Bulan/Sorsogon temperate constraints and group labelling.
2. All crop names shown in the interface can be traced back to `config/crop_library.php` → `CropLibraryService` → `FarmingKnowledgeService`.
3. NSIC registry page: https://nsic.buplant.da.gov.ph/registry.php
4. DA-BPI production-guide landing page (for general crop practices): https://buplant.da.gov.ph/production-guide/
5. Bulan climate/planting calendar proof: `docs/bulan-sorsogon-farming-references.md`
6. Internal progression from registry → curated JSON is documented in `CropSeeder.php` and `config/crop_library.php` (header comment block).