<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional CV</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.55;
            margin: 0;
            background: #ffffff;
        }

        .page {
            padding: 34px 38px 30px;
        }

        .header {
            background: #0f2747;
            color: #ffffff;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .name {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .role {
            margin-top: 6px;
            font-size: 12px;
            color: #dbeafe;
        }

        .section {
            margin-bottom: 14px;
        }

        .section-title {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0f2747;
            border-bottom: 1px solid #dbe4ef;
            padding-bottom: 4px;
        }

        .summary {
            margin: 0;
            white-space: pre-wrap;
        }

        ul {
            margin: 0;
            padding-left: 16px;
        }

        li {
            margin-bottom: 4px;
        }

        .muted {
            color: #6b7280;
        }

        .fallback {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1 class="name">{{ $resume['name'] ?? 'Professional Candidate' }}</h1>
            @if(!empty($resume['target_role']))
                <div class="role">{{ $resume['target_role'] }}</div>
            @endif
        </div>

        @if(!empty($resume['summary']))
            <section class="section">
                <h2 class="section-title">Professional Summary</h2>
                <p class="summary">{{ $resume['summary'] }}</p>
            </section>
        @endif

        <section class="section">
            <h2 class="section-title">Core Skills</h2>
            @if(!empty($resume['skills']) && is_array($resume['skills']))
                <ul>
                    @foreach($resume['skills'] as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No skills provided.</p>
            @endif
        </section>

        <section class="section">
            <h2 class="section-title">Experience</h2>
            @if(!empty($resume['experience']) && is_array($resume['experience']))
                <ul>
                    @foreach($resume['experience'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No experience provided.</p>
            @endif
        </section>

        <section class="section">
            <h2 class="section-title">Education</h2>
            @if(!empty($resume['education']) && is_array($resume['education']))
                <ul>
                    @foreach($resume['education'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No education provided.</p>
            @endif
        </section>

        @if(empty($resume))
            <section class="section">
                <h2 class="section-title">Resume</h2>
                <div class="fallback">{{ $text }}</div>
            </section>
        @endif
    </div>
</body>
</html>
