<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Caregiver;
use App\Models\User;

class Rating extends Model
{
    use HasFactory;
    protected $fillable = ['caregiver_id', 'user_id', 'rating', 'comments'];


    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
