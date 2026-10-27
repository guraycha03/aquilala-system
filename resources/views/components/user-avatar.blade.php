@props([
    'user' => null,
    'size' => 36,
    'class' => '',
    // A store owner must not be able to recognise a farmer by their face
    // any more than by their name, so the masked view drops the picture.
    'mask' => false,
    'photo' => true,
])

@php
    $photoUrl = ($photo && ! $mask) ? $user?->profilePhotoUrl() : null;
    $initials = $user?->initials() ?? '?';
    // A member record composes its display name from parts; only an account
    // carries it in a plain `name` attribute.
    $fullName = $user instanceof \App\Models\Member ? $user->fullName() : ($user?->name ?? '');
    // A masked avatar must not leak the name on hover either.
    $label = $mask ? ($user?->maskedName() ?? '') : $fullName;
    $fontSize = round($size * 0.36);
@endphp

<span class="user-avatar {{ $class }}" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ $fontSize }}px;" @if(! $photoUrl && $label !== '') title="{{ $label }}" @endif>
    @if($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $fullName }}" loading="lazy">
    @else
        {{ $initials }}
    @endif
</span>
