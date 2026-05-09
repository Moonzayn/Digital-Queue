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

        // Determine this ticket's sort time and priority
        $sortTime = null;
        $priority = 0; // 0 = walk-in, 1 = online on time, 2 = online late

        if ($this->type === 'online') {
            if ($this->scheduled_at && $this->scan_time) {
                // Online user scanned on time
                if ($this->scan_time->lte($this->scheduled_at)) {
                    $sortTime = $this->scan_time;
                    $priority = 1;
                } else {
                    // Online user scanned late - treated as walk-in
                    $sortTime = $this->scan_time;
                    $priority = 2;
                }
            } elseif ($this->scheduled_at) {
                // Online user reserved but not scanned yet
                $sortTime = $this->scheduled_at;
                $priority = 1;
            } else {
                // Online user without scheduled time (edge case)
                $sortTime = $this->created_at;
                $priority = 1;
            }
        } else {
            // Walk-in user
            $sortTime = $this->created_at;
            $priority = 0;
        }

        return self::today()
            ->whereIn('status', ['waiting', 'reserved'])
            ->where(function ($query) use ($sortTime, $priority) {
                $query->whereRaw("
                    CASE
                        WHEN type = 'online' AND scheduled_at IS NOT NULL AND scan_time IS NOT NULL
                            THEN CASE
                                WHEN scan_time <= scheduled_at THEN 1
                                ELSE 2
                            END
                        WHEN type = 'online' AND scheduled_at IS NOT NULL THEN 1
                        ELSE 0
                    END < ?
                ", [$priority])
                ->orWhere(function ($q) use ($sortTime, $priority) {
                    $q->whereRaw("
                        CASE
                            WHEN type = 'online' AND scheduled_at IS NOT NULL AND scan_time IS NOT NULL
                                THEN CASE
                                    WHEN scan_time <= scheduled_at THEN 1
                                    ELSE 2
                                END
                            WHEN type = 'online' AND scheduled_at IS NOT NULL THEN 1
                            ELSE 0
                        END = ?
                    ", [$priority])
                    ->whereRaw("
                        CASE
                            WHEN type = 'online' AND scheduled_at IS NOT NULL AND scan_time IS NOT NULL
                                THEN scan_time
                            WHEN type = 'online' AND scheduled_at IS NOT NULL
                                THEN scheduled_at
                            ELSE created_at
                        END < ?
                    ", [$sortTime])
                    ->orWhere(function ($q2) use ($sortTime, $priority) {
                        $q2->whereRaw("
                            CASE
                                WHEN type = 'online' AND scheduled_at IS NOT NULL AND scan_time IS NOT NULL
                                    THEN scan_time
                                WHEN type = 'online' AND scheduled_at IS NOT NULL
                                    THEN scheduled_at
                                ELSE created_at
                            END = ?
                        ", [$sortTime])
                        ->where('id', '<', $this->id);
                    });
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