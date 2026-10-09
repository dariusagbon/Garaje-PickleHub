<?php

namespace Tests\Feature;

use App\Mail\BookingReceipt;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
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

    public function test_rates_are_200_before_6pm_and_250_from_6pm(): void
    {
        $this->assertSame(200, Booking::rateFor(7));
        $this->assertSame(200, Booking::rateFor(8));
        $this->assertSame(200, Booking::rateFor(17));
        $this->assertSame(250, Booking::rateFor(18));
        $this->assertSame(250, Booking::rateFor(23));
    }

    public function test_receipt_email_lists_each_hour_and_the_total(): void
    {
        Mail::fake();

        $response = $this->postJson(route('bookings.store'), $this->booking())
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

    public function test_an_email_that_does_not_exist_is_rejected_with_a_clear_message(): void
    {
        Mail::fake();
        config(['booking.check_dns' => true]);

        $this->postJson(route('bookings.store'), $this->booking(['guest_email' => 'juan@no-such-domain.invalid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_email' => 'The email does not exist.']);

        $this->postJson(route('bookings.store'), $this->booking(['guest_email' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_email' => 'The email does not exist.']);

        $this->assertSame(0, Booking::count());
        Mail::assertNothingSent();
    }

    public function test_receipt_page_needs_the_signed_link(): void
    {
        Mail::fake();
        $reference = $this->postJson(route('bookings.store'), $this->booking())->json('reference');

        $this->get(route('bookings.receipt', $reference))->assertForbidden();

        $this->get(URL::signedRoute('bookings.receipt', ['reference' => $reference]))
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('₱650')
            ->assertSee('Juan Dela Cruz');
    }
}
