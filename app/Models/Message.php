<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'messages';

    protected $touches = ['chat'];

    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function caregiver() :BelongsTo
    {
        return $this->belongsTo(Caregiver::class,'caregiver_id');
    }

    public function chat() :BelongsTo
    {
        return $this->belongsTo(Chat::class,'chat_id');
    }
}
