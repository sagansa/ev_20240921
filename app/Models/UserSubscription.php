<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitlement langganan server-side per akun app.
 *
 * Provider: 'apple' (App Store / StoreKit) atau 'google' (Google Play).
 * Status aktif → user berhak bebas-iklan sampai expires_at; status revoked/
 * refunded/cancelled → entitlement dicabut.
 */
class UserSubscription extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'user_subscriptions';

    protected $fillable = [
        'user_id',
        'provider',
        'product_id',
        'original_transaction_id',
        'store_transaction_id',
        'status',
        'starts_at',
        'expires_at',
        'auto_renewing',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_renewing' => 'boolean',
            'raw' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}