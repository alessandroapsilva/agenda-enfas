<?php

namespace App\Services\Clinical;

use App\Contracts\ClinicalSignatureProvider;
use App\Models\ClinicalDocument;
use App\Models\ClinicalDocumentSignature;
use App\Models\User;
use Illuminate\Http\Request;

class InternalElectronicSignatureProvider implements ClinicalSignatureProvider
{
    public function name(): string
    {
        return 'agenda_enfas';
    }

    public function sign(ClinicalDocument $document, User $user, Request $request): ClinicalDocumentSignature
    {
        $hash = hash('sha256', $document->content);

        return ClinicalDocumentSignature::create([
            'clinical_document_id' => $document->id,
            'signature_type' => 'electronic',
            'user_id' => $user->id,
            'signer_name' => $user->name,
            'signer_registry' => $document->professional
                ? trim(implode(' ', array_filter([
                    $document->professional->council_type,
                    $document->professional->council_number,
                    $document->professional->council_state,
                ])))
                : null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'document_hash' => $hash,
            'provider' => $this->name(),
            'metadata' => [
                'auth_user_id' => $user->id,
                'document_version' => $document->version,
                'authentication' => 'session',
            ],
            'signed_at' => now(),
        ]);
    }
}
