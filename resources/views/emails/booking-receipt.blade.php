@use('App\Models\Booking')
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Booking receipt {{ $booking->reference }}</title></head>
<body style="margin:0;padding:0;background:#eee4d1;font-family:Arial,Helvetica,sans-serif;color:#173d2a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eee4d1;padding:32px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#f8f0e2;border-radius:12px;overflow:hidden;">
                <tr><td style="background:#173d2a;padding:22px 28px;color:#eee4d1;">
                    <div style="font-size:14px;font-weight:bold;letter-spacing:2px;">PICKLE<span style="color:#e8968b;">HUB</span></div>
                    <div style="margin-top:14px;font-size:22px;font-weight:bold;">Booking receipt</div>
                    <div style="margin-top:4px;font-size:13px;color:#c3d9c3;">Show this email at the court as proof of your booking.</div>
                </td></tr>

                <tr><td style="padding:24px 28px 8px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                        <tr>
                            <td style="padding:4px 0;color:#65776b;width:40%;">Reference no.</td>
                            <td style="padding:4px 0;font-weight:bold;font-size:18px;letter-spacing:1px;">{{ $booking->reference }}</td>
                        </tr>
                        <tr><td style="padding:4px 0;color:#65776b;">Name</td><td style="padding:4px 0;">{{ $booking->guest_name }}</td></tr>
                        <tr><td style="padding:4px 0;color:#65776b;">Email</td><td style="padding:4px 0;">{{ $booking->guest_email }}</td></tr>
                        <tr><td style="padding:4px 0;color:#65776b;">Court</td><td style="padding:4px 0;">{{ $booking->court }}</td></tr>
                        <tr><td style="padding:4px 0;color:#65776b;">Date</td><td style="padding:4px 0;font-weight:bold;">{{ $booking->booking_date->format('l, F j, Y') }}</td></tr>
                        <tr><td style="padding:4px 0;color:#65776b;">Booked on</td><td style="padding:4px 0;">{{ $booking->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td></tr>
                    </table>
                </td></tr>

                <tr><td style="padding:12px 28px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
                        <tr style="background:#173d2a;color:#eee4d1;">
                            <th align="left" style="padding:10px 12px;font-size:12px;">Time</th>
                            <th align="left" style="padding:10px 12px;font-size:12px;">Rate</th>
                            <th align="right" style="padding:10px 12px;font-size:12px;">Amount</th>
                        </tr>
                        @foreach ($bookings as $hour)
                            <tr style="border-bottom:1px solid #d8cab1;">
                                <td style="padding:10px 12px;">{{ $hour->timeRange() }}</td>
                                <td style="padding:10px 12px;color:#43564a;">{{ Booking::rateLabelFor($hour->hour) }}</td>
                                <td align="right" style="padding:10px 12px;">{{ Booking::peso($hour->amount()) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" style="padding:14px 12px;font-weight:bold;">Total to pay ({{ $bookings->count() }} {{ Str::plural('hour', $bookings->count()) }})</td>
                            <td align="right" style="padding:14px 12px;font-weight:bold;font-size:20px;color:#c25546;">{{ Booking::peso($total) }}</td>
                        </tr>
                    </table>
                </td></tr>

                <tr><td style="padding:4px 28px 24px;font-size:13px;line-height:1.6;color:#43564a;">
                    <p style="margin:0 0 14px;">Rates: {{ Booking::peso(config('booking.day_rate')) }}/hour before 6:00 PM · {{ Booking::peso(config('booking.evening_rate')) }}/hour from 6:00 PM. Please pay the total at the court.</p>
                    <a href="{{ $receiptUrl }}" style="display:inline-block;background:#173d2a;color:#eee4d1;text-decoration:none;font-weight:bold;font-size:13px;padding:12px 20px;border-radius:12px;">View or print receipt</a>
                </td></tr>

                <tr><td style="background:#e4d8c2;padding:16px 28px;font-size:12px;color:#43564a;">
                    PickleHub · Garaje Pickle Ball Court · Brgy. Mandug, Buhangin District, Davao City
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
