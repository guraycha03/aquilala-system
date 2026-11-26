@extends('layouts.sidebar')

@section('title', 'Association Events')

@section('content')

@include('partials.calendar-theme')

<div class="cal-page-head">
  <div>
    <h1>Association Events</h1>
    <p>The full shared schedule — what's coming up and what has already happened. This is a read-only view for members.</p>
  </div>
</div>

@include('partials.association-event-list', [
  'upcoming' => $upcoming,
  'past'     => $past,
])
@endsection
