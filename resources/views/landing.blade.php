<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EXTRACK · BISU Extension Services</title>
<meta name="description" content="EXTRACK keeps the completed extension services of every Bohol Island State University campus in one place: their accomplishment reports, documents and photos, searchable and on one map.">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#272150">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0E0C24">
@include('partials.favicons')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://unpkg.com">
<link rel="preconnect" href="https://a.tile.openstreetmap.org">
<link rel="preconnect" href="https://b.tile.openstreetmap.org">
<link rel="preconnect" href="https://c.tile.openstreetmap.org">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Public+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
{{-- Stylized Bohol outline, reused by the hero preview and the campus map --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <path id="bohol" d="M18 78C14 62 28 50 40 40C52 30 66 24 82 22C100 18 116 12 134 14C150 15 164 20 176 32C186 44 190 58 186 72C183 86 188 98 194 108C190 116 176 114 166 112C150 116 138 122 122 122C104 124 88 118 72 118C58 118 48 112 38 108C28 104 20 94 18 78Z"/>
    <ellipse id="panglao" cx="30" cy="121" rx="10" ry="4.5" transform="rotate(-12 30 121)"/>
  </defs>
</svg>

<header class="topbar" id="top"><div class="wrap">
  <nav class="nav" aria-label="Main">
    <a class="brand" href="#top"><img src="{{ asset('images/brand/extrack-mark-reversed.svg') }}" alt="" width="40" height="40"><b translate="no">EXTRACK</b></a>
    <div class="links"><a href="#features">What it does</a><a href="#how">How it works</a><a href="#campuses">Campuses</a></div>
    <a class="btn gold sm" href="{{ url('/login') }}">Sign in</a>
  </nav>
</div></header>

<main id="main" tabindex="-1">
<section class="hero">
  <div class="wrap">
    <div class="hero-grid">
      <div class="hero-copy">
        <p class="org">Bohol Island State University, Extension Services</p>
        <h1>One view of every barangay we serve.</h1>
        <p class="lead">EXTRACK keeps every campus’s completed extension services in one place, with their accomplishment reports, documents and photos. Find any service by barangay, campus or year, see it on the map, and export reports.</p>
        <div class="cta">
          <a class="btn gold" href="{{ url('/login') }}">Sign in to EXTRACK</a>
          <a class="btn line" href="#how">See how it works</a>
        </div>
        <p class="note">For each campus’s uploaders, the Campus Directors and the University President. Accounts are issued by the University President.</p>
      </div>

      <figure class="preview" aria-label="Example of a completed service in EXTRACK">
        <div class="pv-map">
          {{-- the same OpenStreetMap map as the dashboards; the drawing below shows until it loads, or if it cannot --}}
          <div class="pv-live live-map" id="pv-live" role="img" aria-hidden="true" aria-label="Map of Bohol with extension activities marked"></div>
          <svg class="pv-fallback" viewBox="0 0 210 135" role="img" aria-label="Map of Bohol with extension activities marked">
            <use href="#bohol" class="land"/><use href="#panglao" class="land"/>
            <g class="m comp"><circle cx="44" cy="46" r="3.6"/><circle cx="96" cy="40" r="2.8"/><circle cx="120" cy="96" r="2.8"/><circle cx="150" cy="56" r="2.8"/><circle cx="66" cy="84" r="2.8"/><circle cx="172" cy="100" r="2.8"/><circle cx="40" cy="98" r="2.8"/><circle cx="130" cy="34" r="2.8"/></g>
          </svg>
          <ul class="legend"><li class="comp">Completed services</li></ul>
        </div>
        <div class="pv-body">
          <div class="flagrow">
            <div><span class="chip-ok">Published · CAL-2026-019</span><b>Seaweed farming training for coastal women</b><small>Sampoloc, Calape · Feb 2 to Apr 24, 2026</small></div>
            <div class="pc ok"><span data-count="38">38</span><small>people reached</small></div>
          </div>
          <div class="vs">
            <div><span>Accomplishment report</span>Scanned copy, PDF</div>
            <div><span>Attendance sheets and photos</span>3 files</div>
          </div>
        </div>
      </figure>
    </div>
  </div>
</section>

<section class="s" id="features"><div class="wrap">
  <div class="s-head">
    <h2>Every report, kept where it can be found.</h2>
    <p>Extension records used to live in separate folders on separate campuses. EXTRACK brings them into one repository, so each office can see what has been done and where.</p>
  </div>

  <div class="check">
    <div class="check-copy">
      <h3>Upload a soft copy or a scan.</h3>
      <p>A campus uploads the accomplishment report as it is, a soft copy or a scanned copy, then adds the barangays, dates and people reached, with the attendance sheets and photos. Files stay private to signed-in users.</p>
    </div>
    <div class="compare" role="group" aria-label="Example of an uploaded service record">
      <div class="pair">
        <article><span>Accomplishment report</span><p>Seaweed farming training for coastal women. Scanned copy, PDF.</p></article>
        <article><span>Supporting files</span><p>Attendance sheet (soft copy), two photos of the planting lines and the first harvest.</p></article>
      </div>
      <dl class="factors">
        <div><dt>Barangay</dt><dd>Sampoloc, Calape</dd></div>
        <div><dt>Reached</dt><dd>38 women</dd></div>
        <div><dt>Status</dt><dd>Published as CAL-2026-019</dd></div>
      </dl>
    </div>
  </div>

  <ul class="more">
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-5.7-6.5-11a6.5 6.5 0 0 1 13 0c0 5.3-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg><h3>Every campus on one map</h3><p>See the completed services of all campuses, and filter them by campus, category or year.</p></li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.5-4.5"/></svg><h3>Search across campuses</h3><p>Find a service by title, barangay, code or year, then open its report and photos.</p></li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7l4 4v13H7z"/><path d="M14 3.5v4h4M10 13h5M10 16.5h5"/></svg><h3>Consolidated reports</h3><p>Filter the repository by year, campus or category, then export the results as a PDF or for Excel.</p></li>
  </ul>
</div></section>

<section class="s band" id="how"><div class="wrap">
  <div class="s-head">
    <h2>From upload to report.</h2>
    <p>Each record keeps its files and its history, so anyone reviewing it can see who added what.</p>
  </div>
  <ol class="steps">
    <li><i>1</i><h3>Upload</h3><p>The campus uploads the accomplishment report, as a soft copy or a scanned copy.</p></li>
    <li><i>2</i><h3>Describe</h3><p>They fill in the title, barangays, dates and people reached, and add attendance sheets and photos.</p></li>
    <li><i>3</i><h3>Publish</h3><p>The record gets its reference code and joins the repository, where every campus can find it.</p></li>
    <li><i>4</i><h3>Report</h3><p>The University President and the campuses search, map and export reports across all campuses.</p></li>
  </ol>
</div></section>

<section class="s" id="campuses"><div class="wrap campus-grid">
  <div class="s-head">
    <h2>One record across the university.</h2>
    <p>The repository stays when people change, so the next team starts from what was already done.</p>
    <ul class="campus-list"><li>Main Campus, Tagbilaran</li><li>Balilihan Campus</li><li>Bilar Campus</li><li>Calape Campus</li><li>Candijay Campus</li><li>Clarin Campus</li></ul>
  </div>
  <div class="campus-map-wrap">
  <div class="campus-live live-map" id="campus-live" role="img" aria-hidden="true" aria-label="Map of Bohol showing the six BISU campuses"></div>
  <svg class="campus-map pv-fallback" viewBox="0 0 210 135" role="img" aria-label="Map of Bohol showing the six BISU campuses">
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
  </div>
</div></section>

<section class="closing"><div class="wrap row"><h2>Ready to upload this term’s reports?</h2><a class="btn gold" href="{{ url('/login') }}">Sign in to EXTRACK</a></div></section>
</main>

<footer><div class="wrap"><span>EXTRACK, Research, Innovation and Extension Office</span><span>Bohol Island State University</span></div></footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  // The landing page maps: real Bohol from OpenStreetMap, fixed in place. The drawing stays if the map cannot load.
  function liveMap(id, bounds) {
    var el = document.getElementById(id);
    if (!el || !window.L) return null;
    var map = L.map(el, {
      zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false,
      boxZoom: false, keyboard: false, touchZoom: false, tap: false, zoomSnap: 0.25,
    });
    map.fitBounds(bounds);
    L.control.attribution({ position: 'bottomleft', prefix: false }).addAttribution('&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>').addTo(map);
    var tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
    tiles.once('load', function () {
      el.parentNode.classList.add('live');
      el.removeAttribute('aria-hidden');
      el.parentNode.querySelector('.pv-fallback').setAttribute('aria-hidden', 'true');
    });
    return map;
  }

  // Hero: sample activities at their towns (approximate positions)
  (function () {
    var map = liveMap('pv-live', [[9.56, 123.78], [10.12, 124.6]]);
    if (!map) return;
    var pins = [
      ['comp', 9.877, 123.893], // the record shown below, in Calape
      ['comp', 9.7553, 123.9703], ['comp', 9.7095, 124.1083],
      ['comp', 9.6476, 123.855], ['comp', 9.9617, 124.0247], ['comp', 9.8186, 124.4958],
      ['comp', 9.9524, 123.9625], ['comp', 9.799, 123.8],
    ];
    pins.forEach(function (p) {
      L.circleMarker([p[1], p[2]], { radius: 5.5, weight: 1.5, color: '#fff', fillOpacity: 1, className: 'm ' + p[0], interactive: false }).addTo(map);
    });
    L.marker([9.877, 123.893], { icon: L.divIcon({ className: 'pv-ring ok', html: '<i></i>', iconSize: [34, 34] }), interactive: false, keyboard: false }).addTo(map);
  })();

  // Campuses: one pin per campus town
  (function () {
    var map = liveMap('campus-live', [[9.53, 123.8], [10.05, 124.56]]);
    if (!map) return;
    [['Main', 9.6476, 123.855, 'bottom'], ['Balilihan', 9.7553, 123.9703, 'top'], ['Bilar', 9.7095, 124.1083, 'top'],
      ['Calape', 9.8918, 123.8768, 'top'], ['Clarin', 9.9617, 124.0247, 'top'], ['Candijay', 9.8186, 124.4958, 'top']].forEach(function (c) {
      L.circleMarker([c[1], c[2]], { radius: 7, weight: 2, className: 'campus-pin', fillOpacity: 1, interactive: false })
        .bindTooltip(c[0], { permanent: true, direction: c[3], offset: [0, c[3] === 'top' ? -8 : 8], className: 'campus-label' })
        .addTo(map);
    });
  })();

  // Count the hero's people-reached number up (skipped when reduced motion is on)
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
