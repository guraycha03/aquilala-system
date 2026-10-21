@extends('layouts.sidebar')

@section('title', 'Association Events')

@section('content')

<div class="header-panel" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 22px 26px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Association Events</h1>
    <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5; max-width: 640px;">
      Everything on the shared calendar, past and future. Only dates from today onward can be scheduled.
    </p>
  </div>
  <button type="button" class="js-open-add-event"
          style="display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 20px; border-radius: 10px; background: #4A7A54; color: #fff; border: 0; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(74,122,84,0.25);">
    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Event
  </button>
</div>

@if(session('success'))
  <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: 13.5px; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;">
    {{ session('success') }}
  </div>
@endif

@include('partials.association-event-list', [
  'upcoming' => $upcoming,
  'past'     => $past,
])

@include('partials.event-modal')
@endsection
