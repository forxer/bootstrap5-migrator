@extends('bootstrap5-migrator::layout')

@section('title', 'Rapport de Migration Bootstrap 5 - ' . $data['meta']['app_name'])

@section('header-title', '📊 Rapport de Migration Bootstrap 5')

@section('header-content')
<div class="meta">
    <div>
        <strong>Application</strong><br>
        {{ $data['meta']['app_name'] }}
    </div>
    <div>
        <strong>Généré le</strong><br>
        {{ $data['meta']['generated_at'] }}
    </div>
    <div>
        <strong>Laravel</strong><br>
        {{ $data['meta']['laravel_version'] }}
    </div>
    <div>
        <strong>PHP</strong><br>
        {{ $data['meta']['php_version'] }}
    </div>
</div>
@endsection

@section('content')
    @include('bootstrap5-migrator::components.score-section', ['validation' => $data['validation']])
    @include('bootstrap5-migrator::components.stats-section', ['analysis' => $data['analysis']])
    @include('bootstrap5-migrator::components.summary-section', ['analysis' => $data['analysis']])
    @include('bootstrap5-migrator::components.issues-section', ['analysis' => $data['analysis']])
    @include('bootstrap5-migrator::components.validation-section', ['validation' => $data['validation']])
    @include('bootstrap5-migrator::components.checklist-section', ['checklist' => $data['migration_checklist']])
    @include('bootstrap5-migrator::components.recommendations-section', ['recommendations' => $data['recommendations']])
@endsection
