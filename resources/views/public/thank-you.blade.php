<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-theme-bg text-theme-text min-h-screen flex items-center justify-center">
    <div class="card max-w-xl w-full p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-theme-success/15 flex items-center justify-center mb-4">
            <i class="fas fa-check text-theme-success text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2">Thank You!</h1>
        <p class="text-theme-muted mb-6">
            Your response for <strong>{{ $form->title }}</strong> has been submitted successfully.
        </p>
        @if(data_get($form->settings, 'show_score') && ($response->assessment_result || $response->quiz_max_score > 0))
            @php
                $assessmentResult = data_get($response->assessment_result, 'result') ?? $response->quiz_interpretation;
                $showAssessmentScore = ! $response->assessment_result || ($response->assessment_result['score_is_meaningful'] ?? true);
            @endphp
            <div class="mb-6 rounded-xl border border-theme bg-theme-surface-2 p-5">
                @if($showAssessmentScore && $response->quiz_max_score > 0)
                    <p class="text-xs font-semibold uppercase tracking-wider text-theme-muted">
                        {{ $assessmentResult ? 'Your Assessment Score' : 'Your Score' }}
                    </p>
                    <p class="mt-1 text-3xl font-bold text-theme-primary">
                        {{ rtrim(rtrim(number_format((float) $response->quiz_score, 2), '0'), '.') }}
                        <span class="text-lg text-theme-muted">/ {{ rtrim(rtrim(number_format((float) $response->quiz_max_score, 2), '0'), '.') }} points</span>
                    </p>
                @endif
                @if($assessmentResult)
                    <div class="mt-4 border-t border-theme pt-4 text-left">
                        <p class="text-sm font-semibold text-theme-text">
                            {{ $assessmentResult['title'] }}{{ !empty($assessmentResult['range']) ? ' ('.$assessmentResult['range'].')' : '' }}
                        </p>
                        <p class="mt-1 text-sm text-theme-muted">{{ $assessmentResult['summary'] }}</p>
                        <p class="mt-3 text-xs text-theme-muted">
                            This self-assessment is for reflection only and is not a medical diagnosis.
                            Consult a healthcare professional about persistent or sudden cognitive concerns.
                        </p>
                    </div>
                @elseif($response->quiz_max_score > 0)
                    <p class="mt-1 text-sm text-theme-muted">
                        {{ data_get($form->settings, 'option_scoring')
                            ? round(((float) $response->quiz_score / (float) $response->quiz_max_score) * 100) . '% of available points'
                            : round(((float) $response->quiz_score / (float) $response->quiz_max_score) * 100) . '% correct' }}
                    </p>
                @endif
                @include('components.assessment-summary', ['response' => $response])
            </div>
        @endif
        @if($form->confirmation_message)
            <p class="text-sm text-theme-text">{{ $form->confirmation_message }}</p>
        @endif
        <div class="mt-6">
            <a href="{{ route('forms.public.show', $form) }}" class="btn btn-primary">Submit another response</a>
        </div>
    </div>
</body>
</html>
