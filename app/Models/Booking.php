<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = ['booking_reference', 'submission_key', 'customer_id', 'vehicle_id', 'package_id', 'source', 'status', 'confirmed_at', 'appointment_at', 'location', 'staff_notes', 'quoted_total', 'currency', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'appointment_at' => 'datetime', 'quoted_total' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(BookingPreference::class)->orderBy('priority');
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(Inspection::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(InspectionFeature::class, 'booking_addons', 'booking_id', 'feature_id')->withPivot('price_snapshot');
    }
}
