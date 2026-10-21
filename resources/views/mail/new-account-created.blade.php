<x-mail.layout :preheader="'Set a password for your ' . config('app.name') . ' account — the link expires in 60 minutes.'">
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#8FB89A;text-transform:uppercase;letter-spacing:1px;">New account</p>

    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#143d23;font-weight:800;">Welcome to {{ config('app.name') }}</h1>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#334155;">
        Hello <strong style="color:#143d23;">{{ $userName }}</strong>,
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#334155;">
        An account was created for you on <strong style="color:#143d23;">{{ config('app.name') }}</strong> as a
        <strong style="color:#143d23;">{{ $roleLabel }}</strong>.
        Choose your own password to finish setting up and start using the platform.
    </p>

    {{-- Primary action: set password in one click --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $setupUrl }}"
                   style="display:inline-block;background-color:#8FB89A;color:#092b14;text-decoration:none;font-size:15px;font-weight:800;letter-spacing:0.05em;padding:14px 30px;border-radius:10px;">
                    Set Your Password
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 24px;text-align:center;font-size:13px;color:#64748b;">
        This link expires on <strong style="color:#143d23;">{{ now()->addMinutes(60)->format('M d, Y \a\t g:i A') }}</strong>.
        If it has expired, ask your administrator to resend the invitation.
    </p>

    {{-- Fallback: temporary password + manual sign-in --}}
    <h2 style="margin:0 0 12px;font-size:15px;color:#143d23;font-weight:800;">Prefer to sign in manually?</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f8f7;border-left:3px solid #8FB89A;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;">
                    Sign in at <strong>{{ url('/login') }}</strong> with
                    <strong style="color:#143d23;">{{ $userEmail }}</strong>. Your temporary password is:
                </p>
                <div style="display:inline-block;background-color:#ffffff;border:1px solid #cfe6cf;border-radius:8px;padding:10px 18px;margin-top:10px;font-size:18px;font-weight:800;letter-spacing:2px;color:#143d23;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                    {{ $tempPassword }}
                </div>
                <p style="margin:10px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                    You will be prompted to choose a permanent password on your first sign-in.
                    The temporary password stops working once you set your own.
                </p>
            </td>
        </tr>
    </table>

    {{-- Security note --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;background-color:#fdf6ec;border-left:3px solid #e0a54a;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#7a5a1e;">
                    <strong>Security reminder:</strong> never share your temporary password with anyone.
                    Aquilala staff will never ask for your password. If you were not expecting this invitation,
                    ignore this email and contact an administrator.
                </p>
            </td>
        </tr>
    </table>
</x-mail.layout>