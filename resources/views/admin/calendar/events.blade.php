@extends('layouts.sidebar')

@section('title', 'Association Events')

@section('content')

@include('partials.calendar-theme')

<div class="cal-page-head">
  <div>
    <h1>Association Events</h1>
    <p>Everything on the shared calendar, past and future. Only dates from today onward can be scheduled.</p>
  </div>
  <button type="button" class="cal-btn cal-btn--primary js-open-add-event">
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Event
  </button>
</div>

@if(session('success'))
  <div class="cal-flash">{{ session('success') }}</div>
@endif

@include('partials.association-event-list', [
  'upcoming' => $upcoming,
  'past'     => $past,
])

@include('partials.event-modal')
@endsection
