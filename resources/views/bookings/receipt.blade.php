@use('App\Models\Booking')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Receipt {{ $booking->reference }} | PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-w-[320px] bg-[#eee4d1] font-sans text-[#173d2a]">
    <main class="receipt-page">
        <div class="receipt-actions no-print">
            <a class="nav-link" href="{{ url('/') }}">&larr; Back to PickleHub</a>
            <button class="button button-dark" type="button" onclick="window.print()">Print receipt</button>
        </div>

        <article class="receipt-card">
            <header class="receipt-header">
                <div>
                    <p class="receipt-brand">Pickle<span>Hub</span></p>
                    <h1>Booking receipt</h1>
                    <p class="receipt-note">Show this receipt at the court as proof of your booking.</p>
                </div>
                <div class="receipt-reference">
                    <small>Reference no.</small>
                    <strong>{{ $booking->reference }}</strong>
                    <span @class(['receipt-status', 'is-cancelled' => $cancelled])>{{ $cancelled ? 'Cancelled' : 'Confirmed' }}</span>
                </div>
            </header>

            <dl class="receipt-details">
                <div><dt>Name</dt><dd>{{ $booking->guest_name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $booking->guest_email }}</dd></div>
                <div><dt>Court</dt><dd>{{ $booking->court }}</dd></div>
                <div><dt>Date</dt><dd>{{ $booking->booking_date->format('l, F j, Y') }}</dd></div>
                <div><dt>Booked on</dt><dd>{{ $booking->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd></div>
            </dl>

            <table class="receipt-table">
                <thead>
                    <tr><th>Time</th><th>Rate</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $hour)
                        <tr @class(['is-cancelled' => $hour->status !== 'confirmed'])>
                            <td>{{ $hour->timeRange() }} @if ($hour->status !== 'confirmed')<small>(cancelled)</small>@endif</td>
                            <td>{{ Booking::rateLabelFor($hour->hour) }}</td>
                            <td class="text-right">{{ Booking::peso($hour->amount()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Total to pay</td>
                        <td class="text-right">{{ Booking::peso($total) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p class="receipt-footnote">
                Rates: {{ Booking::peso(config('booking.day_rate')) }}/hour before 6:00 PM ·
                {{ Booking::peso(config('booking.evening_rate')) }}/hour from 6:00 PM. Please pay the total at the court.
                <br>PickleHub · Garaje Pickle Ball Court · Brgy. Mandug, Buhangin District, Davao City
            </p>
        </article>
    </main>
</body>
</html>
