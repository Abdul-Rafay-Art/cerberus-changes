<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $customerName, public string $vehicleLabel, public string $reportUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Cerberus vehicle inspection report is ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.report-ready');
    }
}
