<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'EXTRACK')</title>
    {{-- Temporary bare layout; Task 7 replaces it with the BISU shell. --}}
</head>
<body>
<main>
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    @yield('content')
</main>
</body>
</html>
