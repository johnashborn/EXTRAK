{{-- ========== Archive ========== --}}
<section x-show="view==='archive'">
  <div class="page-head"><div><h1>Archive</h1><p>Completed services and their accomplishment reports. Open a record to see its photos. These records stay when directors change.</p></div></div>
  <div class="panel flush">
    <template x-for="r in archive" :key="r.id">
      <button type="button" class="arch has-thumb" @click="open(r.id)">
        <span class="thumb"><template x-if="photosOf(r).length"><img :src="photosOf(r)[0].src" alt=""></template><small x-show="photosOf(r).length > 1" x-text="'+' + (photosOf(r).length - 1)"></small></span>
        <span><b x-text="r.title"></b><small x-text="r.code + ' · ' + (role === 'pres' ? campusName(r) + ' · ' : '') + r.barangays.join(', ') + ', ' + r.municipality + ' · ' + fmtDate(r.start) + ' to ' + fmtDate(r.end)"></small>
          <small class="narr" x-show="r.report" x-text="r.report && r.report.narrative"></small></span>
        <span class="arch-nums"><b x-text="r.report ? r.report.actualBeneficiaries : '—'"></b><small>reached</small></span>
        <span class="arch-nums"><b x-text="r.report ? peso(r.report.actualBudget) : '—'"></b><small>spent</small></span>
      </button>
    </template>
  </div>
</section>
