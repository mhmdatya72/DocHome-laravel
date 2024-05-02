<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail($id)
 * @method static create(array $array)
 */
class Center extends Model
{
    use HasFactory;
    protected $fillable = ['name'];


}
