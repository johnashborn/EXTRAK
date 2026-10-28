<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EXTRACK</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
{{-- Stylized Bohol outline, reused by the hero preview and the campus map --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <path id="bohol" d="M18 78C14 62 28 50 40 40C52 30 66 24 82 22C100 18 116 12 134 14C150 15 164 20 176 32C186 44 190 58 186 72C183 86 188 98 194 108C190 116 176 114 166 112C150 116 138 122 122 122C104 124 88 118 72 118C58 118 48 112 38 108C28 104 20 94 18 78Z"/>
    <ellipse id="panglao" cx="30" cy="121" rx="10" ry="4.5" transform="rotate(-12 30 121)"/>
  </defs>
</svg>

<header class="hero" id="top">
  <div class="ghost" aria-hidden="true">EXTRACK</div>
  <div class="wrap">
    <nav class="nav" aria-label="Main">
      <a class="brand" href="#top"><img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40"><b>EXTRACK</b></a>
      <div class="links"><a href="#features">What it does</a><a href="#how">How it works</a><a href="#campuses">Campuses</a></div>
      <a class="btn gold sm" href="{{ url('/admin/login') }}">Sign in</a>
    </nav>
    <div class="hero-grid">
      <div class="hero-copy">
        <p class="org">Bohol Island State University, Extension Services</p>
        <h1>One view of every barangay we serve.</h1>
        <p class="lead">EXTRACK helps the extension office record, review and report community work across every campus. It also warns directors before two campuses plan the same thing for the same community.</p>
        <div class="cta">
          <a class="btn gold" href="{{ url('/admin/login') }}">Sign in to EXTRACK</a>
          <a class="btn line" href="#how">See how it works</a>
        </div>
        <p class="note">For Extension Directors, Campus Directors and the University President. Accounts are issued by the extension office.</p>
      </div>

      <figure class="preview" aria-label="Example of an overlap warning in EXTRACK">
        <div class="pv-map">
          <svg viewBox="0 0 210 135" role="img" aria-label="Map of Bohol with extension activities marked">
            <use href="#bohol" class="land"/><use href="#panglao" class="land"/>
            <g class="m ong"><circle cx="44" cy="46" r="3.6"/><circle cx="96" cy="40" r="2.8"/><circle cx="120" cy="96" r="2.8"/></g>
            <g class="m comp"><circle cx="150" cy="56" r="2.8"/><circle cx="66" cy="84" r="2.8"/><circle cx="172" cy="100" r="2.8"/></g>
            <g class="m prop"><circle cx="40" cy="98" r="2.8"/><circle cx="130" cy="34" r="2.8"/></g>
            <circle class="ring" cx="44" cy="46" r="9" pathLength="100"/>
          </svg>
          <ul class="legend"><li class="ong">Ongoing</li><li class="comp">Completed</li><li class="prop">Proposed</li></ul>
        </div>
        <div class="pv-body">
          <div class="flagrow">
            <div><span class="chip-flag">Possible overlap</span><b>Sampoloc, Calape</b><small>Same barangay, overlapping dates</small></div>
            <div class="pc"><span data-count="87">87</span>%<small>similar</small></div>
          </div>
          <div class="meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="87" aria-label="Similarity"><i></i></div>
          <div class="vs">
            <div><span>Calape Campus</span>Seaweed farming training for coastal women</div>
            <div><span>Main Campus</span>Aquaculture skills seminar for fisherfolk families</div>
          </div>
        </div>
      </figure>
    </div>
  </div>
</header>

<main>
<section class="s" id="features"><div class="wrap">
  <div class="s-head">
    <h2>Know what is already happening before you plan.</h2>
    <p>Extension records used to live in separate folders on separate campuses. EXTRACK puts them in one place that every authorized office can search.</p>
  </div>

  <div class="check">
    <div class="check-copy">
      <h3>Catches overlaps even when the wording differs.</h3>
      <p>When the Extension Director describes a new service, EXTRACK compares its meaning, place and dates with existing work. A seaweed farming training and an aquaculture seminar in the same barangay get flagged, even though they share few exact words.</p>
    </div>
    <div class="compare" aria-label="Example comparison of two activities">
      <div class="pair">
        <article><span>New, Calape Campus</span><p>Training on <mark>seaweed</mark> farming and drying for coastal women, to add a second <mark>income</mark> source for <mark>fisherfolk</mark> households.</p></article>
        <article><span>Existing, Main Campus</span><p>Aquaculture skills seminar on <mark>seaweed</mark> and tilapia culture for <mark>fisherfolk</mark> families, focused on household <mark>income</mark>.</p></article>
      </div>
      <dl class="factors">
        <div><dt>Meaning</dt><dd><i style="--v:91%"></i>91%</dd></div>
        <div><dt>Place</dt><dd>Same barangay</dd></div>
        <div><dt>Timing</dt><dd>Overlaps by 6 weeks</dd></div>
      </dl>
    </div>
  </div>

  <ul class="more">
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/></svg><h3>Every campus in one list</h3><p>Search and filter by campus, barangay, date, status and category.</p></li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5"/></svg><h3>Approvals that leave a trail</h3><p>The Campus Director, then the University President, approves or returns each request with a remark. The Extension Director sees why.</p></li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7l4 4v13H7z"/><path d="M14 3.5v4h4M10 13h5M10 16.5h5"/></svg><h3>Reports ready for CHED</h3><p>Consolidated accomplishments by campus, quarter or category, exported to PDF or Excel.</p></li>
  </ul>
</div></section>

<section class="s band" id="how"><div class="wrap">
  <div class="s-head"><h2>From encoding to the annual report.</h2></div>
  <ol class="steps">
    <li><i>1</i><h3>Request</h3><p>The Extension Director describes the service, its barangay, dates and budget, and attaches documents.</p></li>
    <li><i>2</i><h3>Check</h3><p>EXTRACK suggests a category and shows any similar activities before anything is submitted.</p></li>
    <li><i>3</i><h3>Approve</h3><p>The Campus Director approves it for their campus, then the University President gives final approval.</p></li>
    <li><i>4</i><h3>Report</h3><p>Completed work moves to a permanent archive and feeds the consolidated reports.</p></li>
  </ol>
</div></section>

<section class="s" id="campuses"><div class="wrap campus-grid">
  <div class="s-head">
    <h2>One record across the university.</h2>
    <p>The archive stays when directors change, so the next team starts from what was already done.</p>
    <ul class="campus-list"><li>Main Campus, Tagbilaran</li><li>Balilihan Campus</li><li>Bilar Campus</li><li>Calape Campus</li><li>Candijay Campus</li><li>Clarin Campus</li></ul>
  </div>
  <svg class="campus-map" viewBox="0 0 210 135" role="img" aria-label="Map of Bohol showing the six BISU campuses">
    <use href="#bohol" class="land"/><use href="#panglao" class="land"/>
    <g class="pins">
      <g transform="translate(40 102)"><circle r="3.4"/><text x="-6" y="13" text-anchor="middle">Main</text></g>
      <g transform="translate(62 76)"><circle r="3.4"/><text x="0" y="-7" text-anchor="middle">Balilihan</text></g>
      <g transform="translate(96 96)"><circle r="3.4"/><text x="0" y="-7" text-anchor="middle">Bilar</text></g>
      <g transform="translate(44 46)"><circle r="3.4"/><text x="-7" y="-7" text-anchor="middle">Calape</text></g>
      <g transform="translate(80 30)"><circle r="3.4"/><text x="0" y="-7" text-anchor="middle">Clarin</text></g>
      <g transform="translate(168 100)"><circle r="3.4"/><text x="0" y="-7" text-anchor="middle">Candijay</text></g>
    </g>
  </svg>
</div></section>
</main>

<div class="closing"><div class="wrap row"><h2>Ready to log this term's activities?</h2><a class="btn gold" href="{{ url('/admin/login') }}">Sign in to EXTRACK</a></div></div>
<footer><div class="wrap"><span>EXTRACK, Research, Innovation and Extension Office</span><span>Bohol Island State University</span></div></footer>

<script>
  // Count the hero similarity score up alongside the meter fill (skipped when reduced motion is on)
  (function () {
    var el = document.querySelector('[data-count]');
    if (!el || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var target = +el.dataset.count, start = null, delay = 700, dur = 1300;
    el.textContent = '0';
    function tick(t) {
      if (start === null) start = t;
      var p = Math.min(Math.max((t - start - delay) / dur, 0), 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  })();
</script>
</body>
</html>
