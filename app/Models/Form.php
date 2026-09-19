<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'forms';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'settings',
        'created_by',
        'version',
        'is_multi_section',
        'submit_button_text',
        'confirmation_message',
        'redirect_url',
        'allow_multiple_responses',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings'                 => 'array',
            'is_multi_section'         => 'boolean',
            'allow_multiple_responses' => 'boolean',
            'version'                  => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'draft']);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('order_index');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order_index');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FormAssignment::class);
    }

    /**
     * Doctors (Users) assigned to this form via form_assignments pivot.
     */
    public function assignedDoctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'form_assignments', 'form_id', 'doctor_id')
                    ->withPivot('assigned_at', 'is_active')
                    ->withTimestamps();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }
}
