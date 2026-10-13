<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SaveFormBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'                          => ['sometimes', 'string', 'max:255'],
            'description'                    => ['nullable', 'string'],
            'settings'                       => ['sometimes', 'array'],
            'settings.show_score'            => ['sometimes', 'boolean'],
            'fields'                         => ['sometimes', 'array'],
            'fields.*.id'                    => ['nullable', 'integer'],
            'fields.*.type'                  => ['required_with:fields', 'string'],
            'fields.*.label'                 => ['required_with:fields', 'string', 'max:255'],
            'fields.*.description'           => ['nullable', 'string'],
            'fields.*.placeholder'           => ['nullable', 'string', 'max:255'],
            'fields.*.is_required'           => ['boolean'],
            'fields.*.order_index'           => ['nullable', 'integer'],
            'fields.*.width'                 => ['nullable', 'string'],
            'fields.*.validation_rules'      => ['nullable', 'array'],
            'fields.*.settings'              => ['nullable', 'array'],
            'fields.*.settings.points'       => ['nullable', 'numeric', 'min:1', 'max:100'],
            'fields.*.settings.correct_answers' => ['nullable', 'array'],
            'fields.*.settings.correct_answers.*' => ['string'],
            'fields.*.options'               => ['nullable', 'array'],
            'fields.*.options.*.id'          => ['nullable', 'integer'],
            'fields.*.options.*.label'       => ['required_with:fields.*.options', 'string'],
            'fields.*.options.*.value'       => ['nullable', 'string'],
            'fields.*.options.*.order_index' => ['nullable', 'integer'],
            'fields.*.conditions'            => ['nullable', 'array'],
            'fields.*.conditions.*.condition_field_id' => ['nullable', 'integer'],
            'fields.*.conditions.*.operator'  => ['nullable', 'string'],
            'fields.*.conditions.*.condition_value' => ['nullable', 'string'],
            'fields.*.conditions.*.action'    => ['nullable', 'string'],
            'sections'                       => ['sometimes', 'array'],
            'sections.*.id'                  => ['nullable', 'integer'],
            'sections.*.title'               => ['required_with:sections', 'string', 'max:255'],
            'sections.*.description'         => ['nullable', 'string'],
        ];
    }
}
