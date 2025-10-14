<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'request_limit',
        'requests_used',
        'subscription_valid_until',
        'is_active',
        'role',
        'meta'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_key'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_valid_until' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array'
        ];
    }
    
    public function isValidSubscription(): bool
    {
        return $this->is_active && 
               $this->subscription_valid_until?->isFuture() && 
               $this->requests_used < $this->request_limit;
    }

    public function incrementRequestCount(): void
    {
        $this->increment('requests_used');
    }

    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', $apiKey)->first();
    }    
}
