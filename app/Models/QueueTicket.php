<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    protected $fillable = [
        'queue_number',
        'invoice_id',
        'customer_name',
        'status',
        'queue_date',
        'daily_sequence',
        'called_at',
        'done_at',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'called_at'  => 'datetime',
        'done_at'    => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    
    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Generate the next queue number for today.
     * Format: Q-001, Q-002, … Q-999
     * Resets automatically every calendar day.
     */
    public static function nextForToday(): array
    {
        $today = now()->toDateString();

        $last = static::where('queue_date', $today)
                      ->orderByDesc('daily_sequence')
                      ->lockForUpdate()
                      ->first();

        $seq = $last ? $last->daily_sequence + 1 : 1;

        return [
            'sequence'     => $seq,
            'queue_number' => 'Q-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
            'queue_date'   => $today,
        ];
    }

    /**
     * The ticket currently on the display screen.
     */
    public static function nowServing(): ?self
    {
        return static::where('status', 'serving')
                     ->where('queue_date', now()->toDateString())
                     ->orderByDesc('called_at')
                     ->first();
    }

    /**
     * Queue of paid tickets waiting to be called.
     */
    public static function readyQueue(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('status', 'paid')
                     ->where('queue_date', now()->toDateString())
                     ->orderBy('daily_sequence')
                     ->get();
    }
}