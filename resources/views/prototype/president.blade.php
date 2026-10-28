@php($role = 'pres')
<!DOCTYPE html>
<html lang="en">
<head>
@include('prototype.partials.head', ['title' => 'University President'])
</head>
<body x-data="universityPresident" x-cloak>

{{-- ========== Sidebar ========== --}}
<aside class="side">
  <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.png') }}" alt="" width="34" height="34"><span><b>EXTRACK</b><small>All campuses</small></span></a>
  <nav class="side-nav" aria-label="Sections">
    <button type="button" :class="{on: view==='dashboard'}" @click="view='dashboard'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>Dashboard</button>
    <button type="button" :class="{on: view==='requests' && filter.tab==='waiting'}" @click="openList('waiting')">
      <svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>Final approval<span class="count" x-show="queue.length" x-text="queue.length"></span></button>
    <button type="button" :class="{on: view==='requests' && filter.tab!=='waiting'}" @click="openList('all')">
      <svg viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>All requests</button>
    <button type="button" :class="{on: view==='map'}" @click="view='map'; selectedId=null">
      <svg viewBox="0 0 24 24"><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6 9 4Z"/><path d="M9 4v14M15 6v14"/></svg>Extension map</button>
    <button type="button" :class="{on: view==='archive'}" @click="view='archive'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="4" width="17" height="4.5" rx="1"/><path d="M5 8.5V19a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8.5M10 12.5h4"/></svg>Archive</button>
  </nav>

  <div class="proto">
    <p class="proto-title">Prototype controls</p>
    <p class="proto-note">Only Balilihan Campus has working director dashboards. The other campuses are sample data.</p>
    <a class="link" href="{{ route('prototype.extension-director') }}">Open the Extension Director dashboard</a>
    <a class="link" href="{{ route('prototype.campus-director') }}">Open the Campus Director dashboard</a>
    <button type="button" class="link" @click="resetDemo()">Reset demo data</button>
  </div>

  @include('prototype.partials.me')
</aside>

@include('prototype.partials.topbar', ['where' => 'Office of the University President, all campuses'])

<main class="main">

{{-- ========== Dashboard ========== --}}
<section x-show="view==='dashboard'">
  <div class="page-head">
    <div>
      <h1><span x-text="greeting()"></span>, <span x-text="me.name.split(' ').slice(0,2).join(' ')"></span>.</h1>
      <p>Extension work across all six BISU campuses on <span x-text="todayLabel()"></span>.</p>
    </div>
    <button type="button" class="btn primary" @click="openList('waiting')" x-show="queue.length">Review requests</button>
  </div>

  <div class="tiles">
    <button type="button" class="tile" @click="openList('waiting')">
      <b x-text="queue.length"></b><span>Waiting for your final approval</span><small>Already approved by their Campus Directors</small></button>
    <button type="button" class="tile plain" @click="openList('running')">
      <b x-text="totals.services"></b><span>Services in <span x-text="db.year"></span></span><small x-text="totals.completed + ' completed, in ' + totals.barangays + ' barangays'"></small></button>
    <button type="button" class="tile plain" @click="openList('completed')">
      <b x-text="totals.reached.toLocaleString('en-PH')"></b><span>People reached</span><small>From submitted accomplishment reports</small></button>
    <button type="button" class="tile plain" @click="view='dashboard'; $nextTick(() => document.getElementById('campuses').scrollIntoView({behavior:'smooth'}))">
      <b><span x-text="totals.usedPct"></span><span class="unit">%</span></b><span>Of extension budgets used</span><small x-text="peso(totals.used) + ' of ' + peso(totals.alloc)"></small></button>
  </div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>Waiting for your final approval</h2><button type="button" class="link" @click="openList('waiting')" x-show="queue.length">See all</button></header>
      <template x-if="!queue.length"><p class="empty">Nothing is waiting for you. Requests approved by a Campus Director show up here.</p></template>
      <ul class="rows">
        <template x-for="r in queue" :key="r.id">
          <li class="row-req">
            <button type="button" class="row-main" @click="open(r.id)">
              <span class="ctag" x-text="campusName(r)"></span>
              <b x-text="r.title"></b>
              <span class="meta"><span x-text="r.barangays.join(', ') + ', ' + r.municipality"></span><span x-text="peso(r.budget)"></span><span x-text="'Waiting ' + waitingSince(r) + (waitingSince(r) === 1 ? ' day' : ' days')"></span></span>
              <span class="flags">
                <span class="flag bad" x-show="r.overlap" x-text="r.overlap ? 'Overlap, ' + r.overlap.score + '% similar' + (r.overlap.resolution ? ' (campus: ' + {allow:'allowed both', coordinate:'will coordinate'}[r.overlap.resolution] + ')' : '') : ''"></span>
                <span class="flag warn" x-show="budgetFor(r).over" x-text="'Over the campus budget by ' + peso(budgetFor(r).by)"></span>
                <span class="flag warn" x-show="missingDocs(r).length" x-text="'Missing: ' + missingDocs(r).join(', ')"></span>
              </span>
            </button>
            <div class="row-actions">
              <button type="button" class="btn sm primary" @click="askDecision(r,'approve')">Approve</button>
              <button type="button" class="btn sm ghost" @click="askDecision(r,'return')">Return</button>
            </div>
          </li>
        </template>
      </ul>
    </article>

    <article class="panel">
      <header class="panel-head"><h2>Overlaps between campuses</h2></header>
      <template x-if="!crossOverlaps.length"><p class="empty">No similar work flagged between campuses.</p></template>
      <ul class="rows">
        <template x-for="r in crossOverlaps" :key="r.id">
          <li><button type="button" class="row-main" @click="open(r.id)">
            <b x-text="r.title"></b>
            <span class="meta"><span x-text="campusName(r) + ' and ' + r.overlap.match.campus"></span><span x-text="r.overlap.score + '% similar'"></span></span>
            <span class="when" :class="r.overlap.resolution ? 't-muted' : 't-bad'" x-text="r.overlap.resolution ? 'Campus Director: ' + {allow:'allowed both', coordinate:'campuses will coordinate'}[r.overlap.resolution] : 'Not resolved yet'"></span>
          </button></li>
        </template>
      </ul>
    </article>
  </div>

  <article class="panel flush" id="campuses">
    <header class="panel-head pad"><h2>Campuses in <span x-text="db.year"></span></h2><p class="sub">Select a campus to see its requests</p></header>
    <div class="table-wrap">
      <table class="ctable">
        <thead><tr>
          <th>Campus</th><th class="n">Services</th><th class="n">Ongoing</th><th class="n">Completed</th><th class="n">People reached</th><th>Budget used</th><th class="n">Reports overdue</th><th class="n">Waiting for you</th>
        </tr></thead>
        <tbody>
          <template x-for="row in campusRows" :key="row.c">
            <tr>
              <td><button type="button" @click="openList('all', row.c)" x-text="row.c + ' Campus'"></button></td>
              <td class="n" x-text="row.services"></td>
              <td class="n" x-text="row.running"></td>
              <td class="n" x-text="row.completed"></td>
              <td class="n" :class="{zero: !row.reached}" x-text="row.reached.toLocaleString('en-PH')"></td>
              <td><div class="use"><i><b :style="'width:' + Math.min(row.pct, 100) + '%'"></b></i><span x-text="row.pct + '% of ' + peso(row.alloc)"></span></div></td>
              <td class="n" :class="row.overdue ? 'hot-n' : 'zero'" x-text="row.overdue"></td>
              <td class="n" :class="{zero: !row.waiting}" x-text="row.waiting"></td>
            </tr>
          </template>
        </tbody>
        <tfoot><tr>
          <td>All campuses</td>
          <td class="n" x-text="totals.services"></td>
          <td class="n" x-text="campusRows.reduce((t, r) => t + r.running, 0)"></td>
          <td class="n" x-text="totals.completed"></td>
          <td class="n" x-text="totals.reached.toLocaleString('en-PH')"></td>
          <td><div class="use"><i><b :style="'width:' + Math.min(totals.usedPct, 100) + '%'"></b></i><span x-text="totals.usedPct + '% of ' + peso(totals.alloc)"></span></div></td>
          <td class="n" x-text="totals.overdue"></td>
          <td class="n" x-text="queue.length"></td>
        </tr></tfoot>
      </table>
    </div>
  </article>
</section>

@include('prototype.partials.requests', ['intro' => 'Extension requests from all six campuses. Each is approved by its own Campus Director before it reaches you.'])

@include('prototype.partials.map')

@include('prototype.partials.archive')

@include('prototype.partials.notifications', ['notifIntro' => 'Requests approved by the Campus Directors, overdue reports and overlaps between campuses.'])
</main>

@include('prototype.partials.drawer')

@include('prototype.partials.modals')
</body>
</html>
