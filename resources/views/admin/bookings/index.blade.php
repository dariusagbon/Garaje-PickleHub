@include('admin.partials.header', ['title' => 'Manage bookings'])

<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark">Control center</p>
        <h1 class="admin-title">Court bookings</h1>
        <p class="admin-subtitle">Review, update, and cancel reservations for PickleHub Court.</p>
    </div>
    <a class="button button-outline" href="{{ route('admin.events.index') }}">&#8592; Events</a>
</div>

@if (session('message'))
    <div class="admin-alert success">{{ session('message') }}</div>
@endif

@if ($errors->any())
    <div class="admin-alert error">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<section class="admin-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">Reservations</p>
            <h2>{{ $bookings->count() }} total bookings</h2>
        </div>
    </div>

    <div class="admin-booking-list">
        @forelse ($bookings as $booking)
            <form class="admin-booking-row" method="POST" action="{{ route('admin.bookings.update', $booking) }}">
                @csrf
                @method('PATCH')

                <div class="admin-booking-info">
                    <span class="admin-status {{ $booking->status }}">{{ $booking->status }}</span>
                    <h3>{{ $booking->guest_name }}</h3>
                    <p>
                        {{ $booking->guest_email }}
                        · {{ $booking->booking_date->format('M j, Y') }}
                        · {{ \Carbon\Carbon::createFromTime($booking->hour)->format('g:00 A') }}
                    </p>
                </div>

                <div class="admin-booking-fields">
                    <input name="guest_name" value="{{ $booking->guest_name }}" aria-label="Guest name">
                    <input name="guest_email" type="email" value="{{ $booking->guest_email }}" aria-label="Guest email">
                    <input name="booking_date" type="date" value="{{ $booking->booking_date->format('Y-m-d') }}" aria-label="Booking date">
                    <input name="court" value="{{ $booking->court }}" readonly aria-label="Court">
                    <input name="hour" type="number" min="7" max="23" value="{{ $booking->hour }}" aria-label="Booking hour">
                    <select name="status" aria-label="Booking status">
                        <option value="confirmed" @selected($booking->status === 'confirmed')>Confirmed</option>
                        <option value="cancelled" @selected($booking->status === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="admin-actions">
                    <button class="button button-dark" type="submit">Save</button>
                    {{-- Re-uses this form but sends it to the cancel (DELETE) route instead. --}}
                    <button class="button button-danger" type="submit"
                            formaction="{{ route('admin.bookings.destroy', $booking) }}" formmethod="POST"
                            name="_method" value="DELETE">Cancel</button>
                </div>
            </form>
        @empty
            <div class="admin-empty">
                <p>No bookings yet.</p>
            </div>
        @endforelse
    </div>
</section>

@include('admin.partials.footer')
