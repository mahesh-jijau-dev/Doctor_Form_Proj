@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card title="Total Doctors" :value="$stats['total_doctors']" icon="fa-user-md"
                     :subtitle="$stats['active_doctors'] . ' active'" />
        <x-stat-card title="Total Forms" :value="$stats['total_forms']" icon="fa-file-alt"
                     :subtitle="$stats['published_forms'] . ' published'" />
        <x-stat-card title="Total Responses" :value="number_format($stats['total_responses'])" icon="fa-inbox"
                     :subtitle="$stats['this_month_responses'] . ' this month'" />
        <x-stat-card title="Today's Responses" :value="$stats['today_responses']" icon="fa-calendar-check" />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Chart -->
        <div class="xl:col-span-2 card p-5">
            <h3 class="text-sm font-semibold text-theme-text mb-4">Responses — Last 7 Days</h3>
            <div id="responseChart" style="height:200px"></div>
        </div>

        <!-- Top forms -->
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-theme-text mb-4">Top Forms by Responses</h3>
            @forelse($topForms as $form)
            <div class="flex items-center gap-3 py-2.5 border-b border-theme last:border-0">
                <div class="w-8 h-8 rounded-lg bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-alt text-theme-muted text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-theme-text truncate">{{ $form->title }}</p>
                    <p class="text-xs text-theme-muted">{{ $form->responses_count }} responses</p>
                </div>
                <x-badge :type="$form->status === 'published' ? 'success' : 'muted'">
                    {{ $form->status }}
                </x-badge>
            </div>
            @empty
                <p class="text-sm text-theme-muted text-center py-4">No forms yet</p>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Recent Responses -->
        <div class="card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-theme">
                <h3 class="text-sm font-semibold text-theme-text">Recent Responses</h3>
                <a href="{{ route('admin.responses.index') }}" class="text-xs text-theme-primary hover:underline">View all</a>
            </div>
            <div class="divide-y divide-theme">
                @forelse($recentResponses as $response)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-full bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-theme-muted text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-theme-text truncate">{{ $response->submitted_by_name ?? 'Anonymous' }}</p>
                        <p class="text-xs text-theme-muted truncate">{{ $response->form?->title }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-theme-muted">{{ $response->submitted_at?->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-theme-muted">No responses yet</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="px-5 py-4 border-b border-theme">
                <h3 class="text-sm font-semibold text-theme-text">Recent Activity</h3>
            </div>
            <div class="divide-y divide-theme">
                @forelse($recentActivity as $log)
                <div class="flex items-start gap-3 px-5 py-3">
                    <div class="w-7 h-7 rounded-full bg-theme-surface-2 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-clock text-theme-muted text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-theme-text">{{ $log->description ?? $log->action }}</p>
                        <p class="text-xs text-theme-muted">{{ $log->user?->name ?? 'System' }} &bull; {{ $log->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-theme-muted">No activity yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = @json($chartLabels);
    const values = @json($chartValues);
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const container = document.getElementById('responseChart');
    if (!container) return;

    const canvas = document.createElement('canvas');
    container.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    canvas.width = container.offsetWidth;
    canvas.height = 200;

    const max = Math.max(...values, 1);
    const barWidth = (canvas.width - 60) / labels.length;
    const chartHeight = 160;
    const chartTop = 10;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    labels.forEach((label, i) => {
        const x = 30 + i * barWidth + barWidth * 0.15;
        const barW = barWidth * 0.7;
        const barH = (values[i] / max) * chartHeight;
        const y = chartTop + chartHeight - barH;

        // Bar
        ctx.fillStyle = 'rgba(99,102,241,0.85)';
        ctx.beginPath();
        if (ctx.roundRect) {
            ctx.roundRect(x, y, barW, barH, [4, 4, 0, 0]);
        } else {
            ctx.rect(x, y, barW, barH);
        }
        ctx.fill();

        // Label
        ctx.fillStyle = textColor;
        ctx.font = '10px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(label, x + barW / 2, canvas.height - 5);

        // Value
        if (values[i] > 0) {
            ctx.fillStyle = textColor;
            ctx.fillText(values[i], x + barW / 2, y - 4);
        }
    });
});
</script>
@endpush
