{{-- ========== Archive ========== --}}
<section x-show="view==='archive'">
  <div class="page-head"><h1>Archive</h1></div>
  <div class="panel flush">
    <div class="filters" x-show="archive.length">
      <input type="search" placeholder="Search title, code, barangay or report…" x-model="archFilter.q" aria-label="Search the archive">
      <select x-model="archFilter.year" aria-label="Year completed"><option value="">Any year</option><template x-for="y in archiveYears" :key="y"><option :value="y" x-text="y"></option></template></select>
      @if ($role === 'pres')
      <select x-model="archFilter.campus" aria-label="Campus"><option value="">All campuses</option><template x-for="c in CAMPUSES" :key="c"><option :value="c" x-text="c + ' Campus'"></option></template></select>
      @endif
      <select x-model="archFilter.category" aria-label="Category"><option value="">All categories</option><template x-for="c in CATEGORIES" :key="c"><option :value="c" x-text="c"></option></template></select>
      <select x-model="archFilter.sort" aria-label="Sort by"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="reached">Most people reached</option><option value="title">Title A to Z</option></select>
    </div>
    <p class="arch-count" x-show="archive.length" aria-live="polite"><span x-text="archiveFiltered.length === archive.length ? archive.length + (archive.length === 1 ? ' record' : ' records') : archiveFiltered.length + ' of ' + archive.length + ' records'"></span>
      <button type="button" class="link" x-show="archFiltering" @click="clearArchFilter()">Clear filters</button></p>
    <p class="empty" x-show="!archive.length">No completed services yet. A service moves here when its accomplishment report is submitted.</p>
    <p class="empty" x-show="archive.length && !archiveFiltered.length">No records match. Try another year or category, or clear the filters.</p>
    <template x-for="r in archiveFiltered" :key="r.id">
      @if ($role === 'pres')
      <div class="arch-item">
      @endif
      <button type="button" class="arch has-thumb" @click="open(r.id)">
        <span class="thumb"><template x-if="photosOf(r).length"><img :src="photosOf(r)[0].src" alt="" width="72" height="54" loading="lazy"></template><small x-show="photosOf(r).length > 1" x-text="'+' + (photosOf(r).length - 1)"></small></span>
        <span><b x-text="r.title"></b><small x-text="r.code + ' · ' + (role === 'pres' ? campusName(r) + ' · ' : '') + r.barangays.join(', ') + ', ' + r.municipality + ' · ' + fmtDate(r.start) + ' to ' + fmtDate(r.end)"></small>
          <small class="narr" x-show="r.report" x-text="r.report && r.report.narrative"></small></span>
        <span class="arch-nums"><b x-text="r.report ? r.report.actualBeneficiaries : '—'"></b><small>reached</small></span>
        <span class="arch-nums"><b x-text="r.report ? peso(r.report.actualBudget) : '—'"></b><small>spent</small></span>
      </button>
      @if ($role === 'pres')
      <button type="button" class="arch-del" @click="deleteArchived(r)" :aria-label="'Delete ' + r.title + ' from the archive'" title="Delete from archive"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg></button>
      </div>
      @endif
    </template>
  </div>
</section>
