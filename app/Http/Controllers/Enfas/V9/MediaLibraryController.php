<?php

namespace App\Http\Controllers\Enfas\V9;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    public function index()
    {
        $rows=DB::table('media_assets')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return view(
            'enfas.v9.media',
            compact('rows')
        );
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'file'=>[
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:8192',
            ],
        ]);

        $file=$request->file('file');
        $filename=Str::uuid().'.'
            .strtolower(
                $file->getClientOriginalExtension()
            );

        $path=$file->storeAs(
            'whatsapp/media',
            $filename,
            'public'
        );

        DB::table('media_assets')->insert([
            'name'=>$data['name'],
            'kind'=>'image',
            'disk'=>'public',
            'path'=>$path,
            'mime_type'=>$file->getMimeType()
                ?: 'image/jpeg',
            'size'=>$file->getSize()?:0,
            'is_active'=>true,
            'uploaded_by'=>auth()->id(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return back()->with(
            'success',
            'Imagem adicionada à biblioteca.'
        );
    }

    public function toggle(int $id)
    {
        $row=DB::table('media_assets')
            ->whereNull('deleted_at')
            ->find($id);

        abort_unless($row,404);

        DB::table('media_assets')
            ->where('id',$id)
            ->update([
                'is_active'=>! (bool)$row->is_active,
                'updated_at'=>now(),
            ]);

        return back()->with(
            'success',
            'Status da imagem atualizado.'
        );
    }

    public function destroy(int $id)
    {
        $row=DB::table('media_assets')
            ->whereNull('deleted_at')
            ->find($id);

        abort_unless($row,404);

        $used=DB::table('wa_templates')
            ->where('header_media_id',$id)
            ->whereNull('archived_at')
            ->exists();

        if ($used) {
            return back()->withErrors([
                'delete'=>
                    'A imagem está ligada a um modelo. '
                    .'Troque a imagem do modelo antes de excluí-la.',
            ]);
        }

        Storage::disk($row->disk?:'public')
            ->delete($row->path);

        DB::table('media_assets')
            ->where('id',$id)
            ->update([
                'is_active'=>false,
                'deleted_at'=>now(),
                'updated_at'=>now(),
            ]);

        return back()->with(
            'success',
            'Imagem excluída.'
        );
    }
}
