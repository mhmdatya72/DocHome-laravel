<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;

/**
 * @method static whereIn(string $string, array $serviceIds)
 * @method static findOrFail($id)
 */
class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
//        return $this->belongsTo('App\Models\Category');
    }
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
