@php
$score = $validation['score'];
$scoreClass = $score >= 90 ? 'high' : ($score >= 70 ? 'medium' : 'low');
$description = match (true) {
    $score >= 90 => 'Excellent ! Votre migration est pratiquement terminée.',
    $score >= 70 => 'Bon progrès ! Quelques ajustements sont encore nécessaires.',
    $score >= 50 => 'Votre migration est en cours. Plusieurs éléments nécessitent votre attention.',
    default => 'Attention ! De nombreux problèmes doivent être résolus avant de finaliser la migration.'
};
@endphp

<div class="score-section">
    <h2>🎯 Score de Migration</h2>
    <div class="score {{ $scoreClass }}">{{ $score }}/100</div>
    <div class="score-description">{{ $description }}</div>
    <div class="progress-bar">
        <div class="progress-fill {{ $scoreClass }}" style="width: {{ $score }}%"></div>
    </div>
</div>