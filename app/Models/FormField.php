<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'form_fields';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'form_id',
        'section_id',
        'type',
        'label',
        'description',
        'placeholder',
        'default_value',
        'help_text',
        'is_required',
        'is_visible',
        'order_index',
        'validation_rules',
        'settings',
        'width',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required'      => 'boolean',
            'is_visible'       => 'boolean',
            'order_index'      => 'integer',
            'validation_rules' => 'array',
            'settings'         => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Field type constants
    // -------------------------------------------------------------------------

    public const TYPES = [
        'short_text', 'long_text', 'email', 'phone', 'number',
        'multiple_choice', 'checkbox', 'dropdown',
        'date', 'time', 'file', 'rating', 'linear_scale',
        'checkbox_grid', 'multiple_choice_grid',
        'section_header', 'heading', 'description', 'image',
    ];

    public const OPTION_TYPES = [
        'multiple_choice', 'checkbox', 'dropdown',
        'checkbox_grid', 'multiple_choice_grid',
    ];

    public const TEXT_TYPES = [
        'short_text', 'long_text', 'email', 'phone', 'number',
    ];

    public const LAYOUT_TYPES = [
        'section_header', 'heading', 'description', 'image',
    ];

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Determine if this field type uses selectable options.
     */
    public function hasOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES);
    }

    /**
     * Determine if this field type is a layout/display element.
     */
    public function isLayoutType(): bool
    {
        return in_array($this->type, self::LAYOUT_TYPES);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class, 'field_id')->orderBy('order_index');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(FormFieldCondition::class, 'field_id');
    }

    public function conditionTargets(): HasMany
    {
        return $this->hasMany(FormFieldCondition::class, 'condition_field_id');
    }

    public function responseValues(): HasMany
    {
        return $this->hasMany(FormResponseValue::class, 'field_id');
    }
}
