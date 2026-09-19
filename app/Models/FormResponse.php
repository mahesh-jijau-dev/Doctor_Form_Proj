<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormResponse extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'form_responses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'form_id',
        'form_version',
        'assigned_doctor_id',
        'submitted_by_name',
        'submitted_by_email',
        'ip_address',
        'user_agent',
        'submitted_at',
        'is_complete',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_complete'  => 'boolean',
            'submitted_at' => 'datetime',
            'metadata'     => 'array',
            'form_version' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(FormResponseValue::class, 'response_id');
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Retrieve the response value record for a given field ID.
     *
     * Requires the 'values' relationship to be loaded for efficient lookup.
     */
    public function getValueForField(int $fieldId): ?FormResponseValue
    {
        return $this->values->firstWhere('field_id', $fieldId);
    }
}
