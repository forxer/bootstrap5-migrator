@extends('bootstrap5-migrator::layout')

@section('title', 'Rapport de Comparaison - Migration Bootstrap 5')

@section('header-title', '📊 Rapport de Comparaison Migration')

@section('header-content')
<p>Analyse avant/après migration Bootstrap 5</p>
<p>Généré le {{ $comparison['meta']['generated_at'] }}</p>
@endsection

@section('content')
<div class="score-comparison">
    <h2>Amélioration du Score</h2>
    <div class="before-after">
        <div>
            <div class="score-big" style="color: #dc3545;">{{ $comparison['score_improvement']['before'] }}</div>
            <div>Avant Migration</div>
        </div>
        <div>
            <div class="score-big" style="color: #28a745;">{{ $comparison['score_improvement']['after'] }}</div>
            <div>Après Migration</div>
        </div>
    </div>
    <div class="improvement">Amélioration: +{{ $comparison['score_improvement']['improvement'] }} points</div>
</div>

<h2>Problèmes Résolus</h2>
<table class="metrics-table">
    <thead>
        <tr>
            <th>Type de Problème</th>
            <th>Avant</th>
            <th>Après</th>
            <th>Résolus</th>
        </tr>
    </thead>
    <tbody>
        @foreach($comparison['issues_resolved'] as $type => $data)
            <tr>
                <td>
                    @switch($type)
                        @case('deprecated_classes') Classes obsolètes @break
                        @case('cdn_links') Liens CDN @break
                        @case('special_cases') Cas spéciaux @break
                        @default {{ $type }}
                    @endswitch
                </td>
                <td>{{ $data['before'] }}</td>
                <td class="remaining">{{ $data['after'] }}</td>
                <td class="resolved">{{ $data['resolved'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

@push('styles')
<style>
.score-comparison { text-align: center; padding: 2rem; background: linear-gradient(45deg, #f8f9fa, #e9ecef); border-radius: 12px; }
.before-after { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center; }
.score-big { font-size: 3rem; font-weight: bold; }
.improvement { font-size: 1.5rem; color: #28a745; }
.metrics-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.metrics-table th, .metrics-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; }
.metrics-table th { background: #6f42c1; color: white; }
.resolved { color: #28a745; font-weight: bold; }
.remaining { color: #dc3545; font-weight: bold; }
</style>
@endpush