<?php

namespace App\Services\Enfas;

use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandMediaService
{
    public function syncConfiguredLogo(
        ?int $uploadedBy = null
    ): ?object {
        if (! Schema::hasTable(
            'media_assets'
        )) {
            return null;
        }

        $path = trim(
            (string) AppSetting::getValue(
                'branding',
                'logo',
                ''
            )
        );

        if ($path === '') {
            return $this->fallbackLogo();
        }

        $disk = 'public';

        if (! Storage::disk($disk)->exists(
            $path
        )) {
            return $this->fallbackLogo();
        }

        $mime = Storage::disk($disk)
            ->mimeType($path)
            ?: '';

        if (! in_array(
            strtolower($mime),
            [
                'image/png',
                'image/jpeg',
                'image/webp',
            ],
            true
        )) {
            return $this->fallbackLogo();
        }

        $existing = DB::table(
            'media_assets'
        )
            ->where(
                'disk',
                $disk
            )
            ->where(
                'path',
                $path
            )
            ->first();

        $payload = [
            'name' =>
                'Logo oficial ENFAS',
            'kind' =>
                'image',
            'disk' =>
                $disk,
            'path' =>
                $path,
            'mime_type' =>
                $mime,
            'size' =>
                (int) (
                    Storage::disk($disk)
                        ->size($path)
                    ?: 0
                ),
            'is_active' =>
                true,
            'updated_at' =>
                now(),
        ];

        $columns = Schema::getColumnListing(
            'media_assets'
        );

        $payload = array_filter(
            $payload,
            fn ($value, $key) =>
                in_array(
                    $key,
                    $columns,
                    true
                ),
            ARRAY_FILTER_USE_BOTH
        );

        if ($existing) {
            if (in_array(
                'deleted_at',
                $columns,
                true
            )) {
                $payload['deleted_at'] =
                    null;
            }

            DB::table('media_assets')
                ->where(
                    'id',
                    $existing->id
                )
                ->update($payload);

            return DB::table(
                'media_assets'
            )->find($existing->id);
        }

        if (in_array(
            'uploaded_by',
            $columns,
            true
        )) {
            $payload['uploaded_by'] =
                $uploadedBy;
        }

        if (in_array(
            'created_at',
            $columns,
            true
        )) {
            $payload['created_at'] =
                now();
        }

        $id = DB::table(
            'media_assets'
        )->insertGetId($payload);

        return DB::table(
            'media_assets'
        )->find($id);
    }

    public function defaultLogo(): ?object
    {
        return $this->syncConfiguredLogo(
            auth()->id()
        );
    }

    public function deactivatePath(
        ?string $path
    ): void {
        if (! $path
            || ! Schema::hasTable(
                'media_assets'
            )) {
            return;
        }

        $columns = Schema::getColumnListing(
            'media_assets'
        );

        $payload = [];

        if (in_array(
            'is_active',
            $columns,
            true
        )) {
            $payload['is_active'] =
                false;
        }

        if (in_array(
            'deleted_at',
            $columns,
            true
        )) {
            $payload['deleted_at'] =
                now();
        }

        if (in_array(
            'updated_at',
            $columns,
            true
        )) {
            $payload['updated_at'] =
                now();
        }

        if ($payload !== []) {
            DB::table('media_assets')
                ->where(
                    'disk',
                    'public'
                )
                ->where(
                    'path',
                    $path
                )
                ->update($payload);
        }
    }

    private function fallbackLogo(): ?object
    {
        if (! Schema::hasTable(
            'media_assets'
        )) {
            return null;
        }

        $query = DB::table(
            'media_assets'
        )
            ->where(
                'is_active',
                true
            );

        if (Schema::hasColumn(
            'media_assets',
            'deleted_at'
        )) {
            $query->whereNull(
                'deleted_at'
            );
        }

        return $query
            ->where(
                function ($q) {
                    $q
                        ->where(
                            'name',
                            'like',
                            '%logo%'
                        )
                        ->orWhere(
                            'name',
                            'like',
                            '%enfas%'
                        );
                }
            )
            ->orderByDesc('id')
            ->first();
    }
}
