<x-mail.layout :preheader="'Your ' . config('app.name') . ' verification code is ' . $otp . ' — it expires in 10 minutes.'">
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#5DC257;text-transform:uppercase;letter-spacing:1px;">Email verification</p>

    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#143d23;font-weight:800;">Verify your email address</h1>

    {{-- Code first: visible from the notification and inbox list without
         opening the email, and one tap from the button to the page that
         asks for it. --}}
    <x-mail.otp-block
        :otp="$otp"
        :expires-at="$expiresAt"
        button-url="{{ url('/verify-email') }}"
        button-label="Verify my email"
    />

    <p style="margin:0 0 8px;font-size:15px;line-height:1.65;color:#334155;">
        Hello <strong style="color:#143d23;">{{ $userName }}</strong>,
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#334155;">
        Enter the 6-digit code above to confirm your email address and activate your
        administrator account for <strong style="color:#143d23;">{{ config('app.name') }}</strong>.
    </p>

    {{-- Purpose callout --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background-color:#f6f8f7;border-left:3px solid #5DC257;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;">
                    <strong style="color:#143d23;">Why you received this:</strong>
                    an Aquilala administrator account was registered for
                    <strong>{{ $userEmail }}</strong>. This code confirms the address belongs to you.
                </p>
            </td>
        </tr>
    </table>

    {{-- How to use --}}
    <h2 style="margin:0 0 12px;font-size:15px;color:#143d23;font-weight:800;">How to use this code</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="padding:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">1.</strong>&nbsp; Open the email verification page:
                <a href="{{ url('/verify-email') }}" style="color:#436149;font-weight:700;text-decoration:underline;">{{ url('/verify-email') }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">2.</strong>&nbsp; Enter your email address: <strong>{{ $userEmail }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">3.</strong>&nbsp; Enter the 6-digit code shown above.
            </td>
        </tr>
        <tr>
            <td style="font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">4.</strong>&nbsp; Your account activates automatically &mdash; then sign in.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#64748b;">
        If the code expires, use the <strong>&ldquo;Resend code&rdquo;</strong> option on the verification page to get a new one.
    </p>

    {{-- Security note --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdf6ec;border-left:3px solid #e0a54a;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#7a5a1e;">
                    <strong>Security reminder:</strong> never share this code with anyone.
                    Aquilala staff will never ask you for your verification code.
                    If you did not register an administrator account, ignore this email &mdash; no account will be activated.
                </p>
            </td>
        </tr>
    </table>
</x-mail.layout>
