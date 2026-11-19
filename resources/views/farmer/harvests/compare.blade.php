@extends('layouts.sidebar')

@section('title', 'Estimate vs Actual Harvest')

@section('content')
<style>
  .page-header { margin-bottom: 20px; }
  .page-title {
    font-size: 26px;
    font-weight: 800;
    color: #143d23;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
  }
  .page-subtitle {
    color: #526356;
    font-size: 15px;
    line-height: 1.5;
  }
</style>

<div class="page-header">
  <h1 class="page-title">Estimate vs Actual Harvest</h1>
  <p class="page-subtitle">How close was the yield estimate recorded at planting to what your fields actually produced? Filter by year, season or crop.</p>
</div>

@include('partials.harvest-compare', [
  'compareRoute' => 'farmer.harvests.compare',
  'isAdmin' => false,
])
@endsection
