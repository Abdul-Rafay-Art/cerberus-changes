<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasUuids;

    protected $fillable = ['customer_id', 'registration', 'make', 'model', 'year', 'vin', 'mileage_display'];

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle): void {
            $vehicle->registration = strtoupper(trim($vehicle->registration));
            $vehicle->registration_normalized = preg_replace('/[^A-Z0-9]/', '', $vehicle->registration);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
