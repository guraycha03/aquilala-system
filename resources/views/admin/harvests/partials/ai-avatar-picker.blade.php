<div class="avatar-picker" id="adminAvatarPicker" role="dialog" aria-modal="true" aria-label="Choose an AI avatar" hidden>
  <div class="avatar-picker-backdrop" data-admin-avatar-close></div>
  <div class="avatar-picker-card">
    <div class="avatar-picker-head">
      <b>Choose an avatar</b>
      <button type="button" class="avatar-picker-close" data-admin-avatar-close aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="avatar-picker-grid">
      @foreach($aiAvatars as $choice)
        <button type="button" class="avatar-choice{{ $aiAvatarKey === $choice['key'] ? ' is-active' : '' }}" data-admin-avatar-key="{{ $choice['key'] }}" data-admin-avatar-url="{{ $choice['url'] }}" title="{{ $choice['label'] }}">
          <img src="{{ $choice['url'] }}" alt="{{ $choice['label'] }}">
          <span>{{ $choice['label'] }}</span>
        </button>
      @endforeach
    </div>
  </div>
</div>
