<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicalConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            ['name_en' => 'Heart Disease', 'name_ar' => 'أمراض القلب'],
            ['name_en' => 'Diabetes', 'name_ar' => 'مرض السكري'],
            ['name_en' => 'Hypertension', 'name_ar' => 'ارتفاع ضغط الدم'],
            ['name_en' => 'Epilepsy', 'name_ar' => 'الصرع'],
            ['name_en' => 'Asthma', 'name_ar' => 'الربو'],
            ['name_en' => 'Elderly', 'name_ar' => 'كبار السن / الشيخوخة'],
        ];

        foreach ($conditions as $c) {
            \Illuminate\Support\Facades\DB::table('medical_conditions')->insert([
                'name_en' => $c['name_en'],
                'name_ar' => $c['name_ar'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
