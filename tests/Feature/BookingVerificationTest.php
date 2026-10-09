<?php

namespace Tests\Feature;

use App\Mail\BookingReceipt;
use App\Mail\BookingVerificationCode;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $overrides = []): array
    {
        return array_merge([
            'guest_name' => 'Juan Dela Cruz',
            'guest_email' => 'juan@example.com',
            'booking_date' => now()->addDay()->toDateString(),
            'court' => 'PickleHub Court',
            'hours' => [16, 17, 18],
        ], $overrides);
    }

    /** Ask for a code and return it, as read from the email. */
    private function requestCode(string $email = 'juan@example.com'): string
    {
        $this->postJson(route('bookings.verify-email'), ['guest_name' => 'Juan Dela Cruz', 'guest_email' => $email])
            ->assertOk()
            ->assertJson(['code_sent' => true]);

        $code = null;
        Mail::assertSent(BookingVerificationCode::class, function ($mail) use ($email, &$code) {
            $code = $mail->code;

            return $mail->hasTo($email);
        });

        return $code;
    }

    public function test_rates_are_200_before_6pm_and_250_from_6pm(): void
    {
        $this->assertSame(200, Booking::rateFor(7));
        $this->assertSame(200, Booking::rateFor(8));
        $this->assertSame(200, Booking::rateFor(17));
        $this->assertSame(250, Booking::rateFor(18));
        $this->assertSame(250, Booking::rateFor(23));
    }

    public function test_booking_needs_the_emailed_code(): void
    {
        Mail::fake();

        $this->postJson(route('bookings.store'), $this->booking())
            ->assertStatus(422)
            ->assertJsonValidationErrors('verification_code');

        $code = $this->requestCode();

        $this->postJson(route('bookings.store'), $this->booking(['verification_code' => '000000']))
            ->assertStatus(422);

        $this->postJson(route('bookings.store'), $this->booking(['verification_code' => $code]))
            ->assertCreated();

        $this->assertSame(3, Booking::count());
    }

    public function test_receipt_email_lists_each_hour_and_the_total(): void
    {
        Mail::fake();
        $code = $this->requestCode();

        $response = $this->postJson(route('bookings.store'), $this->booking(['verification_code' => $code]))
            ->assertCreated()
            ->assertJson(['total' => 650, 'total_label' => '₱650', 'receipt_emailed' => true]);

        $reference = $response->json('reference');
        $this->assertMatchesRegularExpression('/^GPH-\d{6}-[A-Z2-9]{5}$/', $reference);
        $this->assertSame([16 => 200, 17 => 200, 18 => 250], Booking::orderBy('hour')->pluck('price', 'hour')->all());
        $this->assertSame(3, Booking::where('reference', $reference)->count());

        Mail::assertSent(BookingReceipt::class, function (BookingReceipt $mail) use ($reference) {
            $html = $mail->render();

            return $mail->hasTo('juan@example.com')
                && str_contains($html, $reference)
                && str_contains($html, '₱650')
                && str_contains($html, 'Evening rate')
                && str_contains($html, '4:00 PM – 5:00 PM');
        });
    }

    public function test_a_verified_email_is_remembered_for_the_next_booking(): void
    {
        Mail::fake();
        $code = $this->requestCode();
        $this->postJson(route('bookings.store'), $this->booking(['verification_code' => $code]))->assertCreated();

        $this->postJson(route('bookings.verify-email'), ['guest_name' => 'Juan', 'guest_email' => 'JUAN@example.com'])
            ->assertOk()
            ->assertJson(['verified' => true]);

        $this->postJson(route('bookings.store'), $this->booking(['hours' => [20]]))->assertCreated();
        Mail::assertSent(BookingVerificationCode::class, 1);
    }

    public function test_a_new_code_cannot_be_requested_straight_away(): void
    {
        Mail::fake();
        $this->requestCode();

        $this->postJson(route('bookings.verify-email'), ['guest_name' => 'Juan', 'guest_email' => 'juan@example.com'])
            ->assertStatus(429);
    }

    public function test_too_many_wrong_codes_throw_the_code_away(): void
    {
        Mail::fake();
        $code = $this->requestCode();

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('bookings.store'), $this->booking(['verification_code' => '111111']))->assertStatus(422);
        }

        $this->postJson(route('bookings.store'), $this->booking(['verification_code' => $code]))->assertStatus(422);
        $this->assertSame(0, Booking::count());
    }

    public function test_addresses_on_domains_that_cannot_receive_mail_are_rejected(): void
    {
        config(['booking.check_dns' => true]);

        $this->postJson(route('bookings.verify-email'), ['guest_name' => 'Juan', 'guest_email' => 'juan@no-such-domain.invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('guest_email');
    }

    public function test_receipt_page_needs_the_signed_link(): void
    {
        Mail::fake();
        $code = $this->requestCode();
        $reference = $this->postJson(route('bookings.store'), $this->booking(['verification_code' => $code]))->json('reference');

        $this->get(route('bookings.receipt', $reference))->assertForbidden();

        $this->get(URL::signedRoute('bookings.receipt', ['reference' => $reference]))
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('₱650')
            ->assertSee('Juan Dela Cruz');
    }
}
