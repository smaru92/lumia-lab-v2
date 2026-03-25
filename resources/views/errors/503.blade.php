<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>점검 중 - 아글라이아 연구소</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/Common/favicon.png') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #1a1a2e;
            color: #eee;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .maintenance-container {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .maintenance-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #fff;
        }

        .subtitle {
            font-size: 18px;
            color: #aaa;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .info-box {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            font-size: 16px;
            color: #ffc107;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .info-box p {
            font-size: 14px;
            color: #ccc;
            line-height: 1.8;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .progress-bar-inner {
            width: 30%;
            height: 100%;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 2px;
            animation: loading 2s ease-in-out infinite;
        }

        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }

        .footer-text {
            font-size: 13px;
            color: #666;
        }

        .footer-text a {
            color: #4facfe;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        /* Mobile */
        @media (max-width: 599px) {
            .maintenance-icon {
                font-size: 60px;
            }

            h1 {
                font-size: 24px;
            }

            .subtitle {
                font-size: 16px;
            }

            .info-box {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>

        <h1>서비스 점검 중입니다</h1>

        <p class="subtitle">
            더 나은 서비스를 위해 시스템 점검을 진행하고 있습니다.<br>
            잠시 후 다시 방문해 주세요.
        </p>

        <div class="progress-bar">
            <div class="progress-bar-inner"></div>
        </div>

        <div class="info-box">
            <h3>📢 점검 안내</h3>
            <p>
                점검 시간: 추후 공지<br>
                점검 내용: 시스템 업데이트 및 최적화
            </p>
        </div>

        <p class="footer-text">
            문의사항은 <a href="mailto:support@example.com">support@example.com</a>으로 연락해 주세요.
        </p>
    </div>
</body>
</html>