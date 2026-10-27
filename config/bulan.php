<?php

/**
 * The association's home municipality: Bulan, Sorsogon.
 *
 * Both admin-side create forms that collect an address are anchored here.
 * City and Province arrive filled in and read-only — the operator never
 * types them — and the barangay is the only address part they pick, from
 * this list.
 *
 * Source: Philippine Standard Geographic Code (PSGC), Municipality of
 * Bulan, PSGC 0506203000 — 63 barangays as of 31 July 2025 (55 barangays
 * plus the 8 Poblacion zones). Cross-checked against PhilAtlas and
 * brgy.to, which carry the identical set.
 *
 * Editing an existing record is deliberately not restricted: legacy rows
 * may hold any address and the edit forms stay free-text.
 */
return [

    'province' => 'Sorsogon',

    'city' => 'Bulan',

    'barangays' => [
        'A. Bonifacio',
        'Abad Santos',
        'Aguinaldo',
        'Antipolo',
        'Beguin',
        'Benigno S. Aquino',
        'Bical',
        'Bonga',
        'Butag',
        'Cadandanan',
        'Calomagon',
        'Calpi',
        'Cocok-Cabitan',
        'Daganas',
        'Danao',
        'Dolos',
        'E. Quirino',
        'Fabrica',
        'G. Del Pilar',
        'Gate',
        'Inararan',
        'J. Gerona',
        'J.P. Laurel',
        'Jamorawon',
        'Lajong',
        'Libertad',
        'M. Roxas',
        'Magsaysay',
        'Managanaga',
        'Marinab',
        'Montecalvario',
        'N. Roque',
        'Namo',
        'Nasuje',
        'Obrero',
        'Osmeña',
        'Otavi',
        'Padre Diaz',
        'Palale',
        'Quezon',
        'R. Gerona',
        'Recto',
        'Sagrada',
        'San Francisco',
        'San Isidro',
        'San Juan Bag-o',
        'San Juan Daan',
        'San Rafael',
        'San Ramon',
        'San Vicente',
        'Santa Remedios',
        'Santa Teresita',
        'Sigad',
        'Somagongsong',
        'Taromata',
        'Zone I Pob.',
        'Zone II Pob.',
        'Zone III Pob.',
        'Zone IV Pob.',
        'Zone V Pob.',
        'Zone VI Pob.',
        'Zone VII Pob.',
        'Zone VIII Pob.',
    ],

];
