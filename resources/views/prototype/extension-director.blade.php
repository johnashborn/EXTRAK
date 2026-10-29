@php($role = 'ed')
<!DOCTYPE html>
<html lang="en">
<head>
@include('prototype.partials.head', ['title' => 'Extension Director'])
</head>
<body x-data="extensionDirector" x-cloak>

{{-- ========== Sidebar ========== --}}
<aside class="side">
  <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.png') }}" alt="" width="34" height="34"><span><b>EXTRACK</b><small>Balilihan Campus</small></span></a>
  <nav class="side-nav" aria-label="Sections">
    <button type="button" :class="{on: view==='dashboard'}" @click="view='dashboard'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>Dashboard</button>
    <button type="button" :class="{on: view==='requests'}" @click="openList('all')">
      <svg viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>Requests<span class="count" x-show="todo.length" x-text="todo.length" title="Needs your action"></span></button>
    <button type="button" :class="{on: view==='new'}" @click="newRequest()">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>New request</button>
    <button type="button" :class="{on: view==='reports'}" @click="view='reports'; selectedId=null">
      <svg viewBox="0 0 24 24"><path d="M7 3.5h7l4 4v13H7z"/><path d="M14 3.5v4h4M10 13h5M10 16.5h5"/></svg>Accomplishment reports<span class="count" x-show="reportsDue.length" x-text="reportsDue.length" title="Reports overdue"></span></button>
    <button type="button" :class="{on: view==='map'}" @click="view='map'; selectedId=null">
      <svg viewBox="0 0 24 24"><path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6 9 4Z"/><path d="M9 4v14M15 6v14"/></svg>Extension map</button>
    <button type="button" :class="{on: view==='archive'}" @click="view='archive'; selectedId=null">
      <svg viewBox="0 0 24 24"><rect x="3.5" y="4" width="17" height="4.5" rx="1"/><path d="M5 8.5V19a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8.5M10 12.5h4"/></svg>Archive</button>
  </nav>

  <div class="proto">
    <p class="proto-title">Prototype controls</p>
    <label for="actor">Signed in as</label>
    <select id="actor" x-model="actor">
      <option value="ed">Extension Director</option>
      <option value="asst">Assistant (Miss Jaquelyn L. Omapas)</option>
    </select>
    <p class="proto-note" x-show="!isDirector">Assistants can encode and save drafts but cannot approve or submit.</p>
    <a class="link" href="{{ route('prototype.campus-director') }}">Open the Campus Director dashboard</a>
    <a class="link" href="{{ route('prototype.president') }}">Open the University President dashboard</a>
    <button type="button" class="link" @click="resetDemo()">Reset demo data</button>
  </div>

  @include('prototype.partials.me')
</aside>

@include('prototype.partials.topbar', ['where' => 'Balilihan Campus extension office'])

<main class="main">

{{-- ========== Dashboard ========== --}}
<section x-show="view==='dashboard'">
  <div class="page-head hero-banner">
    <div class="hero-art" aria-hidden="true"></div>
    <div>
      <h1><span x-text="greeting()"></span>, <span x-text="me.name.split(' ').slice(0,2).join(' ')"></span>.</h1>
      <p>Here is where Balilihan Campus extension work stands on <span x-text="todayLabel()"></span>.</p>
      <p class="core-values"><b>B</b>alance · <b>I</b>ntegrity · <b>U</b>prightness · <b>S</b>tewardship</p>
    </div>
    <button type="button" class="btn primary" @click="newRequest()"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>New request</button>
  </div>

  <div class="tiles">
    <button type="button" class="tile" @click="openList('waiting')">
      <b x-text="waitingApproval.length"></b><span>Waiting for approval</span><small>With the Campus Director or President</small></button>
    <button type="button" class="tile" :class="{warn: returnedToMe.length}" @click="openList('returned')">
      <b x-text="returnedToMe.length"></b><span>Returned to you</span><small>Edit and resubmit</small></button>
    <button type="button" class="tile" :class="{bad: reportsDue.length}" @click="openList('reports')">
      <b x-text="reportsDue.length"></b><span>Reports overdue</span><small>Ended, no accomplishment report</small></button>
    <button type="button" class="tile" :class="{bad: overlapOpen.length}" @click="openList('overlaps')">
      <b x-text="overlapOpen.length"></b><span>Overlaps flagged</span><small>The Campus Director decides</small></button>
  </div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>Needs your action</h2></header>
      <template x-if="!todo.length"><p class="empty">You're all caught up. Returned requests, overdue reports and drafts will show up here.</p></template>
      <ul class="rows">
        <template x-for="t in todo" :key="t.r.id + t.run">
          <li class="row-req">
            <button type="button" class="row-main" @click="open(t.r.id)">
              <b x-text="t.r.title"></b>
              <span class="when" :class="'t-' + t.tone" x-text="t.why"></span>
            </button>
            <div class="row-actions">
              <button type="button" class="btn sm" :class="t.tone === 'muted' ? 'ghost' : 'primary'" @click="doTodo(t)" x-text="t.action"></button>
            </div>
          </li>
        </template>
      </ul>
    </article>

    @include('prototype.partials.dash-budget')
  </div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>Waiting for approval</h2><button type="button" class="link" @click="openList('waiting')" x-show="waitingApproval.length">See all</button></header>
      <template x-if="!waitingApproval.length"><p class="empty">No requests are waiting. Start one with New request.</p></template>
      <ul class="rows">
        <template x-for="r in waitingApproval" :key="r.id">
          <li><button type="button" class="row-main pipe" @click="open(r.id)">
            <span class="pipe-top"><b x-text="r.title"></b><span class="pill" :class="'t-'+statusTone(r)" x-text="statusText(r)"></span></span>
            <span class="mini-steps"><template x-for="s in steps(r)" :key="s.label"><i :class="s.state" :title="s.label"></i></template></span>
            <span class="flags">
              <span class="flag bad" x-show="r.overlap && !r.overlap.resolution" x-text="r.overlap ? 'Possible overlap, ' + r.overlap.score + '% similar' : ''"></span>
              <span class="flag warn" x-show="budgetFor(r).over" x-text="'Over remaining budget by ' + peso(budgetFor(r).by)"></span>
            </span>
          </button></li>
        </template>
      </ul>
    </article>

    @include('prototype.partials.dash-ongoing')
  </div>

  @include('prototype.partials.dash-coverage')
</section>

@include('prototype.partials.requests', ['intro' => 'Every extension request from the Balilihan Campus extension office. The Campus Director approves them, then the University President.'])

{{-- ========== New / edit request ========== --}}
<section x-show="view==='new'">
  <div class="page-head">
    <div><h1 x-text="form.id ? 'Edit request' : 'New extension request'"></h1>
      <p>Describe the service in plain words. EXTRACK suggests a category and checks every campus for similar work while you type. It goes to the Campus Director, then the University President.</p></div>
  </div>
  <div class="form-grid">
    <form class="panel form" @submit.prevent="saveForm(true)" novalidate>
      <div class="field"><label for="f-title">Title</label>
        <input id="f-title" x-model="form.title" placeholder="e.g. Tilapia pond culture training for farm families">
        <p class="err" x-show="formErrors.title" x-text="formErrors.title"></p></div>
      <div class="field"><label for="f-desc">Description</label>
        <textarea id="f-desc" rows="4" x-model="form.description" placeholder="What will you do, for whom, and what should change for the community?"></textarea>
        <p class="err" x-show="formErrors.description" x-text="formErrors.description"></p></div>
      <div class="two">
        <div class="field"><label for="f-cat">Category</label>
          <select id="f-cat" x-model="form.category"><option value="">Choose a category</option><template x-for="c in CATEGORIES" :key="c"><option :value="c" x-text="c" :selected="form.category===c"></option></template></select>
          <p class="hint" x-show="suggestedCategory && form.category !== suggestedCategory">Suggested: <button type="button" class="link" @click="form.category = suggestedCategory" x-text="suggestedCategory"></button></p>
          <p class="err" x-show="formErrors.category" x-text="formErrors.category"></p></div>
        <div class="field"><label for="f-mun">Municipality</label>
          <select id="f-mun" x-model="form.municipality" @change="form.barangays=[]"><template x-for="m in Object.keys(PLACES)" :key="m"><option :value="m" x-text="m" :selected="form.municipality===m"></option></template></select></div>
      </div>
      <fieldset class="field"><legend>Barangays</legend>
        <div class="chips pick"><template x-for="b in PLACES[form.municipality]" :key="b">
          <button type="button" :aria-pressed="form.barangays.includes(b)" @click="toggle(form.barangays, b)" x-text="b"></button></template></div>
        <p class="err" x-show="formErrors.barangays" x-text="formErrors.barangays"></p></fieldset>
      <div class="two">
        <div class="field"><label for="f-start">Start date</label><input id="f-start" type="date" x-model="form.start"></div>
        <div class="field"><label for="f-end">End date</label><input id="f-end" type="date" x-model="form.end" :min="form.start"></div>
      </div>
      <p class="err" x-show="formErrors.dates" x-text="formErrors.dates"></p>
      <div class="two">
        <div class="field"><label for="f-budget">Proposed budget (₱)</label><input id="f-budget" type="number" min="0" step="500" x-model="form.budget">
          <p class="err" x-show="formErrors.budget" x-text="formErrors.budget"></p></div>
        <div class="field"><label for="f-ben">Expected beneficiaries</label><input id="f-ben" type="number" min="0" x-model="form.beneficiaries">
          <p class="err" x-show="formErrors.beneficiaries" x-text="formErrors.beneficiaries"></p></div>
      </div>
      <fieldset class="field"><legend>Beneficiary sectors</legend>
        <div class="chips pick"><template x-for="s in allSectors" :key="s">
          <button type="button" :aria-pressed="form.sectors.includes(s)" @click="toggle(form.sectors, s)" x-text="s"></button></template>
          <button type="button" class="add-chip" x-show="!addingSector" @click="addingSector = true; $nextTick(() => $refs.newSector.focus())">+ Add sector</button>
          <span class="add-sector" x-show="addingSector">
            <input x-ref="newSector" x-model="newSector" maxlength="40" placeholder="e.g. Indigenous peoples" aria-label="New beneficiary sector"
              @keydown.enter.prevent="addSector()" @keydown.escape.stop="addingSector = false; newSector = ''">
            <button type="button" class="btn sm primary" @click="addSector()">Add</button>
            <button type="button" class="btn sm ghost" @click="addingSector = false; newSector = ''">Cancel</button>
          </span></div>
        <p class="hint">Not listed? Add it. New sectors stay in the list for later requests.</p></fieldset>
      <div class="field"><label for="f-part">Partner agencies or LGU <span class="opt">optional</span></label><input id="f-part" x-model="form.partners" placeholder="e.g. BFAR Region 7, Barangay Council"></div>
      <div class="field"><label>Supporting documents</label>
        <p class="hint">Attach the barangay letter of request and the project proposal, plus a budget breakdown or MOA if you have one. The Campus Director and the University President see these files. PDF, Word, Excel or photos, up to 20 MB each.</p>
        <label class="drop"><input type="file" multiple class="sr" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" @change="addDocs($event)">
          <svg viewBox="0 0 24 24"><path d="M12 15V4M7.5 8.5 12 4l4.5 4.5M5 15v3.5a1.5 1.5 0 0 0 1.5 1.5h11a1.5 1.5 0 0 0 1.5-1.5V15"/></svg>
          <span x-text="docBusy ? 'Uploading…' : 'Choose files'"></span></label>
        <p class="err" x-show="docError" x-text="docError"></p>
        <ul class="docs edit" x-show="form.attachments.length">
          <template x-for="(d, i) in form.attachments" :key="d.id">
            <li><span class="doc-ico" x-text="docExt(d)"></span>
              <span class="doc-meta"><b x-text="d.name"></b><small x-text="fmtSize(d.size)"></small></span>
              <select x-model="d.kind" aria-label="Document type"><template x-for="(label, k) in DOC_KINDS" :key="k"><option :value="k" x-text="label" :selected="d.kind===k"></option></template></select>
              <button type="button" class="btn sm ghost" @click="openDocs(form.attachments, i)">View</button>
              <button type="button" class="btn sm ghost" @click="removeDoc(i)">Remove</button></li>
          </template>
        </ul></div>
      <div class="form-actions">
        <button type="button" class="btn ghost" @click="view='dashboard'">Cancel</button>
        <button type="button" class="btn ghost" @click="saveForm(false)">Save draft</button>
        <button type="submit" class="btn primary" :disabled="!isDirector" :title="isDirector ? '' : 'Only the Extension Director can submit'">Submit to Campus Director</button>
      </div>
      <p class="hint right" x-show="!isDirector">You're signed in as the assistant. Save a draft and the Extension Director will submit it.</p>
    </form>

    <aside class="panel checks" aria-live="polite">
      <h2>Checks</h2>
      <div class="check-block">
        <h3>Similar work</h3>
        <p class="muted" x-show="!formChecks.hasText || !form.barangays.length">Add a description and at least one barangay to check every campus for similar work.</p>
        <p class="ok-line" x-show="formChecks.hasText && form.barangays.length && !formChecks.matches.length">No similar work found nearby.</p>
        <template x-for="m in formChecks.matches" :key="m.code">
          <div class="match" :class="{flagged: m.score >= 70}">
            <div class="match-top"><span class="flag" :class="m.score >= 70 ? 'bad' : 'muted'" x-text="m.score >= 70 ? 'Possible overlap' : 'Related'"></span><b x-text="m.score + '%'"></b></div>
            <p class="match-title" x-text="m.title"></p>
            <p class="muted" x-text="m.campus + ', ' + m.barangays.join(', ') + ', ' + fmtDate(m.start) + ' to ' + fmtDate(m.end)"></p>
            <p class="muted" x-show="m.terms.length">Shared: <template x-for="t in m.terms" :key="t"><mark x-text="t"></mark></template></p>
          </div>
        </template>
        <p class="hint" x-show="formChecks.matches.some(m => m.score >= 70)">You can still submit. The Campus Director will see this warning and decide.</p>
      </div>
      <div class="check-block">
        <h3>Budget</h3>
        <p class="muted" x-show="!formChecks.budget">Set a start date to see the budget impact.</p>
        <template x-if="formChecks.budget">
          <div>
            <p class="muted" x-show="!formChecks.budget.sameYear" x-text="'This is charged to the ' + formChecks.budget.year + ' budget, which is not set up in the prototype.'"></p>
            <template x-if="formChecks.budget.sameYear">
              <div>
                <p><span x-text="peso(formChecks.budget.remaining)"></span> remaining in <span x-text="formChecks.budget.year"></span>.</p>
                <p class="ok-line" x-show="!formChecks.budget.over && form.budget" x-text="peso(formChecks.budget.after) + ' left after this request.'"></p>
                <p class="bad-line" x-show="formChecks.budget.over" x-text="'This is ' + peso(formChecks.budget.by) + ' over the remaining budget. You can still submit.'"></p>
              </div>
            </template>
          </div>
        </template>
      </div>
      <div class="check-block">
        <h3>Documents</h3>
        <p class="ok-line" x-show="!missingDocs(form).length">Barangay letter and project proposal attached.</p>
        <p class="bad-line" x-show="missingDocs(form).length" x-text="'Not attached yet: ' + missingDocs(form).join(', ') + '. You can still submit, but the approvers will see that it is missing.'"></p>
      </div>
      <div class="check-block">
        <h3>Approval route</h3>
        <ol class="route"><li>You submit</li><li>Campus Director approves</li><li>University President gives final approval</li></ol>
      </div>
    </aside>
  </div>
</section>

{{-- ========== Accomplishment reports ========== --}}
<section x-show="view==='reports'">
  <div class="page-head"><div><h1>Accomplishment reports</h1>
    <p>Write the report when a service ends: actual dates, budget used, people reached, what changed, and photos. Submitted reports move the service to the archive.</p></div></div>

  <div class="grid-2">
    <article class="panel">
      <header class="panel-head"><h2>Due now</h2></header>
      <template x-if="!reportsDue.length"><p class="empty">No overdue reports. Services that have ended without a report show up here.</p></template>
      <ul class="rows">
        <template x-for="r in reportsDue" :key="r.id">
          <li class="row-req">
            <button type="button" class="row-main" @click="open(r.id)">
              <b x-text="r.title"></b>
              <span class="when t-bad" x-text="'Ended ' + fmtDate(r.end) + ', ' + (r.report ? 'draft saved' : 'not started')"></span>
            </button>
            <div class="row-actions"><button type="button" class="btn sm primary" @click="askReport(r)" x-text="r.report ? 'Continue draft' : 'Write report'"></button></div>
          </li>
        </template>
      </ul>
    </article>

    <article class="panel">
      <header class="panel-head"><h2>Ending in the next 3 weeks</h2></header>
      <template x-if="!reportsSoon.length"><p class="empty">No services end in the next three weeks.</p></template>
      <ul class="rows">
        <template x-for="r in reportsSoon" :key="r.id">
          <li class="row-req">
            <button type="button" class="row-main" @click="open(r.id)">
              <b x-text="r.title"></b>
              <span class="when t-muted" x-text="'Ends ' + fmtDate(r.end) + (r.report ? ', draft saved' : '')"></span>
            </button>
            <div class="row-actions"><button type="button" class="btn sm ghost" @click="askReport(r)" x-text="r.report ? 'Continue draft' : 'Start report'"></button></div>
          </li>
        </template>
      </ul>
    </article>
  </div>

  <article class="panel flush">
    <header class="panel-head pad"><h2>Submitted reports</h2><p class="sub" x-text="submittedReports.length + (submittedReports.length === 1 ? ' report' : ' reports')"></p></header>
    <template x-if="!submittedReports.length"><p class="empty">No reports submitted yet.</p></template>
    <template x-for="r in submittedReports" :key="r.id">
      <button type="button" class="arch has-thumb" @click="open(r.id)">
        <span class="thumb"><template x-if="photosOf(r).length"><img :src="photosOf(r)[0].src" alt=""></template><small x-show="photosOf(r).length > 1" x-text="'+' + (photosOf(r).length - 1)"></small></span>
        <span><b x-text="r.title"></b><small x-text="r.code + ', submitted ' + submittedOn(r) + ', ' + photosOf(r).length + (photosOf(r).length === 1 ? ' photo' : ' photos')"></small>
          <small class="narr" x-text="r.report.narrative"></small></span>
        <span class="arch-nums"><b x-text="r.report.actualBeneficiaries"></b><small>reached</small></span>
        <span class="arch-nums"><b x-text="peso(r.report.actualBudget)"></b><small>spent</small></span>
      </button>
    </template>
  </article>
</section>

@include('prototype.partials.map')

@include('prototype.partials.archive')

@include('prototype.partials.notifications', ['notifIntro' => 'Requests, approvals, overlaps and deadlines for the extension office.'])
</main>

@include('prototype.partials.drawer')

@include('prototype.partials.modals')
</body>
</html>
