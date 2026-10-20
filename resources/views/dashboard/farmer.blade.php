@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('content')
@php
  $user = auth()->user();
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

  $climateInfo = class_exists('\App\Helpers\SeasonHelper')
      ? \App\Helpers\SeasonHelper::getClimateInfo()
      : ['season' => 'dry', 'label' => 'Dry Season', 'months' => 'February – May'];

  $farmerName = $user?->name ?? 'Farmer';
  $seasonName = $climateInfo['label'] ?? 'Dry Season';
  $seasonMonths = $climateInfo['months'] ?? '';

  // KPI cards — the signed-in farmer's own records (see FarmerDashboardController).
  $dashboardStats = [
      [
          'label'       => 'My Crops',
          'value'       => $myCropsCount ?? 0,
          'description' => 'Crops on your farm',
          'icon'        => 'sprout',
      ],
      [
          'label'       => 'My Cost Quotations',
          'value'       => $costEstimatesCount ?? 0,
          'description' => 'Saved quotations',
          'icon'        => 'calculator',
      ],
      [
          'label'       => 'Harvest Reports',
          'value'       => $harvestReportsCount ?? 0,
          'description' => 'Reports submitted',
          'icon'        => 'file',
      ],
      [
          'label'       => 'Local Stores',
          'value'       => $localStoresCount ?? 0,
          'description' => 'Stores listed in Aquilala',
          'icon'        => 'store',
      ],
  ];
@endphp

<style>
  /* Only what belongs to this page's lower half. The dashboard frame, the
     welcome header, the quick-action panel and the KPI card now come from
     components/dash-top.blade.php and components/kpi-card.blade.php, which
     every role's dashboard shares — so this page can't drift from the
     Store Owner's or the Admin's. */

  /* ── Explore supplies ─────────────────────────────────────────── */
  .fd-explore-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; }
  .fd-viewall {
    display: inline-flex; align-items: center; gap: 4px; padding: 8px; border-radius: 8px;
    font-size: 14px; font-weight: 500; color: #55765B; text-decoration: none; transition: color .15s;
  }
  .fd-viewall:hover { color: #344B38; }
  .fd-viewall:focus-visible { outline: 2px solid #648268; outline-offset: 2px; }
  .fd-viewall svg { width: 16px; height: 16px; }

  .fd-explore-card {
    display: flex; align-items: center; gap: 16px; border-radius: 16px;
    border: 1px solid #E0E5DB; background: #fff; padding: 16px;
    text-decoration: none; color: inherit; box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
  }
  @media (min-width: 640px) { .fd-explore-card { padding: 20px; } }
  .fd-explore-card:hover {
    border-color: #C8D5C5;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  }
  .fd-explore-card:focus-visible { outline: 2px solid #648268; outline-offset: 2px; }
  .fd-explore-icon {
    width: 48px; height: 48px; flex-shrink: 0; border-radius: 12px;
    background: #EDF1E9; color: #648268;
    display: flex; align-items: center; justify-content: center;
  }
  .fd-explore-icon svg { width: 24px; height: 24px; }
  .fd-explore-text { min-width: 0; flex: 1; }
  .fd-explore-text h3 { font-size: 14px; font-weight: 600; color: #344B38; margin: 0; }
  .fd-explore-text p { margin: 4px 0 0 0; font-size: 14px; line-height: 1.35; color: #647263; }
  .fd-explore-arrow {
    width: 20px; height: 20px; flex-shrink: 0; color: #899587;
    transition: transform .15s, color .15s;
  }
  .fd-explore-card:hover .fd-explore-arrow { transform: translateX(2px); color: #47654E; }

  /* ── Dark theme (scoped here; dark-mode.css is generated) ─────── */
  [data-theme="dark"] .fd-viewall { color: #6ee7a0; }
  [data-theme="dark"] .fd-viewall:hover { color: #a7f3d0; }
  [data-theme="dark"] .fd-explore-card { background: #111827; border-color: #334155; }
  [data-theme="dark"] .fd-explore-card:hover { border-color: #4ade80; }
  [data-theme="dark"] .fd-explore-text h3 { color: #d6f5e3; }
  [data-theme="dark"] .fd-explore-text p { color: #9fb3a6; }
  [data-theme="dark"] .fd-explore-icon { background: #16301f; color: #6ee7a0; }
  [data-theme="dark"] .fd-explore-arrow { color: #7d8f86; }
  [data-theme="dark"] .fd-explore-card:hover .fd-explore-arrow { color: #6ee7a0; }
</style>

<div class="fd-page">

    {{-- 1 & 2. Welcome header + quick actions — shared with every dashboard --}}
    <x-dash-top
        eyebrow="Farmer Dashboard"
        :title="$greeting . ', ' . $farmerName"
        :subtitle="$seasonName . ($seasonMonths ? ' &middot; ' . $seasonMonths : '')"
    >

        <x-slot:panelHeading>Ready for your next farming activity?</x-slot:panelHeading>
        <x-slot:panelText>Estimate your input costs or explore supplies from local stores.</x-slot:panelText>

        {{-- Quick actions, ordered shortest label first so the scan on
             phones (one per row) reads short → long. --}}
        <x-slot:actions>
            <a href="{{ route('farmer.products.browse') }}" class="fd-action">
                {{-- Magnifier: the same glyph the Browse Supplies page uses
                     for its product search (24-unit/1.7). --}}
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Browse Supplies</span>
            </a>

            <a href="{{ route('farmer.quotations.request') }}" class="fd-action">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="4" y="2.5" width="16" height="19" rx="2"/>
                    <path d="M8 6h8M8 10h2M14 10h2M8 14h2M14 14h2M8 18h8"/>
                </svg>
                <span>Generate Quotation</span>
            </a>

            <a href="{{ route('farmer.harvests.create') }}" class="fd-action">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                    <path d="M14 2v6h6M8 13h8M8 17h8"/>
                </svg>
                <span>Submit Harvest Report</span>
            </a>
        </x-slot:actions>
    </x-dash-top>

    {{-- 3. Personal KPI cards --}}
    <section class="fd-section">

        <div class="fd-sec-head">
            <h2>My Farming at a Glance</h2>
            <p>A quick look at your farming records and available resources.</p>
        </div>

        <div class="fd-kpis kpi-strip">
            @foreach ($dashboardStats as $stat)
                <x-kpi-card
                    :title="$stat['label']"
                    :value="number_format($stat['value'])"
                    :trendLabel="$stat['description']"
                >
                    <x-slot:icon>
                        @if ($stat['icon'] === 'sprout')
                            <svg viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20v-8"/>
                                <path d="M12 12C12 7 8 4 3 4c0 6 3 10 9 10"/>
                                <path d="M12 10c0-5 4-8 9-8 0 6-3 10-9 10"/>
                            </svg>
                        @elseif ($stat['icon'] === 'calculator')
                            <svg viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <rect x="4" y="2.5" width="16" height="19" rx="2"/>
                                <path d="M8 6h8M8 10h2M14 10h2M8 14h2M14 14h2M8 18h8"/>
                            </svg>
                        @elseif ($stat['icon'] === 'file')
                            <svg viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.7" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                <path d="M14 2v6h6M8 13h8M8 17h8"/>
                            </svg>
                        @elseif ($stat['icon'] === 'store')
                            {{-- Storefront icon.
                                 stroke-width scaled from the source's 2.5:
                                 this renders at 20px on a 100-unit viewBox,
                                 so 2.5 would be a 0.5px hairline. 5 =>
                                 1px effective, matching the sibling cards'
                                 24-unit/1.7 icons. --}}
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 100 100"
                                 fill="none" stroke="currentColor"
                                 stroke-width="5" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 39 L25 19 H75 L80 39"/>
                                <path d="M20 39 C20 47 32 47 32 39 C32 47 44 47 44 39 C44 47 56 47 56 39 C56 47 68 47 68 39 C68 47 80 47 80 39"/>
                                <path d="M24 46 V82 H76 V46"/>
                                <path d="M35 82 V56 H65 V82"/>
                                <path d="M50 56 V82"/>
                                <path d="M39 61 H46"/>
                                <path d="M54 61 H61"/>
                                <path d="M24 82 H76"/>
                            </svg>
                        @endif
                    </x-slot:icon>
                </x-kpi-card>
            @endforeach
        </div>
    </section>

    {{-- 4. Explore supplies --}}
    <section class="fd-section">

        <div class="fd-explore-head">
            <div class="fd-sec-head">
                <h2>Explore Farming Supplies</h2>
                <p>Find local stores and check their listed product prices.</p>
            </div>

            <a href="{{ route('farmer.products.browse') }}" class="fd-viewall">
                View all supplies
                <svg viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </div>

        <a href="{{ route('farmer.products.browse') }}" class="fd-explore-card">
            <div class="fd-explore-icon">
                <svg viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7h18l-2 13H5L3 7Z"/>
                    <path d="M8 7a4 4 0 0 1 8 0"/>
                </svg>
            </div>

            <div class="fd-explore-text">
                <h3>Fertilizers and Farm Inputs</h3>
                <p>Browse available products and compare listed prices from local stores.</p>
            </div>

            <svg class="fd-explore-arrow"
                 viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </a>

    </section>

</div>
@endsection
