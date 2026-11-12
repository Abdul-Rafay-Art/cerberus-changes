<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public string $event)
    {
        $this->booking->loadMissing(['customer', 'vehicle', 'package', 'preferences']);
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->event) {
            'confirmed' => 'Your Cerberus inspection appointment is confirmed',
            'cancelled' => 'An update about your Cerberus inspection request',
            'rescheduled' => 'Your Cerberus inspection appointment has changed',
            default => 'We received your Cerberus inspection request',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-update');
    }
}
