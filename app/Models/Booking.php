<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method static where(string $string, $caregiverId)
 * @method static findOrFail($id)
 */
class Booking extends Model
{
    use HasFactory;

    /**
     * @var int|mixed
     */

    protected $fillable = [
        'user_id',
        'location',
        'service_id',
        'caregiver_id',
        'services',
        'total_price',
        'booking_date',
        'start_date', 'end_date',
        'approval_status',
        'phone_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'booking_service');
    }
}

