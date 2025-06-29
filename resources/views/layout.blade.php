<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bootstrap 5 Migrator')</title>
    <style>
        :root {
            --bs-primary: #6f42c1;
            --bs-secondary: #6c757d;
            --bs-success: #198754;
            --bs-info: #0dcaf0;
            --bs-warning: #ffc107;
            --bs-danger: #dc3545;
            --bs-light: #f8f9fa;
            --bs-dark: #212529;
        }

        * { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bs-light);
            color: var(--bs-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--bs-primary), #e83e8c);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .header .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .header .meta div {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
        }

        .content {
            padding: 2rem;
        }

        .score-section {
            text-align: center;
            padding: 2rem;
            margin: 2rem 0;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
        }

        .score {
            font-size: 4rem;
            font-weight: bold;
            margin: 1rem 0;
        }

        .score.high { color: var(--bs-success); }
        .score.medium { color: var(--bs-warning); }
        .score.low { color: var(--bs-danger); }

        .score-description {
            font-size: 1.1rem;
            color: var(--bs-secondary);
        }

        .section {
            margin: 3rem 0;
            padding: 2rem;
            border-left: 4px solid var(--bs-primary);
            background: var(--bs-light);
            border-radius: 0 8px 8px 0;
        }

        .section h2 {
            margin-top: 0;
            color: var(--bs-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .grid {
            display: grid;
            gap: 1rem;
        }

        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--bs-primary);
        }

        .card.danger { border-left-color: var(--bs-danger); }
        .card.warning { border-left-color: var(--bs-warning); }
        .card.success { border-left-color: var(--bs-success); }
        .card.info { border-left-color: var(--bs-info); }

        .issue {
            background: white;
            margin: 1rem 0;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--bs-danger);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .issue.warning { border-left-color: var(--bs-warning); }
        .issue.success { border-left-color: var(--bs-success); }
        .issue.info { border-left-color: var(--bs-info); }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: var(--bs-primary);
            color: white;
            font-weight: 600;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-high { background: var(--bs-danger); color: white; }
        .badge-medium { background: var(--bs-warning); color: var(--bs-dark); }
        .badge-low { background: var(--bs-success); color: white; }
        .badge-info { background: var(--bs-info); color: var(--bs-dark); }
        .badge-success { background: var(--bs-success); color: white; }
        .badge-warning { background: var(--bs-warning); color: var(--bs-dark); }
        .badge-danger { background: var(--bs-danger); color: white; }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--bs-primary);
            transition: width 0.3s ease;
        }

        .progress-fill.success { background: var(--bs-success); }
        .progress-fill.warning { background: var(--bs-warning); }
        .progress-fill.danger { background: var(--bs-danger); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--bs-primary);
        }

        .stat-label {
            color: var(--bs-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .checklist {
            list-style: none;
            padding: 0;
        }

        .checklist li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checklist li:last-child {
            border-bottom: none;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--bs-primary);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .checkbox.checked {
            background: var(--bs-success);
            border-color: var(--bs-success);
            position: relative;
        }

        .checkbox.checked::after {
            content: '✓';
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .highlight {
            background: #fff3cd;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                border-radius: 8px;
            }

            .header {
                padding: 2rem 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .content {
                padding: 1rem;
            }

            .section {
                padding: 1rem;
            }

            .header .meta {
                grid-template-columns: 1fr;
            }
        }

        .print-only { display: none; }

        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }

            body { background: white; }
            .container { box-shadow: none; margin: 0; }
            .header { background: var(--bs-primary) !important; }
            .section, .card, .issue { break-inside: avoid; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>@yield('header-title')</h1>
            @yield('header-content')
        </div>

        <div class="content">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>