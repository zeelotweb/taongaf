<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Cashier\Billable;

#[Fillable(['name', 'email', 'password','bio','avatar_path','avatar_thumbnail_path','role','activity_status','is_subscription_enabled','username','subscription_price','suspended_at',])]

#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, Billable;





    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
 /**   protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
**/



/**  
protected static function booted(): void
{
    static::creating(function ($user) {
        if (static::count() === 0) {
            $user->role = 'superadmin';
        }
    });
}
**/



public function getOrCreateWallet(): \App\Models\Wallet
{
    return $this->wallet ?? \App\Models\Wallet::create([
        'user_id'          => $this->id,
        'token_balance'    => 0,
        'earnings_balance' => 0,
        'total_earned'     => 0,
        'total_spent'      => 0,
    ]);
}


        /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

/**
    protected $fillable = [
        'name',
        //'username',
        'email',
        'bio',
        'avatar_path',
        'avatar_thumbnail_path',
        'password',
        'role',
        'activity_status',
        'is_subscription_enabled',
        'subscription_price',
        'suspended_at',
    ];
*/







    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'is_subscription_enabled' => 'boolean',
        ];
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPublisher(): bool
    {
        return in_array($this->role, ['admin', 'publisher']);
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    // Relationships
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function editorials()
    {
        return $this->hasMany(Editorial::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function tokenTransactions()
    {
        return $this->hasMany(TokenTransaction::class);
    }

    // Check if user has purchased content
    public function hasPurchased($model): bool
    {
        return $this->purchases()
            ->where('purchasable_type', get_class($model))
            ->where('purchasable_id', $model->id)
            ->where('is_active', true)
            ->exists();
    }



    public function reactions()
{
    return $this->hasMany(Reaction::class);
}

public function comments()
{
    return $this->hasMany(Comment::class);
}

public function votes()
{
    return $this->hasMany(Vote::class);
}



public function bookmarks()
{
    return $this->hasMany(Bookmark::class);
}

public function hasBookmarked(Model $model): bool
{
    return $this->bookmarks()
        ->where('bookmarkable_type', get_class($model))
        ->where('bookmarkable_id', $model->id)
        ->exists();
}


public function studioSubscription()
{
    return $this->hasOne(StudioSubscription::class)->latestOfMany();
}

public function studioMemberships()
{
    return $this->hasMany(StudioMembership::class, 'publisher_id');
}

public function staffOf()
{
    return $this->hasMany(StudioMembership::class);
}

public function communityMembers()
{
    return $this->hasMany(CommunityMember::class, 'publisher_id');
}

public function publisherMetrics()
{
    return $this->hasOne(PublisherMetrics::class);
}

public function surveys()
{
    return $this->hasMany(Survey::class);
}

// Helper methods
public function hasActiveStudio(): bool
{
    return $this->studioSubscription?->isActive() ?? false;
}

public function isStaffOf(User $publisher): bool
{
    return StudioMembership::where('publisher_id', $publisher->id)
        ->where('user_id', $this->id)
        ->where('status', 'active')
        ->exists();
}

public function hasStudioRole(User $publisher, string $role): bool
{
    $membership = StudioMembership::where('publisher_id', $publisher->id)
        ->where('user_id', $this->id)
        ->where('status', 'active')
        ->first();

    return $membership?->hasRole($role) ?? false;
}

public function hasCommerceEnabled(): bool
{
    return $this->profileCommerceSetting?->is_enabled ?? false;
}

public function isEligibleForCommerce(): bool
{
    $metrics = $this->publisherMetrics;
    if (!$metrics) return false;

    return $metrics->follower_count >= config('commerce.profile_commerce_unlock.min_followers')
        || $metrics->total_token_earnings >= config('commerce.profile_commerce_unlock.min_earnings');
}

public function isEligibleToHustle(): bool
{
    $metrics = $this->publisherMetrics;
    if (!$metrics) return false;

    return $metrics->follower_count >= config('commerce.hustle_unlock.min_followers')
        || $metrics->total_token_earnings >= config('commerce.hustle_unlock.min_earnings');
}

public function profileCommerceSetting()
{
    return $this->hasOne(ProfileCommerceSetting::class);
}


public function conversations()
{
    return $this->hasManyThrough(
        Conversation::class,
        ConversationParticipant::class,
        'user_id',
        'id',
        'id',
        'conversation_id'
    );
}

public function messageSetting()
{
    return $this->hasOne(MessageSetting::class);
}

public function chatRooms()
{
    return $this->hasManyThrough(
        ChatRoom::class,
        ChatRoomMember::class,
        'user_id',
        'id',
        'id',
        'chat_room_id'
    );
}

public function ownedChatRooms()
{
    return $this->hasMany(ChatRoom::class, 'owner_id');
}

public function canReceiveMessageFrom(User $sender): bool
{
    $settings = $this->messageSetting;
    if (!$settings) return true; // default allow

    return $settings->canReceiveFrom($sender);
}

public function maxChatRooms(): int
{
    $subscription = $this->studioSubscription;
    if (!$subscription || !$subscription->isActive()) return 1;

    return match($subscription->plan) {
        'basic' => 3,
        'pro'   => 5,
        default => 1,
    };
}

public function canCreateChatRoom(): bool
{
    return $this->ownedChatRooms()->count() < $this->maxChatRooms();
}
}