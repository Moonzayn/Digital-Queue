<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    protected $table = 'qr_tokens';

    protected $fillable = [
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    public static function createNew(int $minutes = 5): self
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $token = '';
        for ($i = 0; $i < 8; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return self::create([
            'token' => $token,
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }

    public static function getActive(): ?self
    {
        return self::where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public static function getOrCreate(): self
    {
        $active = self::getActive();
        if ($active) {
            return $active;
        }
        return self::createNew();
    }
}