<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Your booking code</title></head>
<body style="margin:0;padding:0;background:#eee4d1;font-family:Arial,Helvetica,sans-serif;color:#173d2a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eee4d1;padding:32px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#f8f0e2;border-radius:12px;overflow:hidden;">
                <tr><td style="background:#173d2a;padding:20px 28px;color:#eee4d1;font-size:14px;font-weight:bold;letter-spacing:2px;">PICKLE<span style="color:#e8968b;">HUB</span></td></tr>
                <tr><td style="padding:28px;">
                    <p style="margin:0 0 12px;font-size:15px;">Hi {{ $name }},</p>
                    <p style="margin:0 0 20px;font-size:15px;line-height:1.5;">Use this code to confirm your email and finish booking your court:</p>
                    <p style="margin:0 0 20px;text-align:center;font-size:36px;font-weight:bold;letter-spacing:10px;background:#c9e3c1;border-radius:12px;padding:16px 0;color:#173d2a;">{{ $code }}</p>
                    <p style="margin:0 0 8px;font-size:13px;color:#43564a;">The code expires in {{ $minutes }} minutes.</p>
                    <p style="margin:0;font-size:13px;color:#43564a;">If you didn't try to book a court at PickleHub, you can ignore this email.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
