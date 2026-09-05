@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        {{ config('app.name') }}
    @endcomponent
@endslot

{{-- Body --}}
<p style="font-size: 15px; color: #334155; margin: 0 0 16px;">Hello <strong>{{ $userName }}</strong>,</p>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    You have been invited to join the Aquilala System with the registered phone number:
    <strong style="color: #143d23;">{{ $phoneNumber }}</strong>.
    Click this link to confirm your phone number and activate your account.
</p>

<div style="text-align: center; margin: 28px 0;">
    <a href="{{ $activationUrl }}"
       style="display: inline-block; background-color: #436149; color: #ffffff; text-decoration: none;
              font-weight: 700; font-size: 15px; padding: 13px 34px; border-radius: 8px;">
        Confirm Phone Number &amp; Activate Account
    </a>
</div>

<p style="font-size: 13px; color: #64748b; line-height: 1.6; margin: 20px 0 8px;">
    Or copy and paste this link into your browser:
</p>
<p style="word-break: break-all; font-size: 12px; color: #436149; margin: 0 0 24px;">
    <a href="{{ $activationUrl }}" style="color: #436149;">{{ $activationUrl }}</a>
</p>

<div style="background: #f6f8f7; border-left: 3px solid #5DC257; padding: 12px 16px; border-radius: 6px; margin: 0 0 20px;">
    <p style="font-size: 13px; color: #475569; margin: 0;">
        This invitation link expires on <strong>{{ $expiresAt }}</strong>.
        If it expires, ask the system administrator to resend your invitation.
    </p>
</div>

<p style="font-size: 13px; color: #64748b; margin: 0;">
    If you were not expecting this invitation, you can safely ignore this email —
    no account will be created without your confirmation.
</p>

{{-- Footer --}}
@slot('footer')
    @component('mail::footer')
        &copy; {{ date('Y') }} {{ config('app.name') }} — Irrigators Association Inc.
    @endcomponent
@endslot
@endcomponent
