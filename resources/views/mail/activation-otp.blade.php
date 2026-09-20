<x-mail.layout :preheader="'Your ' . config('app.name') . ' verification code is ' . $otp . ' — it expires in 10 minutes.'">
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#5DC257;text-transform:uppercase;letter-spacing:1px;">Account activation</p>

    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#143d23;font-weight:800;">Verify your account</h1>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#334155;">
        Hello <strong style="color:#143d23;">{{ $userName }}</strong>,
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#334155;">
        You have been given an account for <strong style="color:#143d23;">{{ config('app.name') }}</strong>.
        Use the 6-digit verification code below to confirm your email address, create your password,
        and activate your account.
    </p>

    {{-- Purpose callout --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background-color:#f6f8f7;border-left:3px solid #5DC257;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;">
                    <strong style="color:#143d23;">Why you received this:</strong>
                    an account was created for
                    <strong>{{ $userEmail }}</strong>. This code confirms the address belongs to you
                    before any password is set.
                </p>
            </td>
        </tr>
    </table>

    {{-- OTP code --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
        <tr>
            <td align="center">
                <div style="display:inline-block;background-color:#f0f7f0;border:1px solid #cfe6cf;border-radius:12px;padding:18px 26px;">
                    <div style="font-size:11px;color:#64748b;letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">Your verification code</div>
                    <div style="font-size:34px;font-weight:800;letter-spacing:10px;color:#143d23;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">{{ $otp }}</div>
                </div>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 24px;text-align:center;font-size:13px;color:#64748b;">
        This code expires on <strong style="color:#143d23;">{{ $expiresAt }}</strong> and can be used only once.
    </p>

    {{-- How to use --}}
    <h2 style="margin:0 0 12px;font-size:15px;color:#143d23;font-weight:800;">How to activate your account</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="padding:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">1.</strong>&nbsp; Go to the account activation page:
                <a href="{{ url('/activate') }}" style="color:#436149;font-weight:700;text-decoration:underline;">{{ url('/activate') }}</a>
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
            <td style="padding:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">4.</strong>&nbsp; Create your password &mdash; your account activates automatically.
            </td>
        </tr>
        <tr>
            <td style="font-size:14px;line-height:1.6;color:#334155;">
                <strong style="color:#5DC257;">5.</strong>&nbsp; Sign in and start using {{ config('app.name') }}.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#64748b;">
        If the code expires, use the <strong>&ldquo;Resend code&rdquo;</strong> option on the activation page to get a new one.
    </p>

    {{-- Security note --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdf6ec;border-left:3px solid #e0a54a;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#7a5a1e;">
                    <strong>Security reminder:</strong> never share this code with anyone.
                    Aquilala staff will never ask you for your verification code.
                    If you were not expecting this invitation, ignore this email &mdash; no password will ever be set.
                </p>
            </td>
        </tr>
    </table>
</x-mail.layout>