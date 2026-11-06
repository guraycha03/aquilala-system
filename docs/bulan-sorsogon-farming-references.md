# Bulan, Sorsogon — Farming References (Current & Local)

Scope: Bulan, Sorsogon (Bicol Region, Philippines). These links are the official/primary
references the Aquilala assistant should stay aligned with for local climate, crops, and
agriculture services. Only data traceable to these sources (or the association's own
catalog/decision formulas) should be presented as fact.

## Climate (Bulan, Sorsogon)

- Source: [Wikipedia — Bulan, Sorsogon (Climate table)](https://en.wikipedia.org/wiki/Bulan,_Sorsogon#Climate)
  - Modelled climate normals via Meteoblue: https://www.meteoblue.com/en/weather/forecast/modelclimate/bulan_philippines_1722985
  - Temperature: mean daily max 27-31 °C; mean daily min 21-25 °C (year-round tropical).
  - Precipitation: ~1,308 mm/year; wetter May-October, drier December-April.
  - Average rainy days: ~226/year.

## Location / Geography

- [Wikipedia — Bulan, Sorsogon](https://en.wikipedia.org/wiki/Bulan,_Sorsogon)
- [PhilAtlas — Bulan, Sorsogon](https://www.philatlas.com/luzon/r05/sorsogon/bulan.html)
- PSA geographic code: https://psa.gov.ph/classification/psgc/barangays/0506203000

## Demographics / Economy (official)

- [Philippine Statistics Authority (PSA)](https://psa.gov.ph/)
- [PSA — 2024 Census of Population (POPCEN)](https://psa.gov.ph/content/2024-census-population-popcen-population-counts-declared-official-president)
- Poverty incidence / local poverty estimates: https://psa.gov.ph/content/poverty-incidence-pi

## Agriculture / Extension (official)

- Department of Agriculture (DA) national: https://www.da.gov.ph/
- DA Bicol Region field office: https://bicol.da.gov.ph/
- Philippine Rice Research Institute (PhilRice): https://www.philrice.gov.ph/
- International Rice Research Institute (IRRI) Philippines: https://www.irri.org/
- Agricultural Training Institute (ATI): https://ati.da.gov.ph/

## Climate services

- PAGASA (national weather/climate): https://www.pagasa.dost.gov.ph/
- Meteoblue climate model for Bulan: https://www.meteoblue.com/en/weather/forecast/modelclimate/bulan_philippines_1722985

## Association context (internal)

- Bulan, Sorsogon is a tropical lowland municipality (elevation ~8 m; max elevation ~190 m).
- Major crops observed in the area include rice, copra/coconut, and abaca (source: Wikipedia economy section).
- Aquilala association crops are governed by `config/crop_library.php` and `SeasonHelper` — the only crop reference the assistant uses.

## Field conditions for the cost quotation form (Bulan, Sorsogon)

Soils in Bulan, Sorsogon and the Bicol region vary from lowland clay loam in
paddy areas to sandy coastal/riverine pockets and organic/peaty spots in swales.
Weather risks follow the two-season Bicol calendar encoded in
`app/Helpers/SeasonHelper.php` (wet: June-January/Habagat-Amihan; dry: February-May).

Weather-risk handling follows `CostQuotationEngine::getWeatherContextLabel()` and
is limited to keys the engine actually understands (`normal`, `low_rain`,
`typhoon`, `el_nino`). Season-based filtering on the form prevents contradictions:
the UI only offers storm/waterlogging-related choices in the wet season and
drought/heat-related choices in the dry season.

References (real, location-relevant):
- DA-BPI production guides (soil/crop recommendations by region): https://buplant.da.gov.ph/production-guide/
- DA Bicol Region field office (Sorsogon programs): https://bicol.da.gov.ph/
- PAGASA climate advisories (PAGASA Bicol): https://www.pagasa.dost.gov.ph/
- Bicol moonsoon climate: CALD/CALA — https://en.wikipedia.org/wiki/Bulan,_Sorsogon#Climate

Soil-type adjustments in the engine:
- Sandy  -> +15% fertilizer (nutrient leaching; agronomic baseline)
- Peat / Organic -> adds agricultural lime for pH balance
- Clay loam / other -> no adjustment (standard water retention)

## Notes for AI behavior
- Flag temperate crops (strawberry, cabbage as a cool-season crop, etc.) as not ideal in open fields in Bulan unless grown in cool/elevated areas or controlled environments.
- Always confirm the farmer's crop variety/intent before giving planting steps or seasonal picks.