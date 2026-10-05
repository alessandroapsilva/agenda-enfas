<?php

namespace App\Contracts;

use App\Models\ClinicalDocument;
use App\Models\ClinicalDocumentSignature;
use App\Models\User;
use Illuminate\Http\Request;

interface ClinicalSignatureProvider
{
    public function sign(ClinicalDocument $document, User $user, Request $request): ClinicalDocumentSignature;

    public function name(): string;
}
