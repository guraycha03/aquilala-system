@extends('layouts.sidebar')

@section('title', 'Association Calendar')

@section('content')

@include('partials.calendar-theme')

<div class="cal-page-head">
  <div>
    <h1>Association Calendar</h1>
    <p>The same shared schedule members see — input releases, water scheduling, meetings and seminars. Only dates from today onward can be scheduled.</p>
  </div>
  <button type="button" class="cal-btn cal-btn--primary js-open-add-event">
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Event
  </button>
</div>

@if(session('success'))
  <div class="cal-flash">{{ session('success') }}</div>
@endif

<div class="cal-split">
  <div class="cal-split-today">
    @include('partials.today-events-card', ['todayEvents' => $todayEvents])
  </div>
  <div class="cal-split-cal">
    @include('partials.association-calendar', [
      'year'       => $year,
      'month'      => $month,
      'monthLabel' => $monthLabel,
      'prevUrl'    => $prevUrl,
      'nextUrl'    => $nextUrl,
      'events'     => $events,
    ])
  </div>
  <aside class="cal-split-events">
    @include('partials.month-events-panel', [
      'year'       => $year,
      'month'      => $month,
      'monthLabel' => $monthLabel,
      'events'     => $events,
    ])
  </aside>
</div>

@include('partials.event-modal')
@endsection
