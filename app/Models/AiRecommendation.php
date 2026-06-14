<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class AiRecommendation extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function wheelchair()
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
