<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Rating;

/**
 * @method static where(string $string, $caregiverId)
 */
class Caregiver extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
/**
     * Guard for the model
     *
     * @var string
     */
    protected $guard = 'caregiver';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'caregivers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    //     'phone',
    //     'image',
    //     'status',
    //     'status_value'
    // ];
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    private mixed $role;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
// to get specility of caregiver
    public function category (): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
      return $this->belongsTo(Category::class,'category_id');
    }
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function rating(): HasMany
    {
        return $this->hasMany(rating::class);
    }
    public function increaseSalary(float $amount)
    {
        $this->salary += $amount;
        $this->save();
    }
}


