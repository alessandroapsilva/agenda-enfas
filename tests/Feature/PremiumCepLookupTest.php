<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PremiumCepLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_lookup_cep_and_receive_normalized_address(): void
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'cep' => '06733048',
                'state' => 'SP',
                'city' => 'Vargem Grande Paulista',
                'neighborhood' => 'Centro',
                'street' => 'Rua Teste',
                'service' => 'mock',
            ], 200),
        ]);

        $admin = User::factory()->create([
            'username' => 'admin.cep',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($admin)
            ->getJson(route('cep.lookup', ['cep' => '06733048']))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'postal_code' => '06733-048',
                    'address' => 'Rua Teste',
                    'neighborhood' => 'Centro',
                    'city' => 'Vargem Grande Paulista',
                    'state' => 'SP',
                    'source' => 'brasilapi',
                ],
            ]);
    }
}
