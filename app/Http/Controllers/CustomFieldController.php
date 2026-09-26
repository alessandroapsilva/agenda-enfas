<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
    public function index()
    {
        $fields = CustomField::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'custom-fields.index',
            compact('fields')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],

            'entity_type' => [
                'required',
                Rule::in([
                    'appointment',
                    'patient',
                ]),
            ],

            'field_type' => [
                'required',
                Rule::in([
                    'text',
                    'textarea',
                    'number',
                    'date',
                    'select',
                    'checkbox',
                ]),
            ],

            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'options_text' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $baseSlug = Str::slug(
            $data['name'],
            '_'
        );

        $slug = $baseSlug;
        $i = 2;

        while (
            CustomField::where('slug', $slug)->exists()
        ) {
            $slug = $baseSlug . '_' . $i++;
        }

        $options = null;

        if (
            $data['field_type'] === 'select'
            && ! empty($data['options_text'])
        ) {
            $options = collect(
                preg_split(
                    '/\r\n|\r|\n/',
                    $data['options_text']
                )
            )
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values()
                ->all();
        }

        CustomField::create([
            'name' => trim($data['name']),
            'slug' => $slug,
            'entity_type' => $data['entity_type'],
            'field_type' => $data['field_type'],
            'placeholder' => $data['placeholder'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with(
            'success',
            'Campo personalizado criado.'
        );
    }

    public function toggle(CustomField $field)
    {
        $field->update([
            'is_active' => ! $field->is_active,
        ]);

        return back()->with(
            'success',
            $field->is_active
                ? 'Campo ativado.'
                : 'Campo desativado.'
        );
    }
}
