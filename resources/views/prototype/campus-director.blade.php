@php($role = 'cd')
<!DOCTYPE html>
<html lang="en">
<head>
@include('prototype.partials.head', ['title' => 'Campus Director'])
</head>
<body x-data="campusDirector" x-cloak>

{{-- ========== Sidebar ========== --}}
<aside class="side">
  <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.png') }}" alt="" width="34" height="34"><span><b>EXTRACK</b><small>Balilihan Campus</small></span></a>
  <nav class="side-nav" aria-label="Sections">
    <button type="button" :class="{on: view==='dashboard'}" @click="view='dashboard'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>Dashboard</button>
    <button type="button" :class="{on: view==='requests' && filter.tab==='waiting'}" @click="openList('waiting')">
      <svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>For approval<span class="count" x-show="waitingForMe.length" x-text="waitingForMe.length"></span></button>
    <button type="button" :class="{on: view==='requests' && filter.tab!=='waiting'}" @click="openList('all')">
      <svg viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>All requests</button>
    <button type="button" :class="{on: view==='map'}" @click="view='map'; selectedId=null">
      <svg viewBox="0 0 24 24"><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6 9 4Z"/><path d="M9 4v14M15 6v14"/></svg>Extension map</button>
    <button type="button" :class="{on: view==='archive'}" @click="view='archive'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="4" width="17" height="4.5" rx="1"/><path d="M5 8.5V19a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8.5M10 12.5h4"/></svg>Archive</button>
  </nav>

  <div class="proto">
    <p class="proto-title">Prototype controls</p>
    <label for="actor">Signed in as</label>
    <select id="actor" x-model="actor">
      <option value="cd">Campus Director</option>
      <option value="cdasst">Assistant (Lorna Abella)</option>
    </select>
    <p class="proto-note" x-show="!isDirector">Assistants can review and add remarks but cannot approve, return or decline.</p>
    <button type="button" class="link" @click="simulateNewRequest()">Simulate a new request from the Extension Director</button>
    <a class="link" href="{{ route('prototype.extension-director') }}">Open the Extension Director dashboard</a>
    <a class="link" href="{{ route('prototype.president') }}">Open the University President dashboard</a>
    <button type="button" class="link" @click="resetDemo()">Reset demo data</button>
  </div>

  @include('prototype.partials.me')
</aside>

@include('prototype.partials.topbar', ['where' => 'Office of the Campus Director, Balilihan Campus'])

<main class="main">

{{-- ========== Dashboard ========== --}}
<section x-show="view==='dashboard'">
  <div class="page-head">
    <div>
      <h1><span x-text="greeting()"></span>, <span x-text="me.name.split(' ').slice(0,2).join(' ')"></span>.</h1>
      <p>Here is what needs your decision at Balilihan Campus on <span x-text="todayLabel()"></span>. You see only this campus's requests.</p>
    </div>
    <button type="button" class="btn primary" @click="openList('waiting')" x-show="waitingForMe.length">Review requests</button>
  </div>

  <div class="tiles">
    <button type="button" class="tile" @click="openList('waiting')">
      <b x-text="waitingForMe.length"></b><span>Waiting for your approval</span><small>From the Extension Director</small></button>
    <button type="button" class="tile" :class="{bad: toResolve.length}" @click="openList('overlaps')">
      <b x-text="toResolve.length"></b><span>Overlaps to resolve</span><small>Similar work in the same area</small></button>
    <button type="button" class="tile" :class="{warn: overBudget.length}" @click="openList('waiting')">
      <b x-text="overBudget.length"></b><span>Over remaining budget</span><small>Among requests waiting for you</small></button>
    <button type="button" class="tile" @click="openList('president')">
      <b x-text="withPresident.length"></b><span>With the President</span><small>Approved by you, waiting for final approval</small></button>
  </div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>Waiting for your approval</h2><button type="button" class="link" @click="openList('waiting')" x-show="waitingForMe.length">See all</button></header>
      <template x-if="!waitingForMe.length"><p class="empty">Nothing is waiting for you. New requests from the Extension Director will show up here.</p></template>
      <ul class="rows">
        <template x-for="r in waitingForMe" :key="r.id">
          <li class="row-req">
            <button type="button" class="row-main" @click="open(r.id)">
              <b x-text="r.title"></b>
              <span class="meta"><span x-text="r.barangays.join(', ')"></span><span x-text="fmtDate(r.start)"></span><span x-text="peso(r.budget)"></span><span x-text="waitingSince(r) ? 'Waiting ' + waitingSince(r) + (waitingSince(r) === 1 ? ' day' : ' days') : 'Submitted today'"></span></span>
              <span class="flags">
                <span class="flag bad" x-show="r.overlap && !r.overlap.resolution" x-text="r.overlap ? 'Possible overlap, ' + r.overlap.score + '% similar' : ''"></span>
                <span class="flag warn" x-show="budgetFor(r).over" x-text="'Over remaining budget by ' + peso(budgetFor(r).by)"></span>
                <span class="flag warn" x-show="missingDocs(r).length" x-text="'Missing: ' + missingDocs(r).join(', ')"></span>
              </span>
            </button>
            <div class="row-actions" x-show="isDirector">
              <button type="button" class="btn sm primary" @click="askDecision(r,'approve')">Approve</button>
              <button type="button" class="btn sm ghost" @click="askDecision(r,'return')">Return</button>
            </div>
          </li>
        </template>
      </ul>
    </article>

    @include('prototype.partials.dash-budget')
  </div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>With the University President</h2><button type="button" class="link" @click="openList('president')" x-show="withPresident.length">See all</button></header>
      <template x-if="!withPresident.length"><p class="empty">None of your approved requests are waiting for the President.</p></template>
      <ul class="rows">
        <template x-for="r in withPresident" :key="r.id">
          <li><button type="button" class="row-main pipe" @click="open(r.id)">
            <span class="pipe-top"><b x-text="r.title"></b><span class="pill t-wait" x-text="'Waiting ' + waitingSince(r) + (waitingSince(r) === 1 ? ' day' : ' days')"></span></span>
            <span class="mini-steps"><template x-for="s in steps(r)" :key="s.label"><i :class="s.state" :title="s.label"></i></template></span>
            <span class="meta"><span x-text="'You approved it on ' + fmtDate(lastTo(r, 'for_president').at.slice(0, 10))"></span><span x-text="peso(r.budget)"></span></span>
          </button></li>
        </template>
      </ul>
    </article>

    @include('prototype.partials.dash-ongoing')
  </div>

  @include('prototype.partials.dash-coverage')
</section>

@include('prototype.partials.requests', ['intro' => 'Every extension request from the Balilihan Campus extension office. You approve them for the campus, then they go to the University President.'])

@include('prototype.partials.map')

@include('prototype.partials.archive')

@include('prototype.partials.notifications', ['notifIntro' => 'New requests from the extension office, the President\'s decisions and accomplishment reports.'])
</main>

@include('prototype.partials.drawer')

@include('prototype.partials.modals')
</body>
</html>
