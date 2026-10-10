<?php

namespace Tests\Feature;

use Tests\TestCase;

class PremiumNamespaceIntegrityTest extends TestCase
{
    public function test_premium_runtime_files_do_not_contain_collapsed_namespaces(): void
    {
        $files = [
            app_path(
                'Http/Controllers/AgendaController.php'
            ),
            resource_path(
                'views/enfas/v11/today.blade.php'
            ),
            resource_path(
                'views/enfas/v6/whatsapp/messages.blade.php'
            ),
            resource_path(
                'views/enfas/v9/template-studio.blade.php'
            ),
        ];

        $forbidden = [
            'IlluminateSupport',
            'SupportFacades',
            'AppServices',
            'AppModels',
            'CarbonCarbon',
        ];

        foreach ($files as $file) {
            $this->assertFileExists(
                $file
            );

            $content = file_get_contents(
                $file
            );

            foreach ($forbidden as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $content,
                    basename($file)
                        .' contém namespace colapsado: '
                        .$token
                );
            }
        }
    }
}
