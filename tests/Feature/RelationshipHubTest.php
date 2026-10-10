<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_hub_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->getByName('relationship.index');

        $this->assertNotNull($route);
        $this->assertSame('relacionamento', $route->uri());
    }
}
