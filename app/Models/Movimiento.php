<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'transactions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    // La tabla transactions tiene created_at, pero no updated_at.
    // Por eso se desactiva el manejo automático de timestamps.
    public $timestamps = false;

    protected $fillable = [
        'id',
        'transaction_code',
        'account_origin_id',
        'account_destination_id',
        'amount',
        'type',
        'status',
        'branch_id',
        'atm_id',
        'node_api_key_id',
        'idempotency_key',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function cuentaOrigen(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'account_origin_id');
    }

    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'account_destination_id');
    }
}
