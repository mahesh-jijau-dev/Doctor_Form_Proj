<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldCondition;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use Illuminate\Support\Facades\DB;

class FormBuilderService
{
    /**
     * Save the entire form structure from builder (fields, sections, options, conditions).
     * $data = ['sections' => [...], 'fields' => [...]]
     */
    public function saveFormStructure(Form $form, array $data): void
    {
        DB::transaction(function () use ($form, $data) {
            // Update form meta
            if (isset($data['title'])) {
                $form->update([
                    'title'                => $data['title'],
                    'description'          => $data['description'] ?? $form->description,
                    'settings'             => $data['settings'] ?? $form->settings,
                    'is_multi_section'     => $data['is_multi_section'] ?? $form->is_multi_section,
                    'submit_button_text'   => $data['submit_button_text'] ?? $form->submit_button_text,
                    'confirmation_message' => $data['confirmation_message'] ?? $form->confirmation_message,
                ]);
            }

            // Handle sections
            if (isset($data['sections'])) {
                $this->syncSections($form, $data['sections']);
            }

            // Handle fields
            if (isset($data['fields'])) {
                $this->syncFields($form, $data['fields']);
            }
        });
    }

    private function syncSections(Form $form, array $sections): void
    {
        $existingIds = collect($sections)->pluck('id')->filter()->toArray();
        $form->sections()->whereNotIn('id', $existingIds)->delete();

        foreach ($sections as $index => $sectionData) {
            $section = isset($sectionData['id'])
                ? FormSection::find($sectionData['id'])
                : new FormSection(['form_id' => $form->id]);

            if (!$section) {
                $section = new FormSection(['form_id' => $form->id]);
            }

            $section->fill([
                'form_id'     => $form->id,
                'title'       => $sectionData['title'] ?? 'Section',
                'description' => $sectionData['description'] ?? null,
                'order_index' => $index,
            ])->save();
        }
    }

    private function syncFields(Form $form, array $fields): void
    {
        $existingIds = collect($fields)->pluck('id')->filter()->toArray();

        $form->fields()->whereNotIn('id', $existingIds)->each(function ($field) {
            $field->options()->delete();
            $field->conditions()->delete();
            $field->delete();
        });

        foreach ($fields as $index => $fieldData) {
            $field = isset($fieldData['id'])
                ? FormField::find($fieldData['id'])
                : new FormField(['form_id' => $form->id]);

            if (!$field) {
                $field = new FormField(['form_id' => $form->id]);
            }

            $field->fill([
                'form_id'          => $form->id,
                'section_id'       => $fieldData['section_id'] ?? null,
                'type'             => $fieldData['type'],
                'label'            => $fieldData['label'] ?? 'Question',
                'description'      => $fieldData['description'] ?? null,
                'placeholder'      => $fieldData['placeholder'] ?? null,
                'default_value'    => $fieldData['default_value'] ?? null,
                'help_text'        => $fieldData['help_text'] ?? null,
                'is_required'      => $fieldData['is_required'] ?? false,
                'is_visible'       => $fieldData['is_visible'] ?? true,
                'order_index'      => $index,
                'validation_rules' => $fieldData['validation_rules'] ?? null,
                'settings'         => $fieldData['settings'] ?? null,
                'width'            => $fieldData['width'] ?? 'full',
            ])->save();

            // Sync options
            if (isset($fieldData['options'])) {
                $this->syncOptions($field, $fieldData['options']);
            }

            // Sync conditions
            if (isset($fieldData['conditions'])) {
                $this->syncConditions($field, $fieldData['conditions']);
            }
        }
    }

    private function syncOptions(FormField $field, array $options): void
    {
        $existingIds = collect($options)->pluck('id')->filter()->toArray();
        $field->options()->whereNotIn('id', $existingIds)->delete();

        foreach ($options as $index => $optionData) {
            $option = isset($optionData['id'])
                ? FormFieldOption::find($optionData['id'])
                : new FormFieldOption(['field_id' => $field->id]);

            if (!$option) {
                $option = new FormFieldOption(['field_id' => $field->id]);
            }

            $option->fill([
                'field_id'    => $field->id,
                'label'       => $optionData['label'] ?? 'Option',
                'value'       => $optionData['value'] ?? $optionData['label'] ?? 'option',
                'order_index' => $index,
                'is_other'    => $optionData['is_other'] ?? false,
            ])->save();
        }
    }

    private function syncConditions(FormField $field, array $conditions): void
    {
        $field->conditions()->delete();

        foreach ($conditions as $conditionData) {
            if (empty($conditionData['condition_field_id'])) {
                continue;
            }

            FormFieldCondition::create([
                'field_id'           => $field->id,
                'condition_field_id' => $conditionData['condition_field_id'],
                'operator'           => $conditionData['operator'] ?? 'equals',
                'condition_value'    => $conditionData['condition_value'] ?? null,
                'action'             => $conditionData['action'] ?? 'show',
            ]);
        }
    }

    public function duplicateForm(Form $form): Form
    {
        return DB::transaction(function () use ($form) {
            $form->load(['sections', 'fields.options', 'fields.conditions']);

            $newForm = $form->replicate(['status', 'created_at', 'updated_at', 'deleted_at']);
            $newForm->title      = $form->title . ' (Copy)';
            $newForm->status     = 'draft';
            $newForm->version    = 1;
            $newForm->created_by = auth()->id();
            $newForm->save();

            // Duplicate sections and build an old→new ID map
            $sectionMap = [];
            foreach ($form->sections as $section) {
                $newSection          = $section->replicate(['created_at', 'updated_at']);
                $newSection->form_id = $newForm->id;
                $newSection->save();
                $sectionMap[$section->id] = $newSection->id;
            }

            // Duplicate fields and build an old→new ID map
            $fieldMap = [];
            foreach ($form->fields as $field) {
                $newField             = $field->replicate(['created_at', 'updated_at']);
                $newField->form_id    = $newForm->id;
                $newField->section_id = $field->section_id
                    ? ($sectionMap[$field->section_id] ?? null)
                    : null;
                $newField->save();
                $fieldMap[$field->id] = $newField->id;

                // Duplicate options
                foreach ($field->options as $option) {
                    $newOption           = $option->replicate(['created_at', 'updated_at']);
                    $newOption->field_id = $newField->id;
                    $newOption->save();
                }
            }

            // Re-map and duplicate conditions using the new field IDs
            foreach ($form->fields as $field) {
                foreach ($field->conditions as $condition) {
                    if (
                        isset($fieldMap[$condition->field_id]) &&
                        isset($fieldMap[$condition->condition_field_id])
                    ) {
                        FormFieldCondition::create([
                            'field_id'           => $fieldMap[$condition->field_id],
                            'condition_field_id' => $fieldMap[$condition->condition_field_id],
                            'operator'           => $condition->operator,
                            'condition_value'    => $condition->condition_value,
                            'action'             => $condition->action,
                        ]);
                    }
                }
            }

            return $newForm;
        });
    }
}
