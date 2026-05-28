<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    protected $appends = ['name'];
    protected $hidden = ['name_en', 'name_ar'];

    public function getNameAttribute()
    {
        $lang = request()->header('Accept-Language', 'en');
        if (str_starts_with($lang, 'ar')) {
            return $this->name_ar ?? $this->name_en;
        }
        return $this->name_en;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_medical_conditions')
            ->withTimestamps();
    }
}
