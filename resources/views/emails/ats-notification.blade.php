<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;background:#f4f6f8;color:#182433;font-family:Arial,sans-serif;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;border:1px solid #dce1e7;background:#ffffff;">
                    <tr>
                        <td style="padding:20px 28px;background:#182433;color:#ffffff;">
                            <strong style="font-size:16px;">{{ config('app.name') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;">
                            <p style="margin:0 0 12px;">Hello {{ $recipientName }},</p>
                            <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;">{{ $heading }}</h1>
                            <p style="margin:0 0 22px;color:#52606d;">{{ $intro }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border-top:1px solid #dce1e7;">
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="width:36%;padding:10px 8px 10px 0;border-bottom:1px solid #dce1e7;color:#667382;font-size:13px;vertical-align:top;">
                                            {{ $label }}
                                        </td>
                                        <td style="padding:10px 0 10px 8px;border-bottom:1px solid #dce1e7;font-size:14px;font-weight:600;vertical-align:top;">
                                            {{ $value }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="margin:22px 0 0;color:#52606d;">{{ $outro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid #dce1e7;color:#7a8794;font-size:12px;">
                            This is an automated recruitment workflow message from {{ config('app.name') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
