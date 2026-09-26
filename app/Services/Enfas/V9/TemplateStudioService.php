<?php

namespace App\Services\Enfas\V9;

use App\Models\MetaIntegration;
use App\Models\WaTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TemplateStudioService
{
    public function createOnMeta(WaTemplate $template): array
    {
        $integration=MetaIntegration::query()->firstOrFail();
        $components=[];

        $headerType=strtoupper((string)($template->header_type?:'TEXT'));

        if ($headerType==='IMAGE') {
            if (! $template->header_media_id) {
                throw new RuntimeException(
                    'Selecione uma imagem ativa para o cabeçalho.'
                );
            }

            $components[]=[
                'type'=>'HEADER',
                'format'=>'IMAGE',
                'example'=>[
                    'header_handle'=>[
                        $this->mediaHandle(
                            (int)$template->header_media_id,
                            $integration
                        ),
                    ],
                ],
            ];
        } elseif ($headerType==='TEXT'
            && filled($template->header_text)) {
            $components[]=[
                'type'=>'HEADER',
                'format'=>'TEXT',
                'text'=>$template->header_text,
            ];
        }

        $body=[
            'type'=>'BODY',
            'text'=>$template->body,
        ];

        if (($template->sample_values??[])!==[]) {
            $body['example']=[
                'body_text'=>[
                    array_values($template->sample_values),
                ],
            ];
        }

        $components[]=$body;

        if (filled($template->footer)) {
            $components[]=[
                'type'=>'FOOTER',
                'text'=>$template->footer,
            ];
        }

        $buttons=collect($template->buttons??[])
            ->take(3)
            ->map(fn($button)=>[
                'type'=>'QUICK_REPLY',
                'text'=>$button['text']??'Responder',
            ])
            ->values()
            ->all();

        if ($buttons!==[]) {
            $components[]=[
                'type'=>'BUTTONS',
                'buttons'=>$buttons,
            ];
        }

        $version=$integration->graph_version?:'v25.0';
        $url="https://graph.facebook.com/{$version}/{$integration->waba_id}/message_templates";

        $response=Http::withToken($integration->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($url,[
                'name'=>$template->name,
                'language'=>$template->language,
                'category'=>$template->category,
                'components'=>$components,
            ]);

        if (! $response->successful()) {
            $error=$response->json('error')??[];

            throw new RuntimeException(
                ($error['error_user_title']
                    ??$error['error_user_msg']
                    ??$error['message']
                    ??'A Meta recusou o modelo.')
                .(isset($error['code'])
                    ?' [code '.$error['code'].']'
                    :'')
                .(isset($error['error_subcode'])
                    ?', subcode '.$error['error_subcode']
                    :'')
            );
        }

        $data=$response->json();

        $template->forceFill([
            'meta_template_id'=>$data['id']??null,
            'status'=>$data['status']??'PENDING',
            'category'=>$data['category']??$template->category,
            'rejection_reason'=>null,
            'last_error'=>null,
            'last_synced_at'=>now(),
        ])->save();

        return $data;
    }

    private function mediaHandle(
        int $mediaId,
        MetaIntegration $integration
    ): string {
        $media=DB::table('media_assets')
            ->where('id',$mediaId)
            ->where('is_active',true)
            ->whereNull('deleted_at')
            ->first();

        if (! $media) {
            throw new RuntimeException(
                'Imagem não encontrada ou desativada.'
            );
        }

        if ($media->meta_handle
            && $media->meta_handle_at
            && \Illuminate\Support\Carbon::parse(
                $media->meta_handle_at
            )->gt(now()->subHours(12))) {
            return $media->meta_handle;
        }

        $absolute=Storage::disk($media->disk?:'public')
            ->path($media->path);

        if (! is_file($absolute)) {
            throw new RuntimeException(
                'Arquivo da imagem não foi encontrado no storage.'
            );
        }

        $version=$integration->graph_version?:'v25.0';
        $base="https://graph.facebook.com/{$version}";

        $session=Http::withToken($integration->access_token)
            ->acceptJson()
            ->timeout(35)
            ->withQueryParameters([
                'file_length'=>filesize($absolute),
                'file_type'=>$media->mime_type,
                'file_name'=>basename($absolute),
            ])
            ->post($base.'/app/uploads');

        if (! $session->successful()) {
            throw new RuntimeException(
                $session->json('error.message')
                ??'Falha ao iniciar upload de mídia na Meta.'
            );
        }

        $uploadId=$session->json('id');

        $upload=Http::withToken($integration->access_token)
            ->acceptJson()
            ->timeout(45)
            ->withHeaders([
                'file_offset'=>'0',
                'Content-Type'=>$media->mime_type,
            ])
            ->withBody(
                file_get_contents($absolute),
                $media->mime_type
            )
            ->post($base.'/'.$uploadId);

        if (! $upload->successful()) {
            throw new RuntimeException(
                $upload->json('error.message')
                ??'Falha ao enviar imagem para a Meta.'
            );
        }

        $handle=$upload->json('h');

        if (! $handle) {
            throw new RuntimeException(
                'A Meta não retornou o handle da imagem.'
            );
        }

        DB::table('media_assets')
            ->where('id',$mediaId)
            ->update([
                'meta_handle'=>$handle,
                'meta_handle_at'=>now(),
                'updated_at'=>now(),
            ]);

        return $handle;
    }
}
