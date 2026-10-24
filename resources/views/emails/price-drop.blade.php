<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#e61c5d;color:#ffffff;padding:20px 28px;font-size:20px;font-weight:bold;">
                        Hikaku Lite &middot; Price drop alert
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 6px;font-size:14px;color:#8a949e;">Good news! The price you were waiting for is here.</p>
                        <h1 style="margin:0 0 4px;font-size:24px;">{{ $hotel->name }}</h1>
                        <p style="margin:0 0 20px;color:#8a949e;">{{ $hotel->area }}, {{ $hotel->city }}</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eaf6ef;border-radius:10px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <div style="font-size:13px;color:#4d6b58;">Best price today on {{ $sourceName }}</div>
                                    <div style="font-size:32px;font-weight:bold;color:#1a8f4c;">&yen;{{ number_format($price) }}</div>
                                    <div style="font-size:13px;color:#4d6b58;">Your target: &yen;{{ number_format($alert->target_price) }}</div>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0;">
                            <a href="{{ $sourceUrl }}" style="display:inline-block;background:#e61c5d;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:30px;font-weight:bold;">
                                View this deal on {{ $sourceName }}
                            </a>
                        </p>

                        <p style="margin:0;font-size:13px;color:#8a949e;">
                            Prices change daily, so this deal may not last. You asked to be notified at
                            &yen;{{ number_format($alert->target_price) }} or lower. We won't email you about this hotel again for 7 days.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px;background:#fafbfc;font-size:12px;color:#aab2bb;">
                        Hikaku Lite is a demo project with mock data.<br>
                        You received this because you set a price alert.
                        <a href="{{ $unsubscribeUrl }}" style="color:#e61c5d;">Unsubscribe from this alert</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>