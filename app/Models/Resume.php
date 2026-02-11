<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'pdf_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careerJobs(): BelongsToMany
    {
        return $this->belongsToMany(CareerJob::class);
    }
}
