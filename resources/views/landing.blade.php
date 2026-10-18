<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EXTRAK</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=Montserrat:wght@500;600;700;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
<header class="hero">
  <div class="ghost" aria-hidden="true">EXTRAK</div>
  <div class="wrap">
    <nav class="nav" aria-label="Main">
      <!-- To use your official logo, replace the <svg> below with: <img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40"> -->
      <a class="brand" href="#top" id="top"><img src="{{ asset('images/logo.png') }}" alt="EXTRAK" width="40" height="40"><b>EXTRAK</b></a>
      <div class="links"><a href="#features">What it does</a><a href="#how">How it works</a><a href="#campuses">Campuses</a></div>
      <a class="btn gold" href="{{ url('/admin/login') }}">Sign in</a>
    </nav>
    <div class="hero-grid">
      <div>
        <div class="eyebrow">Bohol Island State University · Extension Services</div>
        <h1>One view of every <em>barangay</em> we serve.</h1>
        <p class="lead">EXTRAK helps the extension office record, review and report community work across every campus. It also warns a coordinator before two campuses plan the same thing for the same community.</p>
        <div class="cta"><a class="btn gold" href="{{ url('/admin/login') }}">Sign in to EXTRAK <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a><a class="btn line" href="#how">See how it works</a></div>
        <p class="note">For campus coordinators, campus directors and the VP for Extension. Accounts are issued by the extension office.</p>
      </div>
      <div class="preview" aria-label="Preview of an overlap warning">
        <div class="pv-bar"><i></i><i></i><i></i><span>Activities by barangay</span></div>
        <div class="pv-map"><svg viewBox="0 0 100 62" role="img" aria-label="Map with activity markers">
          <path d="M6 30C4 14 18 6 34 6c14-3 26 4 32 14 8 6 10 12 20 14 8 2 11 12 8 20-4 8-16 10-24 6-9-5-16-5-22-8-4 8-6 14-10 18C24 64 12 58 10 48 12 40 8 38 6 30Z" style="fill:var(--surface);stroke:var(--line);stroke-width:.5"/>
          <g style="fill:var(--s-ong)"><circle cx="30" cy="24" r="3.4"/><circle cx="52" cy="14" r="2.6"/><circle cx="46" cy="36" r="2.6"/></g>
          <g style="fill:var(--s-comp)"><circle cx="66" cy="36" r="2.6"/><circle cx="82" cy="40" r="2.6"/><circle cx="20" cy="46" r="2.6"/></g>
          <g style="fill:var(--s-prop)"><circle cx="76" cy="50" r="2.6"/><circle cx="58" cy="44" r="2.6"/></g>
          <circle cx="30" cy="24" r="6" style="fill:none;stroke:var(--flag);stroke-width:.8;stroke-dasharray:1.6 1.2"/>
        </svg></div>
        <div class="pv-body">
          <div class="flagrow"><div><span class="chip-flag">Possible overlap</span><b style="margin-top:8px">Sampoloc, Calape</b><small>Same barangay, overlapping dates</small></div><div class="pc">87%<small>similar</small></div></div>
          <div class="meter"><i></i></div>
          <div class="vs"><div><span>Calape Campus</span>Seaweed farming training for coastal women</div><div><span>Main Campus</span>Aquaculture skills seminar for fisherfolk families</div></div>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="s" id="features"><div class="wrap">
  <div class="s-head"><div class="eyebrow">What it does</div><h2>Know what is already happening before you plan.</h2><p>Extension records used to live in separate folders on separate campuses. EXTRAK puts them in one place that every authorized office can search.</p></div>
  <div class="feat">
    <div class="card big"><span class="tag">Smart check</span><h3>Catches overlaps even when the wording differs.</h3><p>When a coordinator describes a new activity, EXTRAK compares its meaning, place and dates with existing work. A seaweed farming training and an aquaculture seminar in the same barangay get flagged, even though they share few exact words.</p>
      <div class="demo"><div class="line"><b>Wording</b><span>91%</span></div><div class="line"><b>Place</b><span>Same barangay</span></div><div class="line"><b>Timing</b><span>Overlaps by 6 weeks</span></div><div>Shared theme: <mark>seaweed</mark>, <mark>fisherfolk</mark>, <mark>income</mark></div></div></div>
    <div class="card"><h3>Every campus in one list</h3><p>Search and filter by campus, barangay, date, status and category.</p></div>
    <div class="card"><h3>Approvals that leave a trail</h3><p>Directors approve or return new activities with a remark. Coordinators see why.</p></div>
    <div class="card"><h3>Reports ready for CHED</h3><p>Consolidated accomplishments by campus, quarter or category, exported to PDF or Excel.</p></div>
  </div>
</div></section>

<div class="steps-band"><section class="s" id="how"><div class="wrap">
  <div class="s-head"><div class="eyebrow">How it works</div><h2>From encoding to the annual report.</h2></div>
  <div class="steps">
    <div class="step"><i>1</i><h3>Encode</h3><p>The coordinator describes the activity, its barangay, dates and budget, and attaches documents.</p></div>
    <div class="step"><i>2</i><h3>Check</h3><p>EXTRAK suggests a category and shows any similar activities before anything is submitted.</p></div>
    <div class="step"><i>3</i><h3>Approve</h3><p>The director reviews the activity and the overlap warning, then approves or returns it.</p></div>
    <div class="step"><i>4</i><h3>Report</h3><p>Completed work moves to a permanent archive and feeds the consolidated reports.</p></div>
  </div>
</div></section></div>

<section class="s" id="campuses"><div class="wrap">
  <div class="s-head"><div class="eyebrow">Campuses</div><h2>One record across the university.</h2><p>The archive stays when coordinators and directors change, so the next team starts from what was already done.</p></div>
  <div class="campuses"><span>Main Campus</span><span>Balilihan Campus</span><span>Bilar Campus</span><span>Calape Campus</span><span>Candijay Campus</span><span>Clarin Campus</span></div>
</div></section>

<div class="closing"><div class="wrap row"><h2>Ready to log this term's activities?</h2><a class="btn gold" href="{{ url('/admin/login') }}">Sign in to EXTRAK <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div></div>
<footer><div class="wrap"><span>EXTRAK · Research, Innovation and Extension Office</span><span>Bohol Island State University</span></div></footer>

</body>
</html>
