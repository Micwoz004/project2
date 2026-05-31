<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MobileApiToken extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function issueFor(User $user, string $name = 'mobile'): array
    {
        $plainToken = Str::random(80);
        $token = self::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainToken),
        ]);

        return [$token, $plainToken];
    }

    public static function findValid(?string $plainToken): ?self
    {
        if (! is_string($plainToken) || $plainToken === '') {
            return null;
        }

        $token = self::query()
            ->with('user')
            ->where('token', hash('sha256', $plainToken))
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $token instanceof self ? $token : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
