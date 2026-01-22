<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumia Lab Admin</title>
    @viteReactRefresh
    @vite(['resources/css/admin.css', 'resources/js/admin/main.tsx'])
</head>
<body>
    <div id="root"></div>
</body>
</html>
