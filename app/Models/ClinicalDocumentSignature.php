<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalDocumentSignature extends Model
{
    protected $fillable = [
        'clinical_document_id','signature_type','user_id','signer_name',
        'signer_registry','ip_address','user_agent','document_hash',
        'provider','provider_signature_id','metadata','signed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo { return $this->belongsTo(ClinicalDocument::class, 'clinical_document_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
