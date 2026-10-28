<x-mail.layout :preheader="'Your administrator creation key expires in 10 minutes.'">
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#8FB89A;text-transform:uppercase;letter-spacing:1px;">Security verification</p>
    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#143d23;font-weight:800;">Confirm administrator creation</h1>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#334155;">
        Hello <strong>{{ $adminName }}</strong>, use the one-time key below to unlock creation of another administrator account.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background:#f3f8f4;border:1px solid #dce8df;border-radius:8px;">
        <tr><td style="padding:18px;text-align:center;font-family:monospace;font-size:22px;font-weight:800;letter-spacing:2px;color:#143d23;word-break:break-all;">{{ $key }}</td></tr>
    </table>
    <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#64748b;">
        This key expires on <strong>{{ $expiresAt }}</strong>, works once only, and is invalidated when another key is requested.
        Never forward it or share it with anyone.
    </p>
    <p style="margin:0;font-size:13px;line-height:1.6;color:#7a5a1e;">
        If you did not request this, change your password and notify another administrator immediately.
    </p>
</x-mail.layout>
