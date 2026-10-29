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
    <button type="button" :class="{on: view==='budgets'}" @click="openBudgets()">
      <svg viewBox="0 0 24 24"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z"/><path d="M16 12h4M4 9h16"/><circle cx="16.5" cy="12" r=".6"/></svg>Campus budgets<span class="count soft" x-show="budgetDraft && budgetSummary.changed" x-text="budgetDraft ? budgetSummary.changed : ''" title="Unsaved changes"></span></button>
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
  <div class="page-head hero-banner">
    <div class="hero-art" aria-hidden="true"></div>
    <div>
      <h1><span x-text="greeting()"></span>, <span x-text="me.name.split(' ').slice(0,2).join(' ')"></span>.</h1>
      <p>Extension work across all six BISU campuses on <span x-text="todayLabel()"></span>.</p>
      <p class="core-values"><b>B</b>alance · <b>I</b>ntegrity · <b>U</b>prightness · <b>S</b>tewardship</p>
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
    <button type="button" class="tile plain" @click="openBudgets()">
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
                <span class="flag bad" x-show="r.overlap" x-text="r.overlap ? 'Overlap, ' + r.overlap.score + '% similar' + (r.overlap.resolution ? ' (Campus Director of ' + campusName(r) + ': ' + {allow:'allowed both', coordinate:'will coordinate'}[r.overlap.resolution] + ')' : '') : ''"></span>
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
            <span class="when" :class="r.overlap.resolution ? 't-muted' : 't-bad'" x-text="r.overlap.resolution ? 'Campus Director of ' + campusName(r) + ': ' + {allow:'allowed both', coordinate:'campuses will coordinate'}[r.overlap.resolution] : 'Not resolved yet'"></span>
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

{{-- ========== Campus budgets ========== --}}
<section x-show="view==='budgets'">
  <template x-if="budgetDraft">
  <div class="budgets">
    <div class="page-head">
      <div><h1>Campus budgets</h1>
        <p>Set the university extension budget for <span x-text="db.year"></span>, then divide it among the campuses. What you don't give out stays as the university reserve for top-ups during the year. Requests over a campus's budget are flagged, not blocked.</p></div>
    </div>

    {{-- the university total, how much of it is given to campuses, and the reserve --}}
    <div class="bud-hero">
      <div class="bud-hero-main">
        <label class="eyebrow" for="bud-total" x-text="'University extension budget, ' + db.year"></label>
        <span class="bud-total-input"><span aria-hidden="true">₱</span>
          <input id="bud-total" type="text" inputmode="numeric" x-model="budgetDraft.total" @input="budgetDraft.error=''" @blur="tidyTotal()" @keydown.enter="saveBudgets()"></span>
        <span class="bud-delta" x-show="budgetSummary.totalChanged" :class="budgetSummary.total >= budgetSummary.saved ? 'up' : 'down'"
          x-text="(budgetSummary.total >= budgetSummary.saved ? '+' : '−') + peso(Math.abs(budgetSummary.total - budgetSummary.saved)) + ' from the saved ' + peso(budgetSummary.saved)"></span>

        <div class="bud-split" aria-hidden="true">
          <template x-for="x in budgetCards" :key="x.c"><i :style="'flex:' + Math.max(x.amount, 1) + ';background:' + x.color" :title="x.c + ' ' + peso(x.amount)"></i></template>
          <i class="reserve" x-show="budgetSummary.reserve > 0" :style="'flex:' + Math.max(budgetSummary.reserve, 0)" :title="'Reserve ' + peso(budgetSummary.reserve)"></i>
        </div>
        <ul class="bud-split-key">
          <template x-for="x in budgetCards" :key="x.c"><li><i :style="'background:' + x.color"></i><span x-text="x.c"></span><b x-text="budgetSummary.total ? Math.round(x.amount / budgetSummary.total * 100) + '%' : '0%'"></b></li></template>
          <li x-show="budgetSummary.reserve > 0"><i class="reserve"></i><span>Reserve</span><b x-text="budgetSummary.total ? Math.round(budgetSummary.reserve / budgetSummary.total * 100) + '%' : '0%'"></b></li>
        </ul>
        <div class="bud-hero-actions">
          <button type="button" class="btn gold" @click="askSplit()">
            <svg viewBox="0 0 24 24"><path d="M12 3v6M12 9 6 15M12 9l6 6M6 15v6M18 15v6"/></svg>Split the total among campuses</button>
        </div>
      </div>
      <dl class="bud-hero-stats">
        <div><dt>Given to campuses</dt><dd x-text="peso(budgetSummary.allocated)"></dd>
          <small x-text="budgetSummary.total ? Math.round(budgetSummary.allocated / budgetSummary.total * 100) + '% of the university budget' : 'Set the university budget first'"></small></div>
        <div :class="{alarm: budgetSummary.reserve < 0}">
          <dt x-text="budgetSummary.reserve < 0 ? 'Over-allocated by' : 'University reserve'"></dt><dd x-text="peso(Math.abs(budgetSummary.reserve))"></dd>
          <small x-text="budgetSummary.reserve < 0 ? 'Campus budgets add up to more than the total' : 'Not given to any campus yet, for top-ups'"></small></div>
        <div><dt>Used by campuses</dt><dd x-text="peso(budgetSummary.used)"></dd>
          <small x-text="peso(budgetSummary.pending) + ' more is waiting for approval'"></small></div>
      </dl>
    </div>

    <div class="bud-grid">
      <template x-for="x in budgetCards" :key="x.c">
        <article class="bud-card" :class="{changed: x.changed, over: x.below}" :style="'--c:' + x.color">
          <header>
            <span class="bud-badge" x-text="x.c.slice(0, 2).toUpperCase()"></span>
            <div><h2 x-text="x.c + ' Campus'"></h2>
              <small x-text="x.last ? 'Last changed ' + fmtDate(x.last.at) + ', was ' + peso(x.last.from) : 'Not changed this year'"></small></div>
            <span class="bud-pill" x-show="x.changed">Unsaved</span>
          </header>

          <label class="bud-amount" :for="'bud-' + x.c"><span>Budget for <span x-text="db.year"></span></span>
            <span class="peso-input"><span aria-hidden="true">₱</span>
              <input :id="'bud-' + x.c" type="text" inputmode="numeric" x-model="budgetDraft.values[x.c]" @input="budgetDraft.error=''" @blur="tidyBudget(x.c)" @keydown.enter="saveBudgets()"></span></label>

          <div class="bud-meter" aria-hidden="true">
            <i class="spent" :style="'width:' + x.bars.spent"></i><i class="committed" :style="'width:' + x.bars.committed"></i><i class="pending" :style="'width:' + x.bars.pending"></i>
            <em :style="'left:' + x.bars.mark"></em>
          </div>
          <dl class="bud-figs">
            <div><dt><i class="bdot spent"></i>Spent</dt><dd x-text="peso(x.b.spent)"></dd></div>
            <div><dt><i class="bdot committed"></i>Committed</dt><dd x-text="peso(x.b.committed)"></dd></div>
            <div><dt><i class="bdot pending"></i>Pending</dt><dd x-text="peso(x.b.pending)"></dd></div>
            <div><dt>Remaining</dt><dd :class="{neg: x.remaining < 0}" x-text="peso(x.remaining)"></dd></div>
          </dl>
          <p class="bud-warn" x-show="x.below" x-text="'This is ' + peso(x.used - x.amount) + ' less than the campus has already used. It will show as over budget.'"></p>
          <div class="bud-card-foot">
            <button type="button" class="link" @click="openList('all', x.c)">See requests</button>
            <button type="button" class="btn sm topup" @click="askTopUp(x.c)" :disabled="savedReserve <= 0" :title="savedReserve <= 0 ? 'The university reserve is empty' : 'Reserve: ' + peso(savedReserve)">
              <svg viewBox="0 0 24 24"><path d="M12 19V5M5.5 11.5 12 5l6.5 6.5"/></svg>Top up from reserve</button>
          </div>
        </article>
      </template>
    </div>

    <div class="bud-bar" :class="{dirty: budgetSummary.changed}">
      <p x-show="!budgetDraft.error" x-text="budgetSummary.changed ? budgetSummary.changed + (budgetSummary.changed === 1 ? ' budget changed, not saved yet' : ' budgets changed, not saved yet') + (budgetSummary.reserve < 0 ? '. Campus budgets are over the total by ' + peso(-budgetSummary.reserve) + '.' : '') : 'All budgets are saved.'"></p>
      <p class="err" x-show="budgetDraft.error" x-text="budgetDraft.error"></p>
      <button type="button" class="btn ghost" @click="resetBudgets()" :disabled="!budgetSummary.changed">Discard changes</button>
      <button type="button" class="btn primary" @click="saveBudgets()" :disabled="!budgetSummary.changed">Save budgets</button>
    </div>

    <article class="panel" x-show="budgetHistory.length">
      <header class="panel-head"><h2>Budget changes in <span x-text="db.year"></span></h2></header>
      <ul class="rows">
        <template x-for="(e, i) in budgetHistory" :key="i">
          <li class="bud-log"><i :class="{uni: !e.campus}" :style="e.campus ? 'background:' + CAMPUS_COLORS[e.campus] : ''"></i>
            <span><span x-text="budgetLogText(e)"></span><small class="why" x-show="e.reason" x-text="'Reason: ' + e.reason"></small></span>
            <small x-text="fmtDateTime(e.at)"></small></li>
        </template>
      </ul>
    </article>
  </div>
  </template>
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
