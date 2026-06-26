<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'organization_id',
        'author',
        'date',
        'text',
        'rating',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
