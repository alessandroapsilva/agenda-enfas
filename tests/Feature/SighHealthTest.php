<?php

namespace Tests\Feature;

use Tests\TestCase;

class SighHealthTest extends TestCase
{
    public function test_sigh_health_endpoint_is_available(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status','ok')
            ->assertJsonPath('service','SIGH ENFAS');
    }

    public function test_module_catalog_contains_sigh_far(): void
    {
        $this->getJson('/api/v1/modules')
            ->assertOk()
            ->assertJsonFragment(['key'=>'far','name'=>'SIGH FAR']);
    }
}
