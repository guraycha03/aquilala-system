@extends('layouts.sidebar')

@section('title', 'Association Calendar')

@section('content')

@include('partials.calendar-theme')

<div class="cal-page-head">
  <div>
    <h1>Association Calendar</h1>
    <p>One shared schedule for the association — input releases, water scheduling, meetings and seminars. What's on today appears above the calendar.</p>
  </div>
</div>

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
@endsection
