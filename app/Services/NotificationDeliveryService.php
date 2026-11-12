<?php

namespace App\Services;

use App\Mail\BookingUpdateMail;
use App\Mail\ReportReadyMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationDeliveryService
{
    public function booking(Booking $booking, string $event): bool
    {
        try {
            Mail::to($booking->customer->email)->send(new BookingUpdateMail($booking, $event));

            return true;
        } catch (Throwable $exception) {
            Log::error('Cerberus booking email delivery failed.', [
                'booking_id' => $booking->id,
                'event' => $event,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    public function report(Booking $booking, string $reportUrl): bool
    {
        try {
            $vehicleLabel = trim(($booking->vehicle->year ? $booking->vehicle->year.' ' : '').($booking->vehicle->make ?? '').' '.($booking->vehicle->model ?? ''));
            Mail::to($booking->customer->email)->send(new ReportReadyMail($booking->customer->full_name, $vehicleLabel ?: $booking->vehicle->registration, $reportUrl));

            return true;
        } catch (Throwable $exception) {
            Log::error('Cerberus report email delivery failed.', [
                'booking_id' => $booking->id,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
