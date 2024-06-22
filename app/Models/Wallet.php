<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['balance','user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function increaseBalance(float $amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function decreaseBalance(float $amount)
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->save();
        } else {
            throw new \Exception('Insufficient balance');
        }
    }
}
