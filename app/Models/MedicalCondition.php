<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class MedicalCondition extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    public function getNameAttribute($value)
    {
        $lang = request()->header('Accept-Language', 'en');
        // Extract base language code (e.g. 'en-US' -> 'en')
        $langCode = substr($lang, 0, 2);
        
        $nameArray = json_decode($value, true);
        if (!is_array($nameArray)) {
            // Fallback if not json
            return $value;
        }

        return $nameArray[$langCode] ?? $nameArray['en'] ?? null;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_medical_conditions')
            ->withTimestamps();
    }
}
