@php
$builderFields = $form->fields->map(function($f) {
    return [
        'id'               => $f->id,
        'type'             => $f->type,
        'label'            => $f->label,
        'description'      => $f->description ?? '',
        'placeholder'      => $f->placeholder ?? '',
        'is_required'      => (bool) $f->is_required,
        'order_index'      => $f->order_index,
        'options'          => $f->options->map(function($o) {
                                  return ['id' => $o->id, 'label' => $o->label, 'value' => $o->value];
                              })->toArray(),
        'conditions'       => $f->conditions->map(function($c) {
                                  return [
                                      'condition_field_id' => $c->condition_field_id,
                                      'operator'           => $c->operator,
                                      'condition_value'    => $c->condition_value,
                                      'action'             => $c->action,
                                  ];
                              })->toArray(),
        'settings'         => $f->settings ?: new stdClass(),
        'validation_rules' => $f->validation_rules ?: new stdClass(),
        'width'            => $f->width ?? 'full',
    ];
})->values()->toArray();
@endphp
@extends('layouts.admin')
@section('title', 'Form Builder - ' . $form->title)
@section('page-title', 'Form Builder')

@push('styles')
<style>
.builder-layout { display: flex; gap: 1rem; min-height: 640px; }
.sidebar-panel { width: 240px; flex-shrink: 0; max-height: calc(100vh - 190px); overflow-y: auto; }
.canvas-panel { flex: 1; min-width: 0; max-height: calc(100vh - 190px); overflow-y: auto; }
.config-panel { width: 280px; flex-shrink: 0; max-height: calc(100vh - 190px); overflow-y: auto; }
.field-block { border: 2px solid transparent; border-radius: 0.75rem; transition: all 0.15s; cursor: pointer; background-color: rgb(var(--color-surface)); border-color: rgb(var(--color-border)); }
.field-block:hover { border-color: rgb(var(--color-primary)); }
.field-block.is-selected { border-color: rgb(var(--color-primary)); box-shadow: 0 0 0 3px rgba(var(--color-primary), 0.12); }
.field-block.sortable-ghost { opacity: 0.35; border: 2px dashed rgb(var(--color-primary)); }
.drag-handle { cursor: grab; color: rgb(var(--color-text-muted)); }
.drag-handle:active { cursor: grabbing; }
.toggle-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
.toggle-switch input { display: none; }
.toggle-track { width: 36px; height: 20px; border-radius: 10px; transition: background 0.2s; }
.toggle-thumb { position: absolute; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: white; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
@media (max-width: 1100px) {
  .builder-layout { display: grid; grid-template-columns: 210px minmax(0, 1fr); min-height: 0; }
  .sidebar-panel { width: auto; max-height: none; }
  .canvas-panel { max-height: none; }
  .config-panel { grid-column: 1 / -1; width: auto; max-height: none; }
}
@media (max-width: 700px) {
  .builder-layout { display: flex; flex-direction: column; gap: 0.75rem; }
  .sidebar-panel, .canvas-panel, .config-panel { width: 100%; max-height: none; overflow: visible; }
  .builder-layout .sidebar-panel { order: 2; }
  .builder-layout .canvas-panel { order: 1; }
  .builder-layout .config-panel { order: 3; }
}
</style>
@endpush

@section('content')
<div x-data="formBuilder()" x-init="init()">

  {{-- Top action bar --}}
  <div class="flex items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.forms.index') }}" class="btn btn-ghost btn-sm">
      <i class="fas fa-arrow-left"></i>
    </a>
    <input type="text" x-model="formTitle" @blur="markDirty()"
           class="text-lg font-semibold bg-transparent border-0 outline-none text-theme-text px-2 py-1 rounded hover:bg-theme-surface-2 focus:bg-theme-surface-2 transition-colors min-w-0 flex-1"
           placeholder="Form Title">
    <div class="flex items-center gap-1.5 text-xs">
      <span x-show="isSaving" class="text-theme-muted" style="display:none"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
      <span x-show="isDirty && !isSaving" class="badge badge-warning" style="display:none">Unsaved changes</span>
      <span x-show="justSaved" class="badge badge-success" style="display:none"><i class="fas fa-check"></i> Saved</span>
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <a href="{{ route('admin.forms.preview', $form) }}" target="_blank" class="btn btn-secondary btn-sm">
        <i class="fas fa-eye"></i> Preview
      </a>
      <button @click="saveDraft()" :disabled="isSaving" class="btn btn-secondary btn-sm">
        <i class="fas fa-save"></i> Save
      </button>
      @if($form->status === 'published')
      <form action="{{ route('admin.forms.unpublish', $form) }}" method="POST" class="inline">
        @csrf @method('PATCH')
        <button class="btn btn-warning btn-sm"><i class="fas fa-eye-slash"></i> Unpublish</button>
      </form>
      @else
      <button @click="saveAndPublish()" class="btn btn-primary btn-sm">
        <i class="fas fa-globe"></i> Publish
      </button>
      @endif
    </div>
  </div>

  {{-- Three-column layout --}}
  <div class="builder-layout">

    {{-- LEFT: Field type palette --}}
    <div class="sidebar-panel card p-3">
      <p class="text-xs font-bold uppercase tracking-widest text-theme-muted mb-3 px-1">Basic Fields</p>
      @foreach($fieldTypes['basic'] as $ft)
      <button @click="addField('{{ $ft['type'] }}')"
              class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left transition-all hover:bg-theme-primary hover:text-white group mb-0.5">
        <span class="w-6 text-center text-sm"><i class="fas {{ $ft['icon'] }}"></i></span>
        <span class="text-sm">{{ $ft['label'] }}</span>
        <i class="fas fa-plus text-xs ml-auto opacity-0 group-hover:opacity-100"></i>
      </button>
      @endforeach

      <p class="text-xs font-bold uppercase tracking-widest text-theme-muted mb-3 px-1 mt-4">Advanced</p>
      @foreach($fieldTypes['advanced'] as $ft)
      <button @click="addField('{{ $ft['type'] }}')"
              class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left transition-all hover:bg-theme-primary hover:text-white group mb-0.5">
        <span class="w-6 text-center text-sm"><i class="fas {{ $ft['icon'] }}"></i></span>
        <span class="text-sm">{{ $ft['label'] }}</span>
        <i class="fas fa-plus text-xs ml-auto opacity-0 group-hover:opacity-100"></i>
      </button>
      @endforeach
    </div>

    {{-- CENTER: Canvas --}}
    <div class="canvas-panel">
      {{-- Form header card --}}
      <div class="card p-5 mb-3" style="border-top: 5px solid rgb(var(--color-primary))">
        <input type="text" x-model="formTitle" placeholder="Form Title"
               class="text-xl font-bold w-full bg-transparent border-0 outline-none text-theme-text mb-1 focus:ring-0" @input="markDirty()">
        <input type="text" x-model="formDescription" placeholder="Form description (optional)"
               class="text-sm w-full bg-transparent border-0 outline-none text-theme-muted focus:ring-0" @input="markDirty()">
      </div>

      {{-- Fields list (sortable) --}}
      <div id="fieldsContainer" class="space-y-2 min-h-16 pb-4">
        <template x-for="(field, idx) in fields" :key="field.id">
          <div :id="'fb-' + field.id"
               class="field-block p-4"
               :class="{ 'is-selected': selectedId === field.id }"
               @click="selectedId = field.id">
            <div class="flex items-start gap-3">
              <span class="drag-handle pt-0.5 flex-shrink-0"><i class="fas fa-grip-vertical"></i></span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                  <span class="text-sm font-medium text-theme-text truncate" x-text="field.label || 'Untitled'"></span>
                  <span class="text-theme-danger text-sm" x-show="field.is_required">*</span>
                  <span class="badge badge-muted text-xs capitalize" x-text="field.type.replace(/_/g,' ')"></span>
                </div>
                <div x-html="previewHtml(field)" class="pointer-events-none select-none"></div>
              </div>
              <div class="flex items-center gap-0.5 flex-shrink-0">
                <button @click.stop="cloneField(field)" class="btn btn-ghost btn-sm w-7 h-7 p-0" title="Duplicate">
                  <i class="fas fa-copy text-xs"></i>
                </button>
                <button @click.stop="removeField(field.id)" class="btn btn-ghost btn-sm w-7 h-7 p-0 text-theme-danger" title="Delete">
                  <i class="fas fa-trash text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <div x-show="fields.length === 0" class="card p-12 text-center" style="display:none">
        <i class="fas fa-hand-pointer text-theme-muted text-4xl mb-3"></i>
        <p class="text-theme-muted text-sm">Click any field type on the left to add it to your form</p>
      </div>
    </div>

    {{-- RIGHT: Configuration panel --}}
    <div class="config-panel card p-4">
      <template x-if="!selected">
        <div class="text-center py-10">
          <i class="fas fa-sliders-h text-theme-muted text-3xl mb-3"></i>
          <p class="text-sm text-theme-muted">Click a field to configure it</p>
        </div>
      </template>

      <template x-if="selected">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-theme-text">Field Settings</h3>
            <span class="badge badge-primary text-xs capitalize" x-text="selected.type.replace(/_/g,' ')"></span>
          </div>

          {{-- Label --}}
          <div>
            <label class="block text-xs font-semibold text-theme-muted mb-1 uppercase tracking-wide">Label</label>
            <input type="text" x-model="selected.label" @input="markDirty()" class="input-base text-sm" placeholder="Field label">
          </div>

          {{-- Description --}}
          <div>
            <label class="block text-xs font-semibold text-theme-muted mb-1 uppercase tracking-wide">Description</label>
            <input type="text" x-model="selected.description" @input="markDirty()" class="input-base text-sm" placeholder="Optional helper text">
          </div>

          {{-- Placeholder --}}
          <template x-if="['short_text','long_text','email','phone','number'].includes(selected.type)">
            <div>
              <label class="block text-xs font-semibold text-theme-muted mb-1 uppercase tracking-wide">Placeholder</label>
              <input type="text" x-model="selected.placeholder" @input="markDirty()" class="input-base text-sm" placeholder="Placeholder text">
            </div>
          </template>

          {{-- Required toggle --}}
          <template x-if="!['section_header','heading','description'].includes(selected.type)">
            <div class="flex items-center justify-between py-2.5 border-t border-theme">
              <div>
                <p class="text-sm font-medium text-theme-text">Required</p>
                <p class="text-xs text-theme-muted">User must fill this field</p>
              </div>
              <button @click="selected.is_required = !selected.is_required; markDirty()"
                      class="relative w-10 h-6 rounded-full transition-colors flex-shrink-0"
                      :class="selected.is_required ? 'bg-theme-primary' : 'bg-theme-surface-2 border border-theme'">
                <span class="absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-all"
                      :class="selected.is_required ? 'left-5' : 'left-1'"></span>
              </button>
            </div>
          </template>

          {{-- Options --}}
          <template x-if="['multiple_choice','checkbox','dropdown'].includes(selected.type)">
            <div class="border-t border-theme pt-3">
              <p class="text-xs font-semibold text-theme-muted mb-2 uppercase tracking-wide">Options</p>
              <div class="space-y-1.5" id="optionsContainer">
                <template x-for="(opt, oi) in selected.options" :key="oi">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-grip-vertical text-xs text-theme-muted"></i>
                    <input type="text" x-model="opt.label"
                           @input="opt.value = opt.label.toLowerCase().replace(/[^a-z0-9]+/g,'_'); markDirty()"
                           class="input-base text-sm flex-1 py-1.5" placeholder="Option label">
                    <button @click="selected.options.splice(oi,1); markDirty()" class="w-6 h-6 flex items-center justify-center text-theme-danger hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                      <i class="fas fa-times text-xs"></i>
                    </button>
                  </div>
                </template>
              </div>
              <button @click="selected.options.push({label:'Option '+(selected.options.length+1), value:'option_'+(selected.options.length+1)}); markDirty()"
                      class="btn btn-ghost btn-sm mt-2 w-full text-left">
                <i class="fas fa-plus text-xs"></i> Add Option
              </button>
            </div>
          </template>

          {{-- Rating settings --}}
          <template x-if="selected.type === 'rating'">
            <div class="border-t border-theme pt-3">
              <label class="block text-xs font-semibold text-theme-muted mb-1 uppercase tracking-wide">Max Stars</label>
              <select x-model="selected.settings.max" @change="markDirty()" class="input-base text-sm">
                <option value="5">5 stars</option>
                <option value="10">10 stars</option>
              </select>
            </div>
          </template>

          {{-- Linear scale settings --}}
          <template x-if="selected.type === 'linear_scale'">
            <div class="border-t border-theme pt-3 space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-theme-muted mb-1">Min</label>
                  <input type="number" x-model="selected.settings.min" @input="markDirty()" class="input-base text-sm">
                </div>
                <div>
                  <label class="block text-xs text-theme-muted mb-1">Max</label>
                  <input type="number" x-model="selected.settings.max" @input="markDirty()" class="input-base text-sm">
                </div>
              </div>
              <div>
                <label class="block text-xs text-theme-muted mb-1">Min Label</label>
                <input type="text" x-model="selected.settings.min_label" @input="markDirty()" class="input-base text-sm" placeholder="e.g. Poor">
              </div>
              <div>
                <label class="block text-xs text-theme-muted mb-1">Max Label</label>
                <input type="text" x-model="selected.settings.max_label" @input="markDirty()" class="input-base text-sm" placeholder="e.g. Excellent">
              </div>
            </div>
          </template>

          {{-- Validation (text fields) --}}
          <template x-if="['short_text','long_text','number'].includes(selected.type)">
            <div class="border-t border-theme pt-3">
              <p class="text-xs font-semibold text-theme-muted mb-2 uppercase tracking-wide">Validation</p>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-theme-muted mb-1">Min</label>
                  <input type="number" x-model="selected.validation_rules.min" @input="markDirty()" class="input-base text-sm">
                </div>
                <div>
                  <label class="block text-xs text-theme-muted mb-1">Max</label>
                  <input type="number" x-model="selected.validation_rules.max" @input="markDirty()" class="input-base text-sm">
                </div>
              </div>
            </div>
          </template>

          {{-- Conditional Logic --}}
          <template x-if="!['section_header','heading','description'].includes(selected.type)">
            <div class="border-t border-theme pt-3" x-data="{expanded: false}">
              <button @click="expanded = !expanded" class="flex items-center justify-between w-full text-xs font-semibold text-theme-muted uppercase tracking-wide mb-2">
                <span>Conditional Logic</span>
                <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </button>
              <div x-show="expanded" style="display:none" class="space-y-2">
                <template x-for="(cond, ci) in selected.conditions" :key="ci">
                  <div class="bg-theme-surface-2 rounded-lg p-2.5 space-y-2">
                    <select x-model="cond.condition_field_id" @change="markDirty()" class="input-base text-xs">
                      <option value="">-- Watch field --</option>
                      <template x-for="f in fields.filter(f => f.id !== selected.id)" :key="f.id">
                        <option :value="f.id" x-text="f.label || 'Untitled'"></option>
                      </template>
                    </select>
                    <div class="flex gap-1.5">
                      <select x-model="cond.operator" @change="markDirty()" class="input-base text-xs flex-1">
                        <option value="equals">equals</option>
                        <option value="not_equals">not equals</option>
                        <option value="contains">contains</option>
                        <option value="is_empty">is empty</option>
                        <option value="is_not_empty">not empty</option>
                      </select>
                      <input type="text" x-model="cond.condition_value" @input="markDirty()" class="input-base text-xs flex-1" placeholder="value">
                    </div>
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-1.5">
                        <span class="text-xs text-theme-muted">then</span>
                        <select x-model="cond.action" @change="markDirty()" class="input-base text-xs w-20">
                          <option value="show">Show</option>
                          <option value="hide">Hide</option>
                        </select>
                        <span class="text-xs text-theme-muted">this field</span>
                      </div>
                      <button @click="selected.conditions.splice(ci,1); markDirty()" class="text-theme-danger text-xs hover:underline">Remove</button>
                    </div>
                  </div>
                </template>
                <button @click="selected.conditions.push({condition_field_id:'',operator:'equals',condition_value:'',action:'show'}); markDirty()"
                        class="btn btn-ghost btn-sm w-full text-left">
                  <i class="fas fa-plus text-xs"></i> Add Condition
                </button>
              </div>
            </div>
          </template>

        </div>
      </template>
    </div>

  </div>{{-- /builder-layout --}}
</div>
@endsection

@php
$builderFields = $form->fields->map(function($f) {
    return [
        'id'               => $f->id,
        'type'             => $f->type,
        'label'            => $f->label,
        'description'      => $f->description ?? '',
        'placeholder'      => $f->placeholder ?? '',
        'is_required'      => (bool) $f->is_required,
        'order_index'      => $f->order_index,
        'options'          => $f->options->map(function($o) {
                                  return ['id' => $o->id, 'label' => $o->label, 'value' => $o->value];
                              })->toArray(),
        'conditions'       => $f->conditions->map(function($c) {
                                  return [
                                      'condition_field_id' => $c->condition_field_id,
                                      'operator'           => $c->operator,
                                      'condition_value'    => $c->condition_value,
                                      'action'             => $c->action,
                                  ];
                              })->toArray(),
        'settings'         => $f->settings ?: new stdClass(),
        'validation_rules' => $f->validation_rules ?: new stdClass(),
        'width'            => $f->width ?? 'full',
    ];
})->values()->toArray();
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function formBuilder() {
  return {
    formTitle: @json($form->title),
    formDescription: @json($form->description ?? ''),
    fields: @json($builderFields),
    selectedId: null,
    isDirty: false,
    isSaving: false,
    justSaved: false,
    sortableInstance: null,

    get selected() {
      return this.fields.find(f => f.id === this.selectedId) ?? null;
    },

    init() {
      this.$nextTick(() => this.initSortable());
      window.addEventListener('beforeunload', e => {
        if (this.isDirty) { e.preventDefault(); e.returnValue = ''; }
      });
    },

    initSortable() {
      const el = document.getElementById('fieldsContainer');
      if (!el || this.sortableInstance) return;
      this.sortableInstance = Sortable.create(el, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'sortable-ghost',
        onEnd: evt => {
          const [moved] = this.fields.splice(evt.oldIndex, 1);
          this.fields.splice(evt.newIndex, 0, moved);
          this.fields.forEach((f, i) => f.order_index = i);
          this.markDirty();
        }
      });
    },

    markDirty() { this.isDirty = true; this.justSaved = false; },

    async addField(type) {
      try {
        const res = await fetch('{{ route("admin.forms.builder.add-field", $form) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
          },
          body: JSON.stringify({ type })
        });
        const data = await res.json();
        if (data.success) {
          const f = {
            ...data.field,
            options: data.field.options || [],
            conditions: [],
            settings: data.field.settings || {},
            validation_rules: data.field.validation_rules || {}
          };
          this.fields.push(f);
          this.selectedId = f.id;
          this.$nextTick(() => {
            document.getElementById('fb-'+f.id)?.scrollIntoView({behavior:'smooth', block:'nearest'});
          });
        }
      } catch(e) { alert('Failed to add field. Try again.'); }
    },

    async removeField(id) {
      if (!confirm('Delete this field?')) return;
      try {
        const res = await fetch(`/admin/forms/{{ $form->id }}/builder/field/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (data.success) {
          this.fields = this.fields.filter(f => f.id !== id);
          if (this.selectedId === id) this.selectedId = null;
        }
      } catch(e) { console.error(e); }
    },

    async cloneField(field) {
      const type = field.type;
      await this.addField(type);
      const copy = this.fields[this.fields.length - 1];
      if (copy) {
        copy.label = field.label + ' (Copy)';
        copy.is_required = field.is_required;
        copy.placeholder = field.placeholder;
        if (field.options?.length) {
          copy.options = JSON.parse(JSON.stringify(field.options));
        }
        this.markDirty();
      }
    },

    getPayload() {
      return {
        title: this.formTitle,
        description: this.formDescription,
        fields: this.fields.map((f, i) => ({
          id: typeof f.id === 'number' ? f.id : null,
          type: f.type,
          label: f.label,
          description: f.description,
          placeholder: f.placeholder,
          is_required: !!f.is_required,
          order_index: i,
          options: (f.options || []).map((o,oi) => ({ id: typeof o.id === 'number' ? o.id : null, label: o.label, value: o.value, order_index: oi })),
          conditions: f.conditions || [],
          settings: f.settings || {},
          validation_rules: f.validation_rules || {},
          width: f.width || 'full'
        }))
      };
    },

    async saveDraft() {
      this.isSaving = true;
      try {
        const res = await fetch('{{ route("admin.forms.builder.save", $form) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
          },
          body: JSON.stringify(this.getPayload())
        });
        const data = await res.json();
        if (data.success) {
          this.isDirty = false;
          this.justSaved = true;
          setTimeout(() => this.justSaved = false, 3000);
          // Reload only if there are temp-id fields to get real IDs
          const hasTempIds = this.fields.some(f => typeof f.id === 'string');
          if (hasTempIds) window.location.reload();
        } else {
          alert('Save failed: ' + (data.message || 'Unknown error'));
        }
      } catch(e) {
        alert('Failed to save. Please check your connection.');
      } finally {
        this.isSaving = false;
      }
    },

    async saveAndPublish() {
      await this.saveDraft();
      if (!this.isDirty) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.forms.publish", $form) }}';
        form.innerHTML = `@csrf<input name="_method" value="PATCH">`;
        document.body.appendChild(form);
        form.submit();
      }
    },

    previewHtml(field) {
      const cls = 'w-full px-3 py-1.5 rounded border text-xs bg-theme-surface-2 border-theme text-theme-muted pointer-events-none select-none';
      switch(field.type) {
        case 'short_text':
          return `<input class="${cls}" placeholder="${field.placeholder || 'Short answer'}" disabled>`;
        case 'long_text':
          return `<textarea rows="2" class="${cls}" placeholder="${field.placeholder || 'Long answer'}" disabled></textarea>`;
        case 'email':
          return `<input class="${cls}" placeholder="${field.placeholder || 'Email address'}" disabled>`;
        case 'phone':
          return `<input class="${cls}" placeholder="${field.placeholder || 'Phone number'}" disabled>`;
        case 'number':
          return `<input class="${cls}" placeholder="${field.placeholder || '0'}" disabled>`;
        case 'date':
          return `<input type="date" class="${cls}" disabled>`;
        case 'time':
          return `<input type="time" class="${cls}" disabled>`;
        case 'multiple_choice':
          if (!field.options?.length) return '<p class="text-xs text-theme-muted italic">No options yet</p>';
          return field.options.map(o => `<label class="flex items-center gap-2 py-0.5"><input type="radio" disabled class="pointer-events-none"> <span class="text-xs text-theme-muted">${o.label}</span></label>`).join('');
        case 'checkbox':
          if (!field.options?.length) return '<p class="text-xs text-theme-muted italic">No options yet</p>';
          return field.options.map(o => `<label class="flex items-center gap-2 py-0.5"><input type="checkbox" disabled class="pointer-events-none"> <span class="text-xs text-theme-muted">${o.label}</span></label>`).join('');
        case 'dropdown':
          return `<select class="${cls}" disabled><option>-- Select --</option>${(field.options||[]).map(o=>`<option>${o.label}</option>`).join('')}</select>`;
        case 'file':
          return `<div class="border border-dashed border-theme rounded p-3 text-center"><i class="fas fa-upload text-theme-muted text-sm"></i><span class="text-xs text-theme-muted ml-2">File Upload</span></div>`;
        case 'rating': {
          const max = parseInt(field.settings?.max) || 5;
          return `<div>${Array.from({length:max}, () => '<i class="fas fa-star text-yellow-300 text-sm mr-0.5"></i>').join('')}</div>`;
        }
        case 'linear_scale': {
          const min = parseInt(field.settings?.min) || 1;
          const max2 = parseInt(field.settings?.max) || 5;
          let html = '<div class="flex gap-1 flex-wrap items-center">';
          if (field.settings?.min_label) html += `<span class="text-xs text-theme-muted">${field.settings.min_label}</span>`;
          for (let i = min; i <= max2; i++) html += `<span class="w-7 h-7 rounded border border-theme text-xs flex items-center justify-center text-theme-muted">${i}</span>`;
          if (field.settings?.max_label) html += `<span class="text-xs text-theme-muted">${field.settings.max_label}</span>`;
          return html + '</div>';
        }
        case 'section_header':
        case 'heading':
          return `<div class="h-px bg-theme-border mt-1"></div>`;
        case 'description':
          return `<p class="text-xs text-theme-muted italic">Description block</p>`;
        default:
          return `<p class="text-xs text-theme-muted italic">${field.type}</p>`;
      }
    }
  };
}
</script>
@endpush
