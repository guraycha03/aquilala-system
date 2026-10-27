<x-mail.layout :preheader="'Your ' . config('app.name') . ' account was upgraded to Administrator. If this was not you, contact the association immediately.'">
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:1px;">Security notice</p>

    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#143d23;font-weight:800;">You now have administrator access</h1>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#334155;">
        Hello <strong style="color:#143d23;">{{ $userName }}</strong>,
    </p>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#334155;">
        On <strong style="color:#143d23;">{{ $grantedAt }}</strong>, your account
        (<strong style="color:#143d23;">{{ $userEmail }}</strong>) was upgraded from a standard
        account to <strong style="color:#143d23;">Administrator</strong> on
        <strong style="color:#143d23;">{{ config('app.name') }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background-color:#f6f8f7;border-left:3px solid #8FB89A;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;">
                    Granted by <strong style="color:#143d23;">{{ $grantedByName }}</strong>
                    ({{ $grantedByEmail }}). The action was recorded in the system audit log.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.65;color:#334155;">
        As an administrator you can manage the list of members, create system accounts,
        register stores and review their paperwork, and view association reports.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $signInUrl }}"
                   style="display:inline-block;background-color:#8FB89A;color:#092b14;text-decoration:none;font-size:15px;font-weight:800;letter-spacing:0.05em;padding:14px 30px;border-radius:10px;">
                    Sign In
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;background-color:#fdf6ec;border-left:3px solid #e0a54a;border-radius:6px;">
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#7a5a1e;">
                    <strong>Was this you?</strong> If you were not expecting this upgrade, change your
                    password right away at <strong>{{ $profileUrl }}</strong> and contact the association
                    administrator — someone else may have access to your account.
                </p>
            </td>
        </tr>
    </table>
</x-mail.layout>
