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
        $answers = is_array($this->values) && ! empty($this->values)
            ? array_map('strval', $this->values)
            : ($this->value === null ? [] : [(string) $this->value]);

        if ($answers === []) {
            return '-';
        }

        $options = $this->field?->options ?? collect();
        $labels = collect($answers)->map(function (string $answer) use ($options) {
            $option = $options->first(fn ($option) =>
                (string) ($option->value !== null && $option->value !== '' ? $option->value : $option->label) === $answer
            );

            return $option?->label ?? $answer;
        });

        return $labels->implode(', ');
    }

    public function getCorrectAnswerDisplay(): ?string
    {
        $field = $this->field;
        $correctAnswers = data_get($field?->settings, 'correct_answers', []);

        if (! is_array($correctAnswers) || $correctAnswers === []) {
            return null;
        }

        $options = $field->options;
        $labels = collect($correctAnswers)->map(function ($answer) use ($options) {
            $answer = (string) $answer;
            $option = $options->first(fn ($option) =>
                (string) ($option->value !== null && $option->value !== '' ? $option->value : $option->label) === $answer
            );

            return $option?->label;
        })->filter();

        return $labels->isNotEmpty() ? $labels->implode(', ') : null;
    }

    public function getAwardedScore(): ?float
    {
        $field = $this->field;

        if (! $field) {
            return null;
        }

        $answers = is_array($this->values) && ! empty($this->values)
            ? array_map('strval', $this->values)
            : ($this->value === null ? [] : [(string) $this->value]);
        $options = $field->options;
        $scoredOptions = $options->filter(fn ($option) => $option->score !== null);
        $assessmentScored = (bool) data_get($field->settings, 'assessment_scored');

        if ($scoredOptions->isNotEmpty() || $assessmentScored) {
            $scoreByValue = $options->mapWithKeys(function ($option) use ($assessmentScored) {
                $value = (string) ($option->value !== null && $option->value !== '' ? $option->value : $option->label);
                $score = $option->score;

                if ($score === null && $assessmentScored && is_numeric($value)) {
                    $score = (float) $value;
                }

                return $score === null ? [] : [$value => (float) $score];
            });

            if ($scoreByValue->isNotEmpty()) {
                return collect($answers)->sum(fn (string $answer) => $scoreByValue->get($answer, 0.0));
            }
        }

        $correctAnswers = array_map('strval', data_get($field->settings, 'correct_answers', []));
        if ($correctAnswers === []) {
            return null;
        }

        sort($correctAnswers, SORT_STRING);
        sort($answers, SORT_STRING);

        return $answers === $correctAnswers
            ? (float) data_get($field->settings, 'points', 1)
            : 0.0;
    }

    public function getExportDisplayValue(): string
    {
        $details = ['Answer: ' . $this->getDisplayValue()];
        $correctAnswer = $this->getCorrectAnswerDisplay();
        $score = $this->getAwardedScore();

        if ($correctAnswer !== null) {
            $details[] = 'Correct answer: ' . $correctAnswer;
        }

        if ($score !== null) {
            $details[] = 'Score: ' . rtrim(rtrim(number_format($score, 2), '0'), '.') . ' points';
        }

        return implode(' | ', $details);
    }
}
