<?php

namespace App\Contracts;

interface SignatureProvider
{
    /**
     * Assina bytes já finalizados e devolve os metadados da assinatura.
     * A implementação real pode usar certificado A1, A3 ou assinatura remota.
     *
     * @return array{provider:string,hash:string,signed_at:string,metadata:array}
     */
    public function sign(string $content, array $context=[]): array;

    public function verify(string $content, array $signature): bool;
}
