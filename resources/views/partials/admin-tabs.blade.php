{{-- Translation-ready pill tab bar for grouped admin pages.
     Pass:
       $tabs       -> array of ['key' => string, 'label' => string, 'url' => string]
       $activeTab  -> the 'key' of the currently selected tab  --}}
@php
  $adminTabs     = $tabs ?? [];
  $adminActiveTab = $activeTab ?? '';
  $tabsStyle     = $tabsStyle ?? (string) optional(auth()->user())->sub_pages_style ?? 'top';
@endphp

@if($tabsStyle === 'sidebar')
  {{-- Sub-page navigation is rendered by the sidebar layout itself ("Side menu" style). --}}
@elseif(!empty($adminTabs))
  <div class="aqua-tabs" role="tablist" aria-label="Section navigation">
    @foreach($adminTabs as $tab)
      <a href="{{ $tab['url'] ?? '#' }}"
         role="tab"
         aria-selected="{{ ($tab['key'] ?? '') === $adminActiveTab ? 'true' : 'false' }}"
         class="aqua-tab {{ ($tab['key'] ?? '') === $adminActiveTab ? 'active' : '' }}">
        {{ $tab['label'] }}
      </a>
    @endforeach
  </div>
  <div class="aqua-tabs-spacer" aria-hidden="true"></div>
@endif