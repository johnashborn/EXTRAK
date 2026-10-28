{{-- ========== Requests list ========== --}}
<section x-show="view==='requests'">
  <div class="page-head">
    <div><h1>Requests</h1><p>{{ $intro }}</p></div>
    @if ($role === 'ed')
    <button type="button" class="btn primary" @click="newRequest()"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>New request</button>
    @endif
  </div>
  <div class="tabs" role="tablist">
    <template x-for="t in tabs" :key="t[0]">
      <button type="button" role="tab" :aria-selected="filter.tab===t[0]" @click="filter.tab=t[0]"><span x-text="t[1]"></span><small x-text="tabCount(t[0])"></small></button>
    </template>
  </div>
  <div class="panel flush">
    <div class="filters">
      <input type="search" placeholder="Search title, code or barangay" x-model="filter.q" aria-label="Search requests">
      <select x-model="filter.status" aria-label="Status"><option value="">Any status</option><template x-for="(s,k) in STATUS" :key="k"><option :value="k" x-text="s.label"></option></template></select>
      @if ($role === 'pres')
      <select x-model="filter.campus" aria-label="Campus"><option value="">All campuses</option><template x-for="c in CAMPUSES" :key="c"><option :value="c" x-text="c + ' Campus'" :selected="filter.campus===c"></option></template></select>
      @endif
      <select x-model="filter.category" aria-label="Category"><option value="">All categories</option><template x-for="c in CATEGORIES" :key="c"><option :value="c" x-text="c"></option></template></select>
    </div>
    <div class="table">
      <div class="tr th"><span>Request</span><span>Barangay</span><span>Dates</span><span>Budget</span><span>Status</span></div>
      <template x-for="r in filtered" :key="r.id">
        <button type="button" class="tr" @click="open(r.id)">
          <span class="cell-title"><b x-text="r.title"></b><small x-text="role === 'pres' ? r.code + ', ' + campusName(r) : r.code"></small>
            <span class="flags"><span class="flag bad" x-show="r.overlap && !r.overlap.resolution">Overlap</span><span class="flag bad" x-show="r.status==='ongoing' && r.end < TODAY">Report overdue</span></span></span>
          <span><span x-text="r.barangays.join(', ')"></span><small x-text="r.municipality"></small></span>
          <span><span x-text="fmtDate(r.start)"></span><small x-text="'to ' + fmtDate(r.end)"></small></span>
          <span x-text="peso(r.budget)"></span>
          <span><span class="pill" :class="'t-'+statusTone(r)" x-text="statusText(r)"></span></span>
        </button>
      </template>
      <p class="empty" x-show="!filtered.length">No requests match. Clear the search or pick another tab.</p>
    </div>
  </div>
</section>
