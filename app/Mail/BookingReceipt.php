<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Proof of booking: reference, hours, rates and the amount to pay. */
class BookingReceipt extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, Booking>  $bookings */
    public function __construct(
        public Collection $bookings,
        public string $receiptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your PickleHub booking receipt · '.$this->bookings->first()->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-receipt', with: [
            'booking' => $this->bookings->first(),
            'total' => $this->bookings->sum(fn ($booking) => $booking->amount()),
        ]);
    }
}
