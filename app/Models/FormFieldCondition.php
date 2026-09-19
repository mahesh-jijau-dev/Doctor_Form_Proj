<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFieldCondition extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'form_field_conditions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'field_id',
        'condition_field_id',
        'operator',
        'condition_value',
        'action',
    ];

    // -------------------------------------------------------------------------
    // Condition constants
    // -------------------------------------------------------------------------

    public const OPERATORS = [
        'equals',
        'not_equals',
        'contains',
        'greater_than',
        'less_than',
        'is_empty',
        'is_not_empty',
    ];

    public const ACTIONS = ['show', 'hide'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The field that owns / is controlled by this condition.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }

    /**
     * The field whose value is evaluated as the condition trigger.
     */
    public function conditionField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'condition_field_id');
    }
}
