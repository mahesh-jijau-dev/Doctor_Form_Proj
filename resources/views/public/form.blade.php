<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('theme') !== 'light' }" :class="{ 'dark': dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->title }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="bg-theme-bg text-theme-text min-h-screen antialiased">
    <div class="public-form-page">
        <div class="public-form-shell" x-data="publicFormState()">
            <div class="public-form-header">
                <div class="public-form-header-inner">
                    <div>
                        <div class="public-form-title">{{ $form->title }}</div>
                        @if($form->description)
                            <p class="public-form-description">{{ $form->description }}</p>
                        @endif
                    </div>

                    <button type="button"
                        @click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light')"
                        class="public-theme-toggle">
                        <i class="fas" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                        <span x-text="dark ? 'Light' : 'Dark'"></span>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('forms.public.submit', $form) }}" enctype="multipart/form-data" class="public-form-body">
                @csrf

                <div class="public-meta-row">
                    <div class="public-field-group public-field-half">
                        <label for="_name" class="public-label">Full Name</label>
                        <input id="_name" name="_name" type="text" value="{{ old('_name') }}" class="public-input" placeholder="Enter your full name" />
                    </div>
                    <div class="public-field-group public-field-half">
                        <label for="_email" class="public-label">Email Address</label>
                        <input id="_email" name="_email" type="email" value="{{ old('_email') }}" class="public-input" placeholder="Enter your email" />
                    </div>
                </div>

                @foreach($form->fields as $field)
                    @php
                        $fieldName = 'field_' . $field->id;
                        $conditions = $field->conditions->map(fn($c) => [
                            'condition_field_id' => $c->condition_field_id,
                            'operator' => $c->operator,
                            'condition_value' => $c->condition_value,
                            'action' => $c->action,
                        ])->toArray();
                    @endphp

                    <div class="public-question-block"
                         x-show="visible[{{ $field->id }}] !== false"
                         x-init="register({{ $field->id }}, {{ json_encode($conditions) }})"
                         style="display: none">

                        @if($field->type === 'section_header')
                            <div class="public-section-header">
                                <h2>{{ $field->label }}</h2>
                                @if($field->description)
                                    <p>{{ $field->description }}</p>
                                @endif
                            </div>
                        @elseif($field->type === 'heading')
                            <h3 class="public-heading">{{ $field->label }}</h3>
                        @elseif($field->type === 'description')
                            <p class="public-description-text">{{ $field->label }}</p>
                        @else
                            <div class="public-question">
                                <label for="{{ $fieldName }}" class="public-question-label">
                                    <span>{{ $field->label }}</span>
                                    @if($field->is_required)
                                        <span class="public-required">*</span>
                                    @endif
                                </label>

                                @if($field->description)
                                    <p class="public-question-help">{{ $field->description }}</p>
                                @endif

                                @php $hasError = $errors->has($fieldName); @endphp

                                @if($field->type === 'short_text')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="text" value="{{ old($fieldName) }}" placeholder="{{ $field->placeholder ?? 'Your answer' }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'long_text')
                                    <textarea id="{{ $fieldName }}" name="{{ $fieldName }}" rows="4" placeholder="{{ $field->placeholder ?? 'Your answer' }}" class="public-input public-textarea {{ $hasError ? 'is-invalid' : '' }}">{{ old($fieldName) }}</textarea>
                                @elseif($field->type === 'email')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="email" value="{{ old($fieldName) }}" placeholder="{{ $field->placeholder ?? 'you@example.com' }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'phone')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="tel" value="{{ old($fieldName) }}" placeholder="{{ $field->placeholder ?? '+91 98765 43210' }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'number')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="number" value="{{ old($fieldName) }}" placeholder="{{ $field->placeholder ?? '123' }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'date')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="date" value="{{ old($fieldName) }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'time')
                                    <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="time" value="{{ old($fieldName) }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}" />
                                @elseif($field->type === 'dropdown')
                                    <select id="{{ $fieldName }}" name="{{ $fieldName }}" class="public-input {{ $hasError ? 'is-invalid' : '' }}">
                                        <option value="">Select an option</option>
                                        @foreach($field->options as $option)
                                            <option value="{{ $option->value ?: $option->label }}" {{ old($fieldName) == ($option->value ?: $option->label) ? 'selected' : '' }}>
                                                {{ $option->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif($field->type === 'multiple_choice')
                                    <div class="public-option-list">
                                        @foreach($field->options as $option)
                                            <label class="public-option-item">
                                                <input type="radio" name="{{ $fieldName }}" value="{{ $option->value ?: $option->label }}" {{ old($fieldName) == ($option->value ?: $option->label) ? 'checked' : '' }}>
                                                <span>{{ $option->label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($field->type === 'checkbox')
                                    <div class="public-option-list">
                                        @foreach($field->options as $option)
                                            <label class="public-option-item">
                                                <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $option->value ?: $option->label }}" {{ is_array(old($fieldName)) && in_array($option->value ?: $option->label, old($fieldName)) ? 'checked' : '' }}>
                                                <span>{{ $option->label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($field->type === 'file')
                                    <div class="public-upload-box {{ $hasError ? 'is-invalid' : '' }}">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                        <span>Choose file</span>
                                             <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="file"
                                                 accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp"
                                                 class="public-file-input" />
                                    </div>
                                @elseif($field->type === 'rating')
                                    @php $max = (int) ($field->settings['max'] ?? 5); @endphp
                                    <div class="public-rating-group">
                                        @for($i = 1; $i <= $max; $i++)
                                            <label class="public-rating-star">
                                                <input type="radio" name="{{ $fieldName }}" value="{{ $i }}" {{ old($fieldName) == $i ? 'checked' : '' }}>
                                                <span aria-hidden="true">★</span>
                                            </label>
                                        @endfor
                                    </div>
                                @elseif($field->type === 'linear_scale')
                                    @php
                                        $min = (int) ($field->settings['min'] ?? 1);
                                        $max = (int) ($field->settings['max'] ?? 5);
                                    @endphp
                                    <div class="public-scale-group">
                                        @for($i = $min; $i <= $max; $i++)
                                            <label class="public-scale-item">
                                                <input type="radio" name="{{ $fieldName }}" value="{{ $i }}" {{ old($fieldName) == $i ? 'checked' : '' }}>
                                                <span>{{ $i }}</span>
                                            </label>
                                        @endfor
                                    </div>
                                @endif

                                @error($fieldName)
                                    <div class="public-error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($errors->any())
                    <div class="public-form-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Please check the highlighted fields and try again.</span>
                    </div>
                @endif

                <div class="public-submit-row">
                    <button type="submit" class="public-submit-button">
                        {{ $form->submit_button_text ?? 'Submit' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function publicFormState() {
            return {
                values: {},
                conditions: {},
                visible: {},
                register(fieldId, conds) {
                    this.visible[fieldId] = true;
                    if (conds && conds.length > 0) {
                        this.conditions[fieldId] = conds;
                        this.$watch('values', () => this.evaluate(fieldId));
                    }
                },
                set(fieldId, value) {
                    this.values = { ...this.values, [fieldId]: value };
                },
                evaluate(fieldId) {
                    const conds = this.conditions[fieldId];
                    if (!conds) return;
                    let show = true;
                    for (const c of conds) {
                        const v = this.values[c.condition_field_id] ?? '';
                        const vStr = Array.isArray(v) ? v.join(',') : String(v);
                        let match = false;
                        switch (c.operator) {
                            case 'equals': match = vStr === String(c.condition_value); break;
                            case 'not_equals': match = vStr !== String(c.condition_value); break;
                            case 'contains': match = vStr.includes(c.condition_value); break;
                            case 'is_empty': match = !vStr || vStr === ''; break;
                            case 'is_not_empty': match = !!vStr && vStr !== ''; break;
                            default: match = true;
                        }
                        if (c.action === 'show' && !match) show = false;
                        if (c.action === 'hide' && match) show = false;
                    }
                    this.visible = { ...this.visible, [fieldId]: show };
                }
            };
        }
    </script>
</body>
</html>
