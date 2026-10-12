@extends('layouts.sidebar')

@section('title', 'Association Calendar')

@section('content')

<div class="header-panel" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 22px 26px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04);">
  <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Association Calendar</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5; max-width: 680px;">
    Everything happening in the association — free input releases, water scheduling, meetings and seminars. What's on today shows up above the calendar.
  </p>
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
