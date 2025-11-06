<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '아글라이아 연구소')</title>
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @stack('styles')
</head>
<body>
    @include('layouts.header')

    @yield('content')

    @include('layouts.footer')
    @stack('scripts')
</body>
</html>