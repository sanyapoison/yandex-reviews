<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'yandex_url',
        'rating',
        'reviews_count',
        'grades_count',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
