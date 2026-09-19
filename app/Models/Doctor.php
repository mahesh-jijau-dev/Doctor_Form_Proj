<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'doctors';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'specialty',
        'qualification',
        'license_number',
        'bio',
        'address',
        'city',
        'state',
        'country',
        'notes',
    ];

    /**
     * Appended virtual attributes.
     *
     * @var list<string>
     */
    protected $appends = ['name', 'email'];

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getNameAttribute(): string
    {
        return $this->user->name ?? '';
    }

    public function getEmailAttribute(): string
    {
        return $this->user->email ?? '';
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Form assignments keyed on the doctor's user_id.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(FormAssignment::class, 'doctor_id', 'user_id');
    }

    /**
     * Forms assigned to this doctor (pivot: form_assignments).
     * The foreign pivot key is doctor_id, which maps to the doctor's user_id.
     */
    public function forms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'form_assignments', 'doctor_id', 'form_id', 'user_id', 'id')
                    ->withPivot('assigned_at', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Form responses submitted for this doctor (keyed on user_id).
     */
    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class, 'assigned_doctor_id', 'user_id');
    }
}
