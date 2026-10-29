<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use InvalidArgumentException;

class AssessmentScoringService
{
    /**
     * Calculate an assessment configured in the form's settings.
     *
     * Each scored field may define assessment_section and assessment_group in
     * its settings. Category assessments additionally use category_map.
     */
    public function calculate(Form $form, array $answers): ?array
    {
        $configuration = data_get($form->settings, 'assessment');
        if (! is_array($configuration) || empty($configuration['type'])) {
            return null;
        }

        $fields = $form->fields()->with('options')->get()
            ->filter(fn (FormField $field) => ! $field->isLayoutType());
        $scores = [];
        $maximums = [];
        $sectionScores = [];
        $sectionMaximums = [];
        $groupScores = [];
        $groupMaximums = [];
        $currentConditions = [];
        $categoryCounts = array_fill_keys(array_keys($configuration['category_map'] ?? []), 0);
        $sectionCategoryCounts = [];

        foreach ($fields as $field) {
            $key = 'field_'.$field->id;
            $selected = $answers[$key] ?? null;
            $fieldSettings = $field->settings ?? [];
            $fieldScore = 0.0;
            $fieldMaximum = 0.0;

            if ($field->hasOptions()) {
                [$fieldScore, $fieldMaximum, $selectedValues] = $this->scoreOptions(
                    $field,
                    $selected,
                    (bool) ($fieldSettings['assessment_scored'] ?? false)
                );

                foreach ($categoryCounts as $category => $count) {
                    $categoryValues = array_map('strval', $configuration['category_map'][$category] ?? []);
                    foreach ($selectedValues as $selectedValue) {
                        if (in_array($selectedValue, $categoryValues, true)) {
                            $categoryCounts[$category]++;
                            $sectionKey = (string) ($fieldSettings['assessment_section'] ?? '');
                            if ($sectionKey  !== '') {
                                $sectionCategoryCounts[$sectionKey][$category] = ($sectionCategoryCounts[$sectionKey][$category] ?? 0) + 1;
                            }
                        }
                    }
                }

                if (($fieldSettings['assessment_current_condition'] ?? false) && isset($fieldSettings['assessment_group'])) {
                    $currentConditions[$fieldSettings['assessment_group']] = $selectedValues[0] ?? null;
                }
            } elseif (($fieldSettings['assessment_scored'] ?? false) && is_numeric($selected)) {
                $fieldScore = (float) $selected;
                $fieldMaximum = (float) data_get($fieldSettings, 'assessment_max', $selected);
                if ($fieldScore < 0 || $fieldScore > $fieldMaximum) {
                    throw new InvalidArgumentException("Assessment score is out of range for field {$field->id}.");
                }
            }

            $scores[$field->id] = $fieldScore;
            $maximums[$field->id] = $fieldMaximum;
            $this->addToGroup(
                $sectionScores,
                $sectionMaximums,
                (string) ($fieldSettings['assessment_section'] ?? ''),
                $fieldScore,
                $fieldMaximum
            );
            $this->addToGroup(
                $groupScores,
                $groupMaximums,
                (string) ($fieldSettings['assessment_group'] ?? ''),
                $fieldScore,
                $fieldMaximum
            );
        }

        $total = array_sum($scores);
        $maximum = array_sum($maximums);
        $sections = $this->formatGroups($sectionScores, $sectionMaximums, $configuration['sections'] ?? []);
        $groups = $this->formatGroups($groupScores, $groupMaximums, $configuration['groups'] ?? []);
        $sectionCategories = [];
        foreach ($sectionCategoryCounts as $sectionKey => $counts) {
            $sectionCategories[$sectionKey] = [
                'label' => $configuration['sections'][$sectionKey] ?? $sectionKey,
                'categories' => [],
            ];
            foreach ($categoryCounts as $category => $count) {
                $sectionCategories[$sectionKey]['categories'][$category] = [
                    'label' => $configuration['category_titles'][$category] ?? ucfirst($category),
                    'count' => $counts[$category] ?? 0,
                ];
            }
        }
        $scoreIsMeaningful = in_array($configuration['type'], ['score_bands', 'dhatu_sarata'], true);
        $result = match ($configuration['type']) {
            'score_bands' => $this->scoreBandResult((float) $total, $configuration['bands'] ?? []),
            'guna_majority' => $this->majorityResult($categoryCounts, $configuration),
            'prakriti_vpk' => $this->vpkResult($categoryCounts, $configuration),
            'prakriti_ksa' => $this->ksaResult($categoryCounts, $configuration),
            'dhatu_sarata' => $this->dhatuResult($groups, $currentConditions, $configuration),
            default => throw new InvalidArgumentException('Unsupported assessment type: '.$configuration['type']),
        };
        if (($configuration['highlight_highest_group'] ?? false) && $groups !== []) {
            $highestScore = max(array_column($groups, 'score'));
            $result['highest_groups'] = $highestScore > 0
                ? array_values(array_map(
                    fn (array $group) => $group['label'],
                    array_filter($groups, fn (array $group) => $group['score'] === $highestScore)
                ))
                : [];
        }

        return [
            'type' => $configuration['type'],
            'total_score' => $this->cleanNumber((float) $total),
            'max_score' => $this->cleanNumber((float) ($configuration['max_score'] ?? $maximum)),
            'percentage' => $scoreIsMeaningful && $maximum > 0 ? round($total / $maximum * 100, 1) : null,
            'score_is_meaningful' => $scoreIsMeaningful,
            'sections' => $sections,
            'section_categories' => $sectionCategories,
            'groups' => $groups,
            'categories' => $categoryCounts,
            'result' => $result,
            'field_scores' => $scores,
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: list<string>}
     */
    private function scoreOptions(FormField $field, mixed $selected, bool $assessmentScored): array
    {
        $selectedValues = is_array($selected)
            ? array_values(array_unique(array_map('strval', $selected)))
            : ($selected === null || $selected === '' ? [] : [(string) $selected]);
        $optionScores = [];
        $allScores = [];

        foreach ($field->options as $option) {
            $value = (string) ($option->value !== null && $option->value !== '' ? $option->value : $option->label);
            $score = $option->score;
            if ($score === null && $assessmentScored && is_numeric($value)) {
                $score = (float) $value;
            }
            if ($score !== null) {
                if (! is_numeric($score) || (float) $score < 0) {
                    throw new InvalidArgumentException("Assessment option scores must be non-negative for field {$field->id}.");
                }
                $optionScores[$value] = (float) $score;
                $allScores[] = (float) $score;
            }
        }

        $score = 0.0;
        foreach ($selectedValues as $value) {
            if ($optionScores !== [] && ! array_key_exists($value, $optionScores)) {
                throw new InvalidArgumentException("Assessment answer is invalid for field {$field->id}.");
            }
            $score += $optionScores[$value] ?? 0.0;
        }

        $maximum = 0.0;
        if ($allScores !== []) {
            $maximum = $field->type === 'checkbox' ? array_sum($allScores) : max($allScores);
        }

        return [$score, $maximum, $selectedValues];
    }

    private function addToGroup(array &$scores, array &$maximums, string $key, float $score, float $maximum): void
    {
        if ($key === '') {
            return;
        }

        $scores[$key] = ($scores[$key] ?? 0.0) + $score;
        $maximums[$key] = ($maximums[$key] ?? 0.0) + $maximum;
    }

    private function formatGroups(array $scores, array $maximums, array $labels): array
    {
        $groups = [];
        foreach ($scores as $key => $score) {
            $groups[$key] = [
                'label' => $labels[$key] ?? $key,
                'score' => $this->cleanNumber($score),
                'max_score' => $this->cleanNumber($maximums[$key] ?? 0.0),
            ];
        }

        return $groups;
    }

    private function scoreBandResult(float $score, array $bands): array
    {
        foreach ($bands as $band) {
            $minimum = (float) ($band['min'] ?? 0);
            $maximum = isset($band['max']) ? (float) $band['max'] : INF;
            if ($score >= $minimum && $score <= $maximum) {
                return [
                    'title' => $band['title'],
                    'range' => $band['range'] ?? $this->formatRange($minimum, $maximum),
                    'summary' => $band['summary'] ?? '',
                    'recommendation' => $band['recommendation'] ?? null,
                ];
            }
        }

        throw new InvalidArgumentException("Assessment score {$score} does not match any configured score band.");
    }

    private function majorityResult(array $counts, array $configuration): array
    {
        $topScore = max($counts ?: [0]);
        $topCategories = array_keys(array_filter($counts, fn (int $count) => $count === $topScore));

        if (count($topCategories) === count($counts)) {
            return [
                'title' => $configuration['balanced_title'] ?? 'Balanced mixed profile',
                'range' => 'Tie',
                'summary' => $configuration['balanced_summary'] ?? 'All categories were selected equally.',
                'categories' => $topCategories,
            ];
        }

        if (count($topCategories) > 1) {
            return [
                'title' => $configuration['mixed_title'] ?? 'Mixed profile',
                'range' => 'Tie',
                'summary' => $configuration['mixed_summary'] ?? 'More than one category has the highest count.',
                'categories' => $topCategories,
            ];
        }

        $category = $topCategories[0];

        return [
            'title' => $configuration['category_titles'][$category] ?? ucfirst($category),
            'range' => $topScore.' selections',
            'summary' => $configuration['category_summaries'][$category] ?? '',
            'categories' => [$category],
        ];
    }

    private function vpkResult(array $counts, array $configuration): array
    {
        $ordered = $counts;
        arsort($ordered);
        $categories = array_keys($ordered);
        $values = array_values($ordered);
        $total = array_sum($counts);
        $percentages = [];
        foreach ($counts as $category => $count) {
            $percentages[$category] = $total > 0 ? round($count / $total * 100, 1) : 0.0;
        }

        if ($total === 0) {
            return ['title' => 'No result', 'range' => null, 'summary' => 'No dosha responses were recorded.', 'percentages' => $percentages];
        }

        if (($values[0] - $values[2]) / $total * 100 <= (float) ($configuration['balanced_spread_percent'] ?? 10)) {
            return [
                'title' => $configuration['balanced_title'] ?? 'Sama Prakriti (balanced)',
                'range' => null,
                'summary' => $configuration['balanced_summary'] ?? 'Dosha selections are closely balanced.',
                'categories' => $categories,
                'percentages' => $percentages,
            ];
        }

        $winners = array_keys(array_filter($ordered, fn (int $count) => $count === $values[0]));
        if (count($winners) > 1 || (int) ($configuration['dual_tolerance'] ?? 2) >= $values[0] - $values[1]) {
            $winners = array_slice($categories, 0, 2);

            return [
                'title' => implode('-', array_map(fn (string $category) => $configuration['category_titles'][$category] ?? ucfirst($category), $winners)).' (dual constitution)',
                'range' => null,
                'summary' => $configuration['dual_summary'] ?? 'Two doshas have similar leading counts.',
                'categories' => $winners,
                'percentages' => $percentages,
            ];
        }

        $primary = $categories[0];

        return [
            'title' => ($configuration['category_titles'][$primary] ?? ucfirst($primary)).' dominant',
            'range' => null,
            'summary' => $configuration['single_summary'] ?? 'One dosha has the highest count.',
            'categories' => [$primary],
            'percentages' => $percentages,
        ];
    }

    private function ksaResult(array $counts, array $configuration): array
    {
        $ordered = $counts;
        arsort($ordered);
        $categories = array_keys($ordered);
        $values = array_values($ordered);
        if ($values === [] || $values[0] === 0) {
            return ['title' => 'No result', 'range' => null, 'summary' => 'No constitution responses were recorded.'];
        }

        if ((int) ($configuration['balanced_tolerance'] ?? 1) >= $values[0] - $values[2]) {
            return [
                'title' => $configuration['balanced_title'] ?? 'Balanced (Tridoshic)',
                'range' => null,
                'summary' => $configuration['balanced_summary'] ?? 'All three categories have similar counts.',
                'categories' => $categories,
            ];
        }

        if ((int) ($configuration['dual_tolerance'] ?? 2) >= $values[0] - $values[1]) {
            $winners = array_slice($categories, 0, 2);

            return [
                'title' => implode('-', array_map(fn (string $category) => $configuration['category_titles'][$category] ?? ucfirst($category), $winners)).' (mixed)',
                'range' => null,
                'summary' => $configuration['dual_summary'] ?? 'Two categories have similar leading counts.',
                'categories' => $winners,
            ];
        }

        $primary = $categories[0];

        return [
            'title' => ($configuration['category_titles'][$primary] ?? ucfirst($primary)).' dominant',
            'range' => null,
            'summary' => $configuration['single_summary'] ?? 'One category has the highest count.',
            'categories' => [$primary],
        ];
    }

    private function dhatuResult(array $groups, array $currentConditions, array $configuration): array
    {
        $profiles = [];
        foreach ($groups as $key => $group) {
            $score = (float) $group['score'];
            $profiles[$key] = [
                'label' => $group['label'],
                'score' => $group['score'],
                'max_score' => $group['max_score'],
                'status' => match (true) {
                    $score <= 2 => 'Avara Sara',
                    $score <= 4 => 'Madhya Sara',
                    default => 'Pravara Sara',
                },
                'current_condition' => $configuration['condition_map'][$currentConditions[$key] ?? ''] ?? null,
            ];
        }

        return [
            'title' => 'Dhatu Sarata profile',
            'range' => null,
            'summary' => $configuration['summary'] ?? 'Each tissue score is reported independently; this is not a diagnosis.',
            'profiles' => $profiles,
            'sarvasara' => count(array_filter($profiles, fn (array $profile) => $profile['score'] >= 5)) >= 6,
            'strengths' => array_keys(array_filter($profiles, fn (array $profile) => $profile['score'] >= 5)),
            'areas_for_care' => array_keys(array_filter($profiles, fn (array $profile) => $profile['score'] <= 2)),
            'active_conditions' => array_keys(array_filter($profiles, fn (array $profile) => in_array($profile['current_condition'], ['Vriddhi', 'Kshaya'], true))),
        ];
    }

    private function formatRange(float $minimum, float $maximum): string
    {
        return is_infinite($maximum)
            ? $this->cleanNumber($minimum).'+'
            : $this->cleanNumber($minimum).'–'.$this->cleanNumber($maximum);
    }

    private function cleanNumber(float $number): int|float
    {
        return floor($number) === $number ? (int) $number : $number;
    }
}
