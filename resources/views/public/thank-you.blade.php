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
        @if(data_get($form->settings, 'show_score') && $response->quiz_max_score > 0)
            <div class="mb-6 rounded-xl border border-theme bg-theme-surface-2 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-theme-muted">Your Score</p>
                <p class="mt-1 text-3xl font-bold text-theme-primary">
                    {{ rtrim(rtrim(number_format((float) $response->quiz_score, 2), '0'), '.') }}
                    <span class="text-lg text-theme-muted">/ {{ rtrim(rtrim(number_format((float) $response->quiz_max_score, 2), '0'), '.') }} points</span>
                </p>
                <p class="mt-1 text-sm text-theme-muted">
                    {{ round(((float) $response->quiz_score / (float) $response->quiz_max_score) * 100) }}% correct
                </p>
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
