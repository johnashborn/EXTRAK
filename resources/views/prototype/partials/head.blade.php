<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }} · EXTRACK prototype</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('css/prototype.css') }}">
<script>try { if (localStorage.getItem('extrack.theme.v2') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}</script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>window.EXTRACK_ROLE = @json($role);</script>
<script>window.EXTRACK_SERVER = @json($prototypeServer);</script>
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script defer src="{{ asset('js/prototype/extrack.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
