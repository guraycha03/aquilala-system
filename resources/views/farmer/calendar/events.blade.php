@extends('layouts.sidebar')

@section('title', 'Association Events')

@section('content')
@include('partials.admin-tabs', [
  'tabs' => [
    ['key' => 'calendar', 'label' => 'Calendar', 'url' => route('farmer.calendar.index')],
    ['key' => 'events',   'label' => 'Events',   'url' => route('farmer.calendar.events')],
  ],
  'activeTab' => 'events',
])

<div class="header-panel" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 22px 26px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04);">
  <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Association Events</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5; max-width: 680px;">
    The association's full schedule — future and past. Check what's coming up, or look back at what already happened.
  </p>
</div>

@include('partials.association-event-list', [
  'upcoming' => $upcoming,
  'past'     => $past,
])
@endsection
