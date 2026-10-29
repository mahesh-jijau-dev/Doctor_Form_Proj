@if($response->assessment_result)
    @php
        $assessment = $response->assessment_result;
    @endphp
    @if((!empty($assessment['sections']) && ($assessment['score_is_meaningful'] ?? true)) || !empty($assessment['groups']))
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach(['sections' => 'Section totals', 'groups' => 'System / category totals'] as $collection => $heading)
                @if(!empty($assessment[$collection]) && ($collection !== 'sections' || ($assessment['score_is_meaningful'] ?? true)))
                    <div>
                        <h4 class="mb-2 text-sm font-semibold text-theme-text">{{ $heading }}</h4>
                        <dl class="space-y-1">
                            @foreach($assessment[$collection] as $entry)
                                <div class="flex justify-between gap-3 text-sm">
                                    <dt class="text-theme-muted">{{ $entry['label'] }}</dt>
                                    <dd class="font-medium text-theme-text">
                                        {{ $entry['score'] }}{{ $entry['max_score'] > 0 ? ' / ' . $entry['max_score'] : '' }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
    @if(!empty($assessment['section_categories']))
        <div class="mt-4">
            <h4 class="mb-2 text-sm font-semibold text-theme-text">Category counts by section</h4>
            <dl class="space-y-2">
                @foreach($assessment['section_categories'] as $section)
                    <div class="text-sm">
                        <dt class="font-medium text-theme-text">{{ $section['label'] }}</dt>
                        <dd class="flex flex-wrap gap-x-5 gap-y-1">
                            @foreach($section['categories'] as $category)
                                <span class="text-theme-muted">{{ $category['label'] }}: {{ $category['count'] }}</span>
                            @endforeach
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif
    @if(($assessment['percentage'] ?? null) !== null)
        <p class="mt-3 text-sm text-theme-muted">Percentage of available points: {{ $assessment['percentage'] }}%</p>
    @endif
    @if(array_key_exists('highest_groups', $assessment['result']))
        <p class="mt-3 text-sm">
            <strong>Highest-scoring system{{ count($assessment['result']['highest_groups']) === 1 ? '' : 's' }}:</strong>
            {{ $assessment['result']['highest_groups'] === [] ? 'No symptoms scored' : implode(', ', $assessment['result']['highest_groups']) }}
        </p>
    @endif

    @if(!empty($assessment['categories']))
        <div class="mt-4">
            <h4 class="mb-2 text-sm font-semibold text-theme-text">Category counts</h4>
            <dl class="flex flex-wrap gap-x-5 gap-y-1">
                @foreach($assessment['categories'] as $category => $count)
                    <div class="flex gap-2 text-sm">
                        <dt class="text-theme-muted">{{ ucfirst(str_replace('_', ' ', $category)) }}</dt>
                        <dd class="font-medium text-theme-text">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    @if(!empty($assessment['result']['profiles']))
        <div class="mt-4 overflow-x-auto">
            <h4 class="mb-2 text-sm font-semibold text-theme-text">Dhatu profile</h4>
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-theme text-theme-muted">
                    <th class="py-2 pr-3">Dhatu</th><th class="py-2 pr-3">Score</th>
                    <th class="py-2 pr-3">Sarata</th><th class="py-2">Current condition</th>
                </tr></thead>
                <tbody>
                    @foreach($assessment['result']['profiles'] as $profile)
                        <tr class="border-b border-theme">
                            <td class="py-2 pr-3">{{ $profile['label'] }}</td>
                            <td class="py-2 pr-3">{{ $profile['score'] }} / {{ $profile['max_score'] }}</td>
                            <td class="py-2 pr-3">{{ $profile['status'] }}</td>
                            <td class="py-2">{{ $profile['current_condition'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($assessment['result']['sarvasara'])
                <p class="mt-2 text-sm font-semibold">Overall profile: Sarvasara</p>
            @endif
            @if(!empty($assessment['result']['strengths']))
                <p class="mt-2 text-sm"><strong>Strengths:</strong> {{ implode(', ', $assessment['result']['strengths']) }}</p>
            @endif
            @if(!empty($assessment['result']['areas_for_care']))
                <p class="mt-1 text-sm"><strong>Areas for care:</strong> {{ implode(', ', $assessment['result']['areas_for_care']) }}</p>
            @endif
            @if(!empty($assessment['result']['active_conditions']))
                <p class="mt-1 text-sm"><strong>Current conditions needing attention:</strong> {{ implode(', ', $assessment['result']['active_conditions']) }}</p>
            @endif
        </div>
    @endif

    @if(!empty($assessment['result']['recommendation']))
        <p class="mt-3 text-sm text-theme-muted"><strong>Recommendation:</strong> {{ $assessment['result']['recommendation'] }}</p>
    @endif
    @if(!empty($assessment['result']['percentages']))
        <p class="mt-2 text-sm text-theme-muted">
            @foreach($assessment['result']['percentages'] as $category => $percentage)
                {{ ucfirst(str_replace('_', ' ', $category)) }}: {{ $percentage }}%{{ !$loop->last ? ' · ' : '' }}
            @endforeach
        </p>
    @endif
    @if(!empty($assessment['result']['summary']))
        <p class="mt-2 text-xs text-theme-muted">This assessment is for informational purposes and is not a medical diagnosis.</p>
    @endif
@endif
