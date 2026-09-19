@props(['action' => ''])
<form method="GET" action="{{ $action }}" class="filter-form flex flex-wrap gap-2 items-center">
    {{ $slot }}
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-search"></i> Search
    </button>
    @if(request()->hasAny(['search', 'status', 'form_id', 'doctor_id', 'from', 'to']))
        <a href="{{ $action }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Clear
        </a>
    @endif
</form>
