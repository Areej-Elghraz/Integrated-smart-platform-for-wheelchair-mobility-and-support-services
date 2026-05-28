<?php

use App\Enums\LanguagePreferenceEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Enum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // name
            $table->string('name');
            // email
            $table->string('email')->unique();
            $table->string('email_verification_code')->nullable();
            $table->tinyInteger('email_verification_times_sent')->nullable();
            $table->timestamp('email_verification_code_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            // password
            $table->string('password')->nullable();
            $table->string('password_set')->default(true); /// 
            // socialite
            $table->string('provider_id')->nullable(); /// 
            $table->string('provider_name')->nullable(); /// 
            // role
            $table->enum('role', UserRoleEnum::cases())
                ->default(UserRoleEnum::USER->value);


            // user -> data
            $table->string('phone')->unique()->nullable();
            $table->boolean('follow_doctor')->nullable();

            // organization -> data
            // $table->string('location')->nullable();
            $table->string('image')->nullable();
            // $table->string('language')->default(LanguagePreferenceEnum::EN->value);

            // $table->string('provider_token');
            // $table->string('provider_refresh_token');
            // $table->decimal('latitude', 10, 7)->nullable();SS
            // $table->decimal('longitude', 10, 7)->nullable();
            // $table->enum('language', LanguagePreferenceEnum::cases())->default(LanguagePreferenceEnum::EN->value);
            // disability_type
            // mobility_issues
            // chronic_illness
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->tinyInteger('times')->default(0);
            $table->boolean('verified')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
