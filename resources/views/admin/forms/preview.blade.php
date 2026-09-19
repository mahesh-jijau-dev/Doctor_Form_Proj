<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': dark }">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preview: {{ $form->title }}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-theme-bg text-theme-text min-h-screen">

  {{-- Preview banner --}}
  <div class="sticky top-0 z-50 bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-700 px-4 py-2 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <i class="fas fa-eye text-amber-500"></i>
      <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Preview Mode — Responses will NOT be saved</span>
    </div>
    <a href="{{ route('admin.forms.builder', $form) }}" class="btn btn-sm btn-secondary">
      <i class="fas fa-arrow-left"></i> Back to Builder
    </a>
  </div>

  <div class="max-w-2xl mx-auto px-4 py-8" x-data="previewForm()" x-init="init()">

    {{-- Form header --}}
    <div class="card p-6 mb-4" style="border-top: 6px solid rgb(var(--color-primary))">
      <h1 class="text-2xl font-bold text-theme-text mb-2">{{ $form->title }}</h1>
      @if($form->description)
        <p class="text-theme-muted">{{ $form->description }}</p>
      @endif
    </div>

    {{-- Render fields --}}
    @foreach($form->fields as $field)
      @php
        $conditions = $field->conditions->map(fn($c) => [
          'condition_field_id' => $c->condition_field_id,
          'operator' => $c->operator,
          'condition_value' => $c->condition_value,
          'action' => $c->action,
        ])->toArray();
      @endphp

      <div class="mb-3"
           x-show="visible[{{ $field->id }}] !== false"
           x-init="register({{ $field->id }}, {{ json_encode($conditions) }})"
           style="display: none">

        @if($field->type === 'section_header')
          <div class="mt-4 mb-2">
            <h2 class="text-lg font-bold text-theme-text border-b border-theme pb-2">{{ $field->label }}</h2>
            @if($field->description)
              <p class="text-sm text-theme-muted mt-1">{{ $field->description }}</p>
            @endif
          </div>

        @elseif($field->type === 'heading')
          <h3 class="text-base font-semibold text-theme-text mt-4">{{ $field->label }}</h3>

        @elseif($field->type === 'description')
          <p class="text-sm text-theme-muted">{{ $field->label }}</p>

        @else
          <div class="card p-5">
            <label class="block text-sm font-medium text-theme-text mb-1">
              {{ $field->label }}
              @if($field->is_required)
                <span class="text-theme-danger">*</span>
              @endif
            </label>
            @if($field->description)
              <p class="text-xs text-theme-muted mb-2">{{ $field->description }}</p>
            @endif

            @if($field->type === 'short_text')
              <input type="text" placeholder="{{ $field->placeholder }}"
                     @input="set({{ $field->id }}, $event.target.value)"
                     class="input-base">

            @elseif($field->type === 'long_text')
              <textarea rows="4" placeholder="{{ $field->placeholder }}"
                        @input="set({{ $field->id }}, $event.target.value)"
                        class="input-base"></textarea>

            @elseif($field->type === 'email')
              <input type="email" placeholder="{{ $field->placeholder ?: 'you@example.com' }}"
                     @input="set({{ $field->id }}, $event.target.value)"
                     class="input-base">

            @elseif($field->type === 'phone')
              <input type="tel" placeholder="{{ $field->placeholder ?: '+91 98765 43210' }}"
                     @input="set({{ $field->id }}, $event.target.value)"
                     class="input-base">

            @elseif($field->type === 'number')
              <input type="number" placeholder="{{ $field->placeholder }}"
                     @input="set({{ $field->id }}, $event.target.value)"
                     class="input-base">

            @elseif($field->type === 'date')
              <input type="date" @change="set({{ $field->id }}, $event.target.value)" class="input-base">

            @elseif($field->type === 'time')
              <input type="time" @change="set({{ $field->id }}, $event.target.value)" class="input-base">

            @elseif($field->type === 'multiple_choice')
              <div class="space-y-2 mt-1">
                @foreach($field->options as $opt)
                  <label class="flex items-center gap-3 p-3 rounded-lg border border-theme hover:border-theme-primary cursor-pointer transition-colors">
                    <input type="radio" name="field_{{ $field->id }}" value="{{ $opt->value }}"
                           @change="set({{ $field->id }}, $event.target.value)">
                    <span class="text-sm">{{ $opt->label }}</span>
                  </label>
                @endforeach
              </div>

            @elseif($field->type === 'checkbox')
              <div class="space-y-2 mt-1">
                @foreach($field->options as $opt)
                  <label class="flex items-center gap-3 p-3 rounded-lg border border-theme hover:border-theme-primary cursor-pointer transition-colors">
                    <input type="checkbox" value="{{ $opt->value }}"
                           @change="toggleCheck({{ $field->id }}, '{{ $opt->value }}', $event.target.checked)">
                    <span class="text-sm">{{ $opt->label }}</span>
                  </label>
                @endforeach
              </div>

            @elseif($field->type === 'dropdown')
              <select @change="set({{ $field->id }}, $event.target.value)" class="input-base">
                <option value="">-- Select an option --</option>
                @foreach($field->options as $opt)
                  <option value="{{ $opt->value }}">{{ $opt->label }}</option>
                @endforeach
              </select>

            @elseif($field->type === 'file')
              <div class="border-2 border-dashed border-theme rounded-lg p-6 text-center hover:border-theme-primary transition-colors">
                <i class="fas fa-cloud-upload-alt text-theme-muted text-2xl mb-2"></i>
                <p class="text-sm text-theme-muted">Click to choose file</p>
                <input type="file" class="hidden">
              </div>

            @elseif($field->type === 'rating')
              @php $maxStars = intval($field->settings['max'] ?? 5); @endphp
              <div class="flex gap-1" x-data="{ r: 0, h: 0 }">
                @for($s = 1; $s <= $maxStars; $s++)
                  <button type="button"
                          @click="r = {{ $s }}; set({{ $field->id }}, {{ $s }})"
                          @mouseenter="h = {{ $s }}" @mouseleave="h = 0"
                          class="text-2xl transition-colors focus:outline-none"
                          :class="{{ $s }} <= (h || r) ? 'text-yellow-400' : 'text-theme-muted'">
                    <i class="fas fa-star"></i>
                  </button>
                @endfor
              </div>

            @elseif($field->type === 'linear_scale')
              @php
                $scaleMin = intval($field->settings['min'] ?? 1);
                $scaleMax = intval($field->settings['max'] ?? 5);
              @endphp
              <div x-data="{ sel: null }">
                <div class="flex items-center gap-2 flex-wrap mt-1">
                  @if(!empty($field->settings['min_label']))
                    <span class="text-xs text-theme-muted">{{ $field->settings['min_label'] }}</span>
                  @endif
                  @for($n = $scaleMin; $n <= $scaleMax; $n++)
                    <button type="button"
                            @click="sel = {{ $n }}; set({{ $field->id }}, {{ $n }})"
                            class="w-10 h-10 rounded-lg border-2 text-sm font-medium transition-all"
                            :class="sel === {{ $n }} ? 'bg-theme-primary text-white border-theme-primary' : 'border-theme hover:border-theme-primary text-theme-muted'">
                      {{ $n }}
                    </button>
                  @endfor
                  @if(!empty($field->settings['max_label']))
                    <span class="text-xs text-theme-muted">{{ $field->settings['max_label'] }}</span>
                  @endif
                </div>
              </div>

            @endif
          </div>
        @endif
      </div>
    @endforeach

    {{-- Disabled submit --}}
    <div class="mt-6">
      <button disabled class="btn btn-primary btn-lg opacity-60 cursor-not-allowed">
        <i class="fas fa-paper-plane"></i>
        {{ $form->submit_button_text ?? 'Submit Form' }}
        <span class="text-xs opacity-70 ml-1">(Preview only)</span>
      </button>
    </div>
  </div>

  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
  <script>
  function previewForm() {
    return {
      values: {},
      conditions: {},
      visible: {},

      init() {
        // All fields start visible; register() sets up conditions
      },

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

      toggleCheck(fieldId, value, checked) {
        const arr = Array.isArray(this.values[fieldId]) ? [...this.values[fieldId]] : [];
        if (checked) arr.push(value); else arr.splice(arr.indexOf(value), 1);
        this.values = { ...this.values, [fieldId]: arr };
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
            case 'equals':      match = vStr === String(c.condition_value); break;
            case 'not_equals':  match = vStr !== String(c.condition_value); break;
            case 'contains':    match = vStr.includes(c.condition_value);   break;
            case 'is_empty':    match = !vStr || vStr === '';               break;
            case 'is_not_empty':match = !!vStr && vStr !== '';              break;
            default:            match = true;
          }
          if (c.action === 'show' && !match) show = false;
          if (c.action === 'hide' && match)  show = false;
        }
        this.visible = { ...this.visible, [fieldId]: show };
      }
    };
  }
  </script>
</body>
</html>
