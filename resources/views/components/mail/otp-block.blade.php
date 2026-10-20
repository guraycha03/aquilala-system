@props(['otp', 'expiresAt', 'buttonUrl', 'buttonLabel'])

{{-- The code sits at the very top of the email so that:
     1. notification previews and inbox lists show it WITHOUT opening the email,
     2. mail OTP detectors (Gmail on Android, iOS Mail) offer one-tap copy/autofill,
     3. a single button jumps straight to the page that asks for it. --}}
<p style="margin:0 0 12px;font-size:15px;line-height:1.65;color:#334155;">
    Your verification code is <strong style="color:#143d23;font-size:17px;">{{ $otp }}</strong>.
</p>

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
<p style="margin:0 0 18px;text-align:center;font-size:13px;color:#64748b;">
    This code expires on <strong style="color:#143d23;">{{ $expiresAt }}</strong> and can be used only once.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
    <tr>
        <td align="center">
            <a href="{{ $buttonUrl }}" style="display:inline-block;background-color:#16a34a;color:#ffffff;font-size:15px;font-weight:700;letter-spacing:.05em;text-decoration:none;padding:14px 34px;border-radius:8px;">{{ $buttonLabel }}</a>
        </td>
    </tr>
</table>
