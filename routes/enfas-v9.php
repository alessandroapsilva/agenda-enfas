<?php

use App\Http\Controllers\Enfas\V9\MasterDataController;
use App\Http\Controllers\Enfas\V9\MediaLibraryController;
use App\Http\Controllers\Enfas\V9\TemplateStudioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    foreach ([
        'locais'=>'locations',
    ] as $path=>$entity) {
        Route::get(
            '/'.$path,
            fn(\Illuminate\Http\Request $request)=>
                app(MasterDataController::class)
                    ->index($request,$entity)
        )->name('v9.'.$entity.'.index');

        Route::post(
            '/'.$path,
            fn(\Illuminate\Http\Request $request)=>
                app(MasterDataController::class)
                    ->store($request,$entity)
        )->name('v9.'.$entity.'.store');

        Route::patch(
            '/'.$path.'/{id}',
            fn(\Illuminate\Http\Request $request,int $id)=>
                app(MasterDataController::class)
                    ->update($request,$entity,$id)
        )->name('v9.'.$entity.'.update');

        Route::patch(
            '/'.$path.'/{id}/status',
            fn(int $id)=>
                app(MasterDataController::class)
                    ->toggle($entity,$id)
        )->name('v9.'.$entity.'.toggle');

        Route::delete(
            '/'.$path.'/{id}',
            fn(int $id)=>
                app(MasterDataController::class)
                    ->destroy($entity,$id)
        )->name('v9.'.$entity.'.delete');
    }

    Route::get(
        '/whatsapp/templates',
        [TemplateStudioController::class,'index']
    )->name('v9.templates.index');

    Route::post(
        '/whatsapp/templates',
        [TemplateStudioController::class,'store']
    )->name('v9.templates.store');

    Route::patch(
        '/whatsapp/templates/{template}',
        [TemplateStudioController::class,'update']
    )->name('v9.templates.update');

    Route::post(
        '/whatsapp/templates/{template}/enviar',
        [TemplateStudioController::class,'send']
    )->name('v9.templates.send');

    Route::post(
        '/whatsapp/templates/{template}/duplicar',
        [TemplateStudioController::class,'duplicate']
    )->name('v9.templates.duplicate');

    Route::patch(
        '/whatsapp/templates/{template}/status',
        [TemplateStudioController::class,'toggle']
    )->name('v9.templates.toggle');

    Route::post(
        '/whatsapp/templates/{template}/arquivar',
        [TemplateStudioController::class,'archive']
    )->name('v9.templates.archive');

    Route::delete(
        '/whatsapp/templates/{template}',
        [TemplateStudioController::class,'destroy']
    )->name('v9.templates.delete');

    Route::post(
        '/whatsapp/templates/sincronizar',
        [TemplateStudioController::class,'sync']
    )->name('v9.templates.sync');

    Route::get(
        '/whatsapp/midia',
        [MediaLibraryController::class,'index']
    )->name('v9.media.index');

    Route::post(
        '/whatsapp/midia',
        [MediaLibraryController::class,'store']
    )->name('v9.media.store');

    Route::patch(
        '/whatsapp/midia/{id}/status',
        [MediaLibraryController::class,'toggle']
    )->name('v9.media.toggle');

    Route::delete(
        '/whatsapp/midia/{id}',
        [MediaLibraryController::class,'destroy']
    )->name('v9.media.delete');

    Route::redirect(
        '/premium/profissionais',
        '/profissionais'
    );

    Route::redirect(
        '/premium/pacientes',
        '/pacientes'
    );

    Route::redirect(
        '/premium/servicos',
        '/servicos'
    );

    Route::redirect(
        '/premium/unidades',
        '/locais'
    );

    Route::redirect(
        '/premium/whatsapp/modelos',
        '/whatsapp/templates'
    );

    Route::redirect(
        '/premium/whatsapp/midia',
        '/whatsapp/midia'
    );

    Route::redirect(
        '/automacoes',
        '/whatsapp/automacoes'
    );
});
