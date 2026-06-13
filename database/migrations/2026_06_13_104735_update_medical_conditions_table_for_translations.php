<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clear the existing data to avoid constraint issues if needed, or just truncate it
        // Since we are changing the structure and seeding new data, let's truncate.
        DB::table('user_medical_conditions')->delete();
        DB::table('medical_conditions')->delete();

        Schema::table('medical_conditions', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_ar']);
            $table->json('name')->after('id');
        });

        // Insert new records
        $conditions = [
            [
                'en' => '🩸 High Blood Pressure',
                'ar' => '🩸 ضغط الدم المرتفع',
                'de' => '🩸 Bluthochdruck',
                'fr' => '🩸 Hypertension',
                'ge' => '🩸 Bluthochdruck', // Provided German fallback for 'ge'
                'hi' => '🩸 उच्च रक्तचाप',
                'ko' => '🩸 고혈압',
                'vi' => '🩸 Huyết áp cao',
            ],
            [
                'en' => '❤️ Heart Disease',
                'ar' => '❤️ أمراض القلب',
                'de' => '❤️ Herzerkrankung',
                'fr' => '❤️ Maladie cardiaque',
                'ge' => '❤️ Herzerkrankung',
                'hi' => '❤️ हृदय रोग',
                'ko' => '❤️ 심장병',
                'vi' => '❤️ Bệnh tim',
            ],
            [
                'en' => '🫁 Asthma',
                'ar' => '🫁 الربو',
                'de' => '🫁 Asthma',
                'fr' => '🫁 Asthme',
                'ge' => '🫁 Asthma',
                'hi' => '🫁 अस्थमा',
                'ko' => '🫁 천식',
                'vi' => '🫁 Hen suyễn',
            ],
            [
                'en' => '💉 Diabetes',
                'ar' => '💉 السكري',
                'de' => '💉 Diabetes',
                'fr' => '💉 Diabète',
                'ge' => '💉 Diabetes',
                'hi' => '💉 मधुमेह',
                'ko' => '💉 당뇨병',
                'vi' => '💉 Bệnh tiểu đường',
            ],
            [
                'en' => '⚡ Epilepsy',
                'ar' => '⚡ الصرع',
                'de' => '⚡ Epilepsie',
                'fr' => '⚡ Épilepsie',
                'ge' => '⚡ Epilepsie',
                'hi' => '⚡ मिर्गी',
                'ko' => '⚡ 간질',
                'vi' => '⚡ Bệnh động kinh',
            ],
            [
                'en' => '👴 Elderly',
                'ar' => '👴 كبار السن',
                'de' => '👴 Ältere Menschen',
                'fr' => '👴 Personnes âgées',
                'ge' => '👴 Ältere Menschen',
                'hi' => '👴 बुजुर्ग',
                'ko' => '👴 노인',
                'vi' => '👴 Người cao tuổi',
            ]
        ];

        foreach ($conditions as $condition) {
            DB::table('medical_conditions')->insert([
                'name' => json_encode($condition, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_conditions', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('name_en')->after('id');
            $table->string('name_ar')->after('name_en');
        });
    }
};
