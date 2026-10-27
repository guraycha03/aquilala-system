@extends('layouts.sidebar')

@section('title', 'Archive')

@section('content')
<style>
  .archive-page { max-width: 1180px; }
  .archive-head { margin-bottom: 20px; }
  .archive-head h1 { margin: 0; color: #1e2922; font-size: 24px; font-weight: 700; }
  .archive-head p { margin: 5px 0 0; color: #64748b; font-size: 13px; }
  .archive-types { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
  .archive-type { display: block; padding: 18px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; color: inherit; text-decoration: none; transition: .15s ease; }
  .archive-type:hover, .archive-type.active { border-color: #b7cbb7; background: #f7faf6; }
  .archive-type-label { color: #1e2922; font-size: 14px; font-weight: 700; }
  .archive-type-count { display: block; margin-top: 8px; color: #166534; font-size: 25px; font-weight: 800; }
  .archive-type-desc { display: block; margin-top: 4px; color: #64748b; font-size: 12px; line-height: 1.45; }
  .archive-panel { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; }
  .archive-panel-head { display: flex; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid #eef1f4; }
  .archive-panel-head h2 { margin: 0; color: #1a2b1e; font-size: 16px; }
  .archive-panel-head p { margin: 4px 0 0; color: #879a8c; font-size: 12.5px; }
  .archive-table { width: 100%; border-collapse: collapse; }
  .archive-table th, .archive-table td { padding: 13px 20px; border-bottom: 1px solid #f1f5f9; text-align: left; font-size: 13px; }
  .archive-table th { color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
  .archive-table td { color: #334155; }
  .archive-table tr:last-child td { border-bottom: 0; }
  .archive-restore { border: 1px solid #bbf7d0; border-radius: 8px; background: #f0fdf4; color: #15803d; padding: 7px 11px; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
  .archive-restore:hover { background: #dcfce7; }
  .archive-empty { padding: 48px 20px; text-align: center; color: #94a3b8; }
  @media (max-width: 760px) {
    .archive-types { grid-template-columns: 1fr; }
    .archive-panel { overflow-x: auto; }
    .archive-table { min-width: 620px; }
  }
</style>

<div class="archive-page">
  <div class="archive-head">
    <h1>Archive</h1>
    <p>Restore records that were archived instead of permanently removed.</p>
  </div>

  @if(session('success'))
    <div class="page-flash page-flash--success" style="margin-bottom:14px;">{{ session('success') }}</div>
  @endif

  <div class="archive-types">
    @foreach($types as $key => $archiveType)
      <a class="archive-type {{ $type === $key ? 'active' : '' }}" href="{{ route('admin.archive', ['type' => $key]) }}">
        <span class="archive-type-label">{{ $archiveType['label'] }}</span>
        <strong class="archive-type-count">{{ number_format($archiveType['count']) }}</strong>
        <span class="archive-type-desc">{{ $archiveType['description'] }}</span>
      </a>
    @endforeach
  </div>

  <div class="archive-panel">
    <div class="archive-panel-head">
      <div>
        <h2>{{ $types[$type]['label'] }}</h2>
        <p>{{ $types[$type]['description'] }}</p>
      </div>
    </div>

    @if($records->isEmpty())
      <div class="archive-empty">No archived {{ strtolower($types[$type]['label']) }}.</div>
    @else
      <table class="archive-table">
        <thead>
          <tr>
            @if($type === 'users')
              <th>Account</th><th>Role</th><th>Archived</th><th></th>
            @elseif($type === 'members')
              <th>Member</th><th>Contact</th><th>Archived</th><th></th>
            @else
              <th>Reporter</th><th>Crop</th><th>Harvested</th><th>Archived</th><th></th>
            @endif
          </tr>
        </thead>
        <tbody>
          @foreach($records as $record)
            <tr>
              @if($type === 'users')
                <td><strong>{{ $record->name }}</strong><br><span style="color:#64748b;">{{ $record->email }}</span></td>
                <td>{{ ucwords(str_replace('_', ' ', $record->role)) }}</td>
                <td>{{ $record->archived_at?->format('M d, Y') }}</td>
                <td style="text-align:right;"><form method="POST" action="{{ route('admin.archive.users.restore', $record->id) }}">@csrf<button class="archive-restore">Restore</button></form></td>
              @elseif($type === 'members')
                <td><strong>{{ $record->fullName() }}</strong></td>
                <td>{{ $record->email ?: ($record->phone ?: '—') }}</td>
                <td>{{ $record->archived_at?->format('M d, Y') }}</td>
                <td style="text-align:right;"><form method="POST" action="{{ route('admin.archive.members.restore', $record->id) }}">@csrf<button class="archive-restore">Restore</button></form></td>
              @else
                <td>{{ $record->reporterName() }}</td>
                <td>{{ $record->crop_type }}</td>
                <td>{{ $record->harvest_date?->format('M d, Y') }}</td>
                <td>{{ $record->archived_at?->format('M d, Y') }}</td>
                <td style="text-align:right;"><form method="POST" action="{{ route('admin.archive.harvest-reports.restore', $record->id) }}">@csrf<button class="archive-restore">Restore</button></form></td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>
@endsection
