<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Choose a dashboard · EXTRACK prototype</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/prototype.css') }}">
<script>try { if (localStorage.getItem('extrack.theme.v2') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}</script>
</head>
<body class="chooser-page">
<main class="chooser">
  <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.png') }}" alt="" width="34" height="34"><span><b>EXTRACK</b><small>Balilihan Campus</small></span></a>
  <h1>Which dashboard do you want to open?</h1>
  <p class="sub">This is a prototype for the research proposal. All three dashboards share the same sample data, so a request submitted or approved on one shows up on the others.</p>
  <ul class="choices">
    <li><a href="{{ route('prototype.extension-director') }}">
      <b>Extension Director</b><span>Make extension requests, follow them through approval, and submit accomplishment reports.</span></a></li>
    <li><a href="{{ route('prototype.campus-director') }}">
      <b>Campus Director</b><span>Approve, return or decline the campus's requests before they go to the University President.</span></a></li>
    <li><a href="{{ route('prototype.president') }}">
      <b>University President</b><span>Give final approval and see every campus's extension work and budget.</span></a></li>
  </ul>
  <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="signout">
    @csrf
    <button type="submit"><svg viewBox="0 0 24 24"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11"/></svg>Sign out ({{ auth()->user()->name }})</button>
  </form>
</main>
</body>
</html>
