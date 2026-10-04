<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_book_an_available_court_slot(): void
    {
        $response = $this->postJson(route('bookings.store'), [
            'guest_name' => 'Court Player',
            'guest_email' => 'player@example.com',
            'booking_date' => now()->addDay()->toDateString(),
            'court' => 'PickleHub Court',
            'hour' => 7,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('bookings', [
            'guest_email' => 'player@example.com',
            'court' => 'PickleHub Court',
            'hour' => 7,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_court_slot_cannot_be_booked_twice(): void
    {
        $data = [
            'guest_name' => 'First Player',
            'guest_email' => 'first@example.com',
            'booking_date' => now()->addDay()->toDateString(),
            'court' => 'PickleHub Court',
            'hour' => 18,
        ];

        $this->postJson(route('bookings.store'), $data)->assertCreated();
        $this->assertSame(1, DB::table('bookings')
            ->whereDate('booking_date', $data['booking_date'])
            ->where('court', $data['court'])
            ->where('hour', $data['hour'])
            ->where('status', 'confirmed')
            ->count());
        $this->postJson(route('bookings.store'), array_merge($data, [
            'guest_name' => 'Second Player',
            'guest_email' => 'second@example.com',
        ]))->assertStatus(409);
    }

    public function test_guest_can_book_multiple_hours_for_one_court(): void
    {
        $date = now()->addDay()->toDateString();

        $this->postJson(route('bookings.store'), [
            'guest_name' => 'Long Rally',
            'guest_email' => 'rally@example.com',
            'booking_date' => $date,
            'court' => 'PickleHub Court',
            'hours' => [9, 10, 11],
        ])->assertCreated();

        $this->assertSame(3, \Illuminate\Support\Facades\DB::table('bookings')
            ->whereDate('booking_date', $date)
            ->where('court', 'PickleHub Court')
            ->where('guest_email', 'rally@example.com')
            ->count());
    }

    public function test_availability_returns_plain_booking_dates(): void
    {
        $date = now()->addDay()->toDateString();
        $this->postJson(route('bookings.store'), [
            'guest_name' => 'Court Player',
            'guest_email' => 'player@example.com',
            'booking_date' => $date,
            'court' => 'PickleHub Court',
            'hours' => [8],
        ])->assertCreated();

        $this->getJson('/api/bookings')
            ->assertOk()
            ->assertExactJson([['booking_date' => $date, 'court' => 'PickleHub Court', 'hour' => 8]]);
    }

    public function test_a_cancelled_slot_can_be_booked_again(): void
    {
        $date = now()->addDay()->toDateString();
        $data = [
            'guest_name' => 'First Player',
            'guest_email' => 'first@example.com',
            'booking_date' => $date,
            'court' => 'PickleHub Court',
            'hours' => [12],
        ];
        $this->postJson(route('bookings.store'), $data)->assertCreated();
        \App\Models\Booking::query()->update(['status' => 'cancelled']);

        $this->postJson(route('bookings.store'), array_merge($data, [
            'guest_name' => 'Second Player',
            'guest_email' => 'second@example.com',
        ]))->assertCreated();

        $this->assertDatabaseHas('bookings', ['guest_email' => 'second@example.com', 'hour' => 12, 'status' => 'confirmed']);
        $this->assertSame(1, DB::table('bookings')->count());
    }
}
