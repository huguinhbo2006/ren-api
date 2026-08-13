<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'asset_id',
        'category',
        'description',
        'amount_cents',
        'expense_date',
        'vendor',
        'receipt_url',
        'type',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'expense_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('type', 'maintenance');
    }

    public function scopeRepair($query)
    {
        return $query->where('type', 'repair');
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month);
    }
}
