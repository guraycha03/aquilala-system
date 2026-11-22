<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $report->title }} — {{ $language === 'tl' ? 'Tagalog' : 'English' }}</title>
<style>
  @page { size: A4; margin: 18mm 16mm; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #fff; }
  .sheet { padding: 0; }
  @include('admin.harvests.generated.partials.report-paper-styles')
</style>
</head>
<body>
<div class="sheet">
  @include('admin.harvests.generated.partials.report-paper', [
    'language' => $language,
  ])
</div>
</body>
</html>
