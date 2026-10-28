    <article class="panel">
      <header class="panel-head"><h2>Ongoing and upcoming</h2></header>
      <template x-if="!ongoing.length"><p class="empty">No approved or ongoing services.</p></template>
      <ul class="rows">
        <template x-for="r in ongoing" :key="r.id">
          <li><button type="button" class="row-main" @click="open(r.id)">
            <b x-text="r.title"></b>
            <span class="meta"><span x-text="r.barangays.join(', ')"></span><span x-text="fmtDate(r.start) + ' to ' + fmtDate(r.end)"></span></span>
            <span class="when" :class="'t-'+endsIn(r).tone" x-text="endsIn(r).text"></span>
          </button></li>
        </template>
      </ul>
    </article>
