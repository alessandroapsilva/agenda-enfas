<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class PremiumPermissionsTest extends TestCase
{
    public function test_admin_has_every_permission(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->canAccess('users.manage'));
        $this->assertTrue($user->canAccess('settings.manage'));
        $this->assertTrue($user->canAccess('agenda.manage'));
    }

    public function test_attendant_uses_role_defaults(): void
    {
        $user = new User([
            'role' => 'attendant',
            'permissions' => null,
        ]);

        $this->assertTrue($user->canAccess('agenda.manage'));
        $this->assertTrue($user->canAccess('patients.manage'));
        $this->assertFalse($user->canAccess('users.manage'));
    }

    public function test_custom_permissions_override_role_defaults(): void
    {
        $user = new User([
            'role' => 'attendant',
            'permissions' => ['dashboard.view'],
        ]);

        $this->assertTrue($user->canAccess('dashboard.view'));
        $this->assertFalse($user->canAccess('agenda.manage'));
    }
}
