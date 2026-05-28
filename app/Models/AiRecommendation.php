<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function wheelchair()
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
