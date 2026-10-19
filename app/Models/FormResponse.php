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
        'quiz_score',
        'quiz_max_score',
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
            'quiz_score' => 'decimal:2',
            'quiz_max_score' => 'decimal:2',
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

    public function getQuizInterpretationAttribute(): ?array
    {
        if (
            data_get($this->form?->settings, 'scoring_mode') !== 'weighted_options'
            || $this->quiz_score === null
        ) {
            return null;
        }

        $score = (int) $this->quiz_score;

        return match (true) {
            $score <= 12 => [
                'range' => '0–12',
                'title' => 'Sattva-dominant range',
                'summary' => 'Your responses suggest strong cognitive engagement, focus, and learning habits.',
            ],
            $score <= 26 => [
                'range' => '13–26',
                'title' => 'Rajasic influence — mild',
                'summary' => 'Your responses suggest good intellectual capacity, with some restlessness or room to strengthen focus.',
            ],
            $score <= 40 => [
                'range' => '27–40',
                'title' => 'High Rajas / early Tamas — moderate',
                'summary' => 'Your responses suggest that stress, focus, or learning habits may be worth reflecting on and supporting.',
            ],
            default => [
                'range' => '41–54',
                'title' => 'Tamas-dominant range — higher score',
                'summary' => 'Your responses suggest that intellectual engagement and daily habits may need additional support.',
            ],
        };
    }
}
