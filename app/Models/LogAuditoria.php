<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'logs_auditoria';

    protected $fillable = [
        'user_id',
        'acao',
        'entidade',
        'entidade_id',
        'ip_address',
        'user_agent',
        'dados_anteriores',
        'dados_novos',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'dados_anteriores' => 'array',
            'dados_novos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
