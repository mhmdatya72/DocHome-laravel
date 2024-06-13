<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Center;
use App\Notifications\MessageSent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $role
 * @method static where(string $string, mixed $id)
 * @method static create(array|string[] $array_merge)
 */
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * Guard for the model
     *
     * @var string
     */
    protected $guard = 'api';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    //  User attributes
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_image',
        'center_id',
        'email_verified_at',
        'access_token',
    ];

    // Hide sensitive attributes when returning as JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];
    // protected $guarded =['id'];

    public function chats() : HasMany
    {
        return $this->hasMany(Chat::class,'created_by');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'password',
    //     // 'remember_token',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }


    public function routeNotificationForOneSignal() : array
    {
      return ['tags'=>['key'=>'userId','relation'=>'=', 'value'=>(string)($this->id)]];
    }

    public function sendNewMessageNotification(array $data) : void {
        $this->notify(new MessageSent($data));
    }

}