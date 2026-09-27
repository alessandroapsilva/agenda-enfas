<?php

namespace App\Services\Enfas;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CepLookupService
{
    public function lookup(string $cep): array
    {
        $cep = preg_replace('/\D+/', '', $cep);

        if (! preg_match('/^\d{8}$/', $cep)) {
            throw new RuntimeException('CEP inválido.');
        }

        return Cache::remember(
            'cep:'.$cep,
            now()->addDays(30),
            fn () => $this->lookupRemote($cep)
        );
    }

    private function lookupRemote(string $cep): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(4)
                ->retry(1, 150)
                ->get('https://brasilapi.com.br/api/cep/v2/'.$cep);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'postal_code' => $this->formatCep($cep),
                    'address' => trim((string) ($data['street'] ?? '')),
                    'neighborhood' => trim((string) ($data['neighborhood'] ?? '')),
                    'city' => trim((string) ($data['city'] ?? '')),
                    'state' => strtoupper(trim((string) ($data['state'] ?? ''))),
                    'source' => 'brasilapi',
                ];
            }
        } catch (\Throwable) {
            // ViaCEP abaixo é o fallback.
        }

        try {
            $response = Http::acceptJson()
                ->timeout(4)
                ->retry(1, 150)
                ->get('https://viacep.com.br/ws/'.$cep.'/json/');

            if ($response->successful()) {
                $data = $response->json();

                if (! ($data['erro'] ?? false)) {
                    return [
                        'postal_code' => $this->formatCep($cep),
                        'address' => trim((string) ($data['logradouro'] ?? '')),
                        'neighborhood' => trim((string) ($data['bairro'] ?? '')),
                        'city' => trim((string) ($data['localidade'] ?? '')),
                        'state' => strtoupper(trim((string) ($data['uf'] ?? ''))),
                        'source' => 'viacep',
                    ];
                }
            }
        } catch (\Throwable) {
        }

        throw new RuntimeException('CEP não encontrado ou serviço temporariamente indisponível.');
    }

    private function formatCep(string $cep): string
    {
        return substr($cep, 0, 5).'-'.substr($cep, 5, 3);
    }
}
