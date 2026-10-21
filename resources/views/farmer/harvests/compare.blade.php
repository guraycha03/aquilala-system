@extends('layouts.sidebar')

@section('title', 'Estimate vs Actual Harvest')

@section('content')

  <div class="header-panel" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px 26px; margin-bottom: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Estimate vs Actual Harvest</h1>
      <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5; max-width: 640px;">
        How close was the yield estimate recorded at planting to what your fields actually produced? Filter by year, season or crop.
      </p>
    </div>
    <a href="{{ route('farmer.harvests.create') }}"
       style="display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 20px; border-radius: 10px; background: #4A7A54; color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(74,122,84,0.25);">
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Submit Harvest Report
    </a>
  </div>

  @include('partials.harvest-compare', [
    'compareRoute' => 'farmer.harvests.compare',
    'isAdmin' => false,
  ])
@endsection
