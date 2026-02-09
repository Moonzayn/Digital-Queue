<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Queue extends Model
{
    use HasFactory;

    protected $table = 'queues';

    protected $fillable = [
        'ticket_number',
        'customer_name',
        'unique_code',
        'type',
        'status',
        'scheduled_at',
        'called_at',
        'completed_at',
        'ip_address',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'reserved', 'serving']);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeServing($query)
    {
        return $query->where('status', 'serving');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['waiting', 'reserved', 'serving']);
    }

        public function getPositionAttribute(): int
    {
        if ($this->status === 'serving') {
            return 0;
        }
        if (!in_array($this->status, ['waiting', 'reserved'])) {
            return 0;
        }

        // Determine this ticket's sort time
        $mySortTime = ($this->type === 'online' && $this->scheduled_at)
            ? $this->scheduled_at
            : $this->created_at;

        return self::today()
            ->whereIn('status', ['waiting', 'reserved'])
            ->where(function ($query) use ($mySortTime) {
                $query->whereRaw("
                    CASE WHEN type = 'online' AND scheduled_at IS NOT NULL
                        THEN scheduled_at
                        ELSE created_at
                    END < ?
                ", [$mySortTime])
                ->orWhere(function ($q) use ($mySortTime) {
                    $q->whereRaw("
                        CASE WHEN type = 'online' AND scheduled_at IS NOT NULL
                            THEN scheduled_at
                            ELSE created_at
                        END = ?
                    ", [$mySortTime])
                    ->where('id', '<', $this->id);
                });
            })
            ->count() + 1;
    }

    public static function generateTicketNumber(): string
    {
        $prefix = now()->day > 15 ? 'B' : 'A';
        $todayCount = self::today()->count() + 1;
        return $prefix . str_pad($todayCount, 3, '0', STR_PAD_LEFT);
    }

    public static function generateUniqueCode(): string
    {
        return md5(uniqid((string) mt_rand(), true));
    }

    /**
     * Get display name: name or ticket number
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->customer_name ?: $this->ticket_number;
    }
}