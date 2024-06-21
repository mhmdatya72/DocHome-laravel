<?php

namespace App\Listeners;

use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateWalletForNewUser
{
    public function handle(Registered $event)
    {
        $user = $event->user;
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }
}
