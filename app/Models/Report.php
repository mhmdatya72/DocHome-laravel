<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, mixed $user_name)
 * @method static create(array|string[] $array_merge)
 * @method static findOrFail($id)
 */
class Report extends Model
{
    use HasFactory;
    protected $guarded = [];

}
