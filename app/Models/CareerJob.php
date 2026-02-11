<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CareerJob extends Model
{
    protected $fillable = [
        'title',
        'company',
        'description',
        'location',
        'skills',
        'salary'
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    public function resumes(): BelongsToMany
    {
        return $this->belongsToMany(Resume::class);
    }
}
