<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;


class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d\TH:i:s.u\Z');
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->getRawOriginal('image') && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->getRawOriginal('image'));
            }
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'password_set',
        'language',
        'role',
        // user
        'phone',
        'username',
        'gender',
        'birth_date',
        'weight',
        'height',
        // organization
        'location',
        'image',
        // otp
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'provider_id',
        'provider_name',
        'password_set',
        'remember_token',
        'provider_token',
        'provider_refresh_token',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'age',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'                  => 'datetime',
            'password'                           => 'hashed',
            'language'                           => \App\Enums\LanguagePreferenceEnum::class,
        ];
    }


    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function organizationRoleOrganization()
    {
        if ($this->isOrganization()) {
            return $this->organizations()->first();
        }

        return null;
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'owner_id');
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class, 'owner_id');
    }

    public function isUser(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::USER->value;
    }

    public function isCompanion(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::COMPANION->value;
    }

    public function isDoctor(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::DOCTOR->value;
    }

    public function isOrganization(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::ORGANIZATION->value;
    }

    public function isOrganizationAdmin(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::ORGANIZATION_ADMIN->value;
    }

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\UserRoleEnum::ADMIN->value;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? \Carbon\Carbon::parse($this->birth_date)->age : null;
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritePlaces()
    {
        return $this->morphedByMany(Place::class, 'favoritable', 'favorites')->withTimestamps();
    }

    public function favorites()
    {
        return $this->favoritePlaces();
    }

    public function favoriteOrganizations()
    {
        return $this->morphedByMany(Organization::class, 'favoritable', 'favorites')->withTimestamps();
    }

    public function visitedPlaces()
    {
        return $this->morphedByMany(Place::class, 'visitable', 'visitors')->withTimestamps();
    }

    public function visitedOrganizations()
    {
        return $this->morphedByMany(Organization::class, 'visitable', 'visitors')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function hiddenPosts()
    {
        return $this->belongsToMany(Post::class, 'hidden_posts')->withTimestamps();
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    public function eChairs(): HasMany
    {
        return $this->hasMany(EChair::class, 'assigned_to_user_id');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    // disability relations removed in Phase 3

    public function medicalConditions()
    {
        return $this->belongsToMany(MedicalCondition::class, 'user_medical_conditions');
    }

    public function wheelchairs(): HasMany
    {
        return $this->hasMany(Wheelchair::class);
    }

    /**
     * Users this user has added as friends.
     */
    public function friendsOfMine()
    {
        return $this->belongsToMany(User::class, 'user_friends', 'user_id', 'friend_id')
            ->using(Friendship::class)
            ->withPivot(['status', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * Users who added this user as a friend.
     */
    public function friendOf()
    {
        return $this->belongsToMany(User::class, 'user_friends', 'friend_id', 'user_id')
            ->using(Friendship::class)
            ->withPivot(['status', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * Merged accepted friends
     */
    public function getFriendsAttribute()
    {
        $friendsOfMine = $this->friendsOfMine()->wherePivot('status', 'accepted')->get();
        $friendOf = $this->friendOf()->wherePivot('status', 'accepted')->get();

        return $friendsOfMine->merge($friendOf);
    }

    /**
     * Query scope for accepted friends (useful for eager loading)
     */
    public function friends()
    {
        return $this->friendsOfMine()->wherePivot('status', 'accepted');
    }

    public function connectionRequestsSent()
    {
        return $this->hasMany(ConnectionRequest::class, 'sender_id');
    }

    public function connectionRequestsReceived()
    {
        return $this->hasMany(ConnectionRequest::class, 'receiver_id');
    }

    // Companion -> User
    public function getConnectedUserForCompanionAttribute()
    {
        $request = $this->connectionRequestsSent()
            ->where('connection_type', 'companion')
            ->where('status', 'accepted')
            ->first();
        return $request ? $request->receiver : null;
    }

    // User -> Companions (Multiple companions can follow one user)
    public function getConnectedCompanionsAttribute()
    {
        return User::whereIn('id', ConnectionRequest::where('receiver_id', $this->id)
            ->where('connection_type', 'companion')
            ->where('status', 'accepted')
            ->pluck('sender_id'))->get();
    }

    // Doctor -> Patients
    public function getConnectedPatientsAttribute()
    {
        return User::whereIn('id', ConnectionRequest::where('receiver_id', $this->id)
            ->where('connection_type', 'doctor')
            ->where('status', 'accepted')
            ->pluck('sender_id'))->get();
    }

    // User -> Doctor (A user has one doctor)
    public function getConnectedDoctorAttribute()
    {
        $request = $this->connectionRequestsSent()
            ->where('connection_type', 'doctor')
            ->where('status', 'accepted')
            ->first();
        return $request ? $request->receiver : null;
    }

    /**
     * 3. دالة الصلاحية الخاصة بـ Filament (أضفناها هنا في النهاية)
     * لتسمح للمسؤولين فقط بدخول لوحة الإدارة
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // هنخليها ترجع true عشان تقدري تدخلي بالأكونت اللي عملتيه حالا وتجربي براحتك
        return true;

        // وقت المناقشة لو عايزة تحبكيها أوي خليها كدا:
        // return $this->isAdmin();
    }
}
