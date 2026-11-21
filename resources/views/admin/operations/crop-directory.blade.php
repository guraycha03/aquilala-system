@extends('layouts.sidebar')

@section('title', 'Crop Directory')

@section('content')
@php
    $fallbackImage = 'images/crop.png';
    $fallbackFull  = public_path($fallbackImage);
    $fallbackUrl   = asset($fallbackImage) . (file_exists($fallbackFull) ? '?v=' . filemtime($fallbackFull) : '');
@endphp

<style>
    .crop-dir-page { max-width: 100%; }
    .crop-dir-header { margin-bottom: 24px; }
    .crop-dir-title { font-size: 26px; font-weight: 800; color: #143d23; letter-spacing: -0.02em; margin-bottom: 4px; }
    .crop-dir-subtitle { color: #526356; font-size: 15px; line-height: 1.5; }

    .category-section { margin-bottom: 32px; }
    .category-title { font-size: 18px; font-weight: 700; color: #1a2b1e; margin: 0 0 14px; padding-bottom: 8px; border-bottom: 2px solid #EDF1E9; display: flex; align-items: center; gap: 10px; }
    .category-icon { width: 28px; height: 28px; border-radius: 8px; background: #EDF1E9; color: #648268; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .category-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .crop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    .crop-card { background: #fff; border: 1px solid #E0E5DB; border-radius: 14px; overflow: hidden; transition: all 0.2s ease; text-decoration: none; color: inherit; display: block; }
    .crop-card:hover { border-color: #C8D5C5; box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .crop-thumb { aspect-ratio: 4/3; background: #F5F2EB; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .crop-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .crop-card:hover .crop-thumb img { transform: scale(1.05); }
    .crop-thumb svg { width: 48px; height: 48px; stroke: #C8D5C5; fill: none; }
    .crop-info { padding: 14px 16px; }
    .crop-name { font-size: 14px; font-weight: 700; color: #1a2b1e; margin: 0 0 4px; }
    .crop-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 11.5px; color: #64748b; }
    .crop-meta span { background: #F0FDF4; padding: 2px 8px; border-radius: 4px; font-weight: 500; }
    .crop-category-label { background: #EDF1E9; color: #648268; }

    .empty-state { text-align: center; padding: 40px 20px; color: #64748b; }
    .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; stroke: #C8D5C5; }
</style>

<div class="crop-dir-page">
    <div class="crop-dir-header">
        <h1 class="crop-dir-title">Crop Directory</h1>
        <p class="crop-dir-subtitle">Complete reference of all crops in the Aquilala system — categories, seasons, maturity, and local names.</p>
    </div>

    @foreach($cropsByCategory as $cat)
        <section class="category-section">
            <h2 class="category-title">
                <span class="category-icon">
                    @switch($cat['name'])
                        @case('Grain')
                            <svg viewBox="0 0 24 24"><path d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10z"/><path d="M12 6v12M6 12h12"/></svg>
                            @break
                        @case('Vegetable')
                            <svg viewBox="0 0 24 24"><path d="M2 12h20M12 2v20"/><path d="M6 6l2 2M16 6l-2 2M6 18l2-2M16 18l-2-2"/></svg>
                            @break
                        @case('Fruit')
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/></svg>
                            @break
                        @case('Root Crop')
                            <svg viewBox="0 0 24 24"><path d="M12 2v8M12 16v6"/><path d="M8 10h8"/><path d="M6 16h12"/><path d="M4 22h16"/></svg>
                            @break
                        @case('Herb & Spice')
                            <svg viewBox="0 0 24 24"><path d="M12 22v-8"/><path d="M9 14h6"/><path d="M7 10h10"/><path d="M5 6h14"/></svg>
                            @break
                        @case('Fiber & Industrial')
                            <svg viewBox="0 0 24 24"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/><path d="M7 6v12"/><path d="M17 6v12"/></svg>
                            @break
                        @case('Legume')
                            <svg viewBox="0 0 24 24"><circle cx="8" cy="16" r="3"/><circle cx="16" cy="16" r="3"/><circle cx="12" cy="8" r="3"/><path d="M8 13V8c0-2.2 1.8-4 4-4s4 1.8 4 4v5"/><path d="M16 13V8c0-2.2-1.8-4-4-4s-4 1.8-4 4v5"/></svg>
                            @break
                    @endswitch
                </span>
                {{ $cat['name'] }} <span style="font-size:12px; font-weight:400; color:#64748b;">({{ $cat['crops']->count() }})</span>
            </h2>

            <div class="crop-grid">
                @foreach($cat['crops'] as $crop)
                    @php
                        $slug = Str::slug($crop->name);
                        $imgRel = "images/crops/{$slug}.jpg";
                        $imgFull = public_path($imgRel);
                        $imgUrl = file_exists($imgFull) ? asset($imgRel) . '?v=' . filemtime($imgFull) : null;
                        if (!$imgUrl) {
                            $imgRel = "images/crops/{$slug}.png";
                            $imgFull = public_path($imgRel);
                            $imgUrl = file_exists($imgFull) ? asset($imgRel) . '?v=' . filemtime($imgFull) : null;
                        }
                        $fallbackFull = public_path('images/crop.png');
                        $fallbackUrl = asset('images/crop.png') . (file_exists($fallbackFull) ? '?v=' . filemtime($fallbackFull) : '');
                    @endphp
                    <a href="{{ route('farmer.crops-directory.show', $crop->id) }}" class="crop-card">
                        <div class="crop-thumb">
                            @if($imgUrl)
                                <img src="{{ $imgUrl }}" alt="{{ $crop->name }}" loading="lazy">
                            @else
                                <svg viewBox="0 0 24 24"><path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2z"/><circle cx="12" cy="12" r="4"/><path d="M12 16v-8"/></svg>
                            @endif
                        </div>
                        <div class="crop-info">
                            <h3 class="crop-name">{{ $crop->name }}</h3>
                            <div class="crop-meta">
                                <span class="crop-category-label">{{ $crop->category->name ?? 'Uncategorized' }}</span>
                                @if($crop->season)
                                    <span>{{ ucfirst($crop->season) }} Season</span>
                                @endif
                                @if($crop->maturity_days)
                                    <span>{{ $crop->maturity_days }} days</span>
                                @endif
                                @if($crop->local_name)
                                    <span>Also: {{ $crop->local_name }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection