@props(['preheader' => null])
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2ef;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;-webkit-text-size-adjust:100%;">
    @if ($preheader)
        <div style="display:none;font-size:1px;color:#eef2ef;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ef;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #dfe7e1;">
                    {{-- Brand header --}}
                    <tr>
                        <td style="background-color:#143d23;padding:24px 32px;">
                            <div style="font-size:20px;font-weight:800;color:#ffffff;letter-spacing:1.5px;">{{ strtoupper(config('app.name')) }}</div>
                            <div style="font-size:12px;color:#9fd39a;margin-top:3px;letter-spacing:.3px;">Irrigators Association Inc.</div>
                        </td>
                    </tr>
                    {{-- Accent rule --}}
                    <tr>
                        <td style="height:4px;background-color:#5DC257;line-height:4px;font-size:0;">&nbsp;</td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f6f8f7;padding:18px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">
                                &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; Irrigators Association Inc.<br>
                                This is an automated message. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="max-width:600px;width:100%;margin:16px 0 0;font-size:11px;color:#94a3b8;line-height:1.5;text-align:center;">
                    Sent by {{ config('app.name') }}. If you received this by mistake, you can safely ignore it.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
