@extends('layouts.app')

@section('title', '데이터 없음 | 아글라이아 연구소')

@push('styles')
    <style>
        .not-found-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px 20px;
            text-align: center;
        }

        .not-found-icon {
            font-size: 72px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .not-found-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .not-found-message {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .not-found-character {
            background-color: #ecf0f1;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 30px;
            font-family: monospace;
            font-size: 16px;
            color: #34495e;
        }

        .not-found-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        /* 반응형 디자인 */
        @media (max-width: 599px) {
            .not-found-container {
                padding: 30px 15px;
            }

            .not-found-icon {
                font-size: 56px;
            }

            .not-found-title {
                font-size: 24px;
            }

            .not-found-message {
                font-size: 16px;
            }

            .not-found-actions {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }

        @media (min-width: 600px) and (max-width: 1024px) {
            .not-found-container {
                padding: 35px 20px;
            }

            .not-found-icon {
                font-size: 64px;
            }

            .not-found-title {
                font-size: 28px;
            }
        }
    </style>
@endpush

@section('content')
<div class="not-found-container">
    <div class="not-found-icon">⚠️</div>

    <h1 class="not-found-title">데이터를 찾을 수 없습니다</h1>

    <p class="not-found-message">
        {{ $message ?? '요청하신 페이지의 데이터를 찾을 수 없습니다.' }}
    </p>

    @isset($characterName)
    <div class="not-found-character">
        캐릭터: {{ $characterName }}
    </div>
    @endisset

    <div class="not-found-actions">
        <a href="/character?min_tier={{ $defaultTier ?? 'Diamond' }}&version={{ $defaultVersion ?? '1.0.0' }}" class="btn-action btn-primary">
            캐릭터 목록으로 돌아가기
        </a>
        <a href="/" class="btn-action btn-secondary">
            메인 페이지로 이동
        </a>
    </div>
</div>
@endsection