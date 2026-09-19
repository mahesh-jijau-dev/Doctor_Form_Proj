<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormResponseValue extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'form_response_values';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'response_id',
        'field_id',
        'field_label',
        'field_type',
        'value',
        'values',
        'file_path',
        'file_original_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function response(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'response_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Return a human-readable display value for the response.
     *
     * Multi-select answers are joined with a comma; scalar values are
     * returned as-is, falling back to '-' when empty.
     */
    public function getDisplayValue(): string
    {
        if (is_array($this->values) && ! empty($this->values)) {
            return implode(', ', $this->values);
        }

        return $this->value ?? '-';
    }
}
