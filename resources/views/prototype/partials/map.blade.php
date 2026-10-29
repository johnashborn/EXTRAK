{{-- ========== Extension map ========== --}}
<section x-show="view==='map'">
  <div class="page-head"><div><h1>Extension map</h1>
    <p>Barangays that have received extension services from any BISU campus. Select a pin to see what was done there.
      @if ($role === 'pres')
        Use it to see which barangays have been reached and where services are concentrated across campuses.
      @else
        Use it to see where help has gone and to borrow ideas from other campuses.
      @endif
    </p></div></div>

  <div class="map-filters">
    <select x-model="mapFilter.campus" aria-label="Campus"><option value="">All campuses</option><template x-for="c in CAMPUSES" :key="c"><option :value="c" x-text="c + ' Campus'" :selected="mapFilter.campus===c"></option></template></select>
    <select x-model="mapFilter.category" aria-label="Category"><option value="">All categories</option><template x-for="c in CATEGORIES" :key="c"><option :value="c" x-text="c" :selected="mapFilter.category===c"></option></template></select>
    <label class="check"><input type="checkbox" x-model="mapFilter.ongoing"> Ongoing</label>
    <label class="check"><input type="checkbox" x-model="mapFilter.completed"> Completed</label>
    <span class="sub push" x-text="mapPlaces.length + (mapPlaces.length === 1 ? ' barangay, ' : ' barangays, ') + mapServices.length + (mapServices.length === 1 ? ' service' : ' services')"></span>
  </div>

  <div class="map-layout">
    <div class="map-canvas" x-ref="map" x-effect="if (view === 'map') $nextTick(() => initMap($refs.map))" role="application" aria-label="Map of Bohol with barangays that received extension services"></div>

    <aside class="panel map-side" aria-live="polite">
      <template x-if="!mapPlace">
        <div class="map-intro">
          <h2>Select a barangay</h2>
          <p class="muted">Each pin is a barangay. The number is how many services it received. A grey pin means more than one campus worked there.</p>
          <ul class="map-key">
            <template x-for="c in CAMPUSES" :key="c"><li><i :style="'background:' + campusColor(c)"></i><span x-text="c + ' Campus'"></span></li></template>
            <li><i style="background:#47445F"></i><span>More than one campus</span></li>
          </ul>
          <p class="hint">Pins are placed near each town centre. Exact barangay positions come with the real system.</p>
        </div>
      </template>

      <template x-if="mapPlace">
        <div class="map-detail">
          <header class="map-detail-head">
            <div><h2 x-text="mapPlace.name"></h2><p class="sub" x-text="mapPlace.services.length + (mapPlace.services.length === 1 ? ' extension service' : ' extension services')"></p></div>
            <button type="button" class="icon-btn" @click="mapSel=null" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
          </header>

          <ul class="map-pick" x-show="mapPlace.services.length > 1" aria-label="Services in this barangay">
            <template x-for="(s, i) in mapPlace.services" :key="s.id">
              <li><button type="button" :aria-pressed="mapSel.i === i" @click="mapSel.i = i">
                <i :style="'background:' + campusColor(s.campus || 'Balilihan')"></i>
                <span><b x-text="s.title"></b><small x-text="campusName(s) + ', ' + s.start.slice(0, 4)"></small></span></button></li>
            </template>
          </ul>

          <template x-if="mapService">
            <article class="map-service">
              <span class="ctag" :style="'color:' + campusColor(mapService.campus || 'Balilihan')" x-text="campusName(mapService)"></span>
              <h3 x-text="mapService.title"></h3>
              <p><span class="pill" :class="'t-' + statusTone(mapService)" x-text="STATUS[mapService.status].label"></span></p>
              <p x-text="mapService.description"></p>
              <dl class="details">
                <div><dt>Dates</dt><dd x-text="fmtDate(mapService.start) + ' to ' + fmtDate(mapService.end)"></dd></div>
                <div><dt>Category</dt><dd x-text="mapService.category"></dd></div>
                <div><dt x-text="mapService.report ? 'People reached' : 'Expected beneficiaries'"></dt><dd x-text="(mapService.report ? mapService.report.actualBeneficiaries : mapService.beneficiaries) + (mapService.sectors.length ? ' (' + mapService.sectors.join(', ') + ')' : '')"></dd></div>
                <div x-show="isOwn(mapService)"><dt x-text="mapService.report ? 'Budget used' : 'Budget'"></dt><dd x-text="peso(mapService.report ? mapService.report.actualBudget : mapService.budget)"></dd></div>
                <div class="wide" x-show="mapService.partners"><dt>Partners</dt><dd x-text="mapService.partners"></dd></div>
                <div class="wide" x-show="mapService.report"><dt>What was done</dt><dd x-text="mapService.report && mapService.report.narrative"></dd></div>
              </dl>
              <p class="muted" x-show="!mapService.report">Ongoing. Photos and results come with the accomplishment report.</p>
              <ul class="gallery" x-show="photosOf(mapService).length">
                <template x-for="(p, i) in photosOf(mapService)" :key="i">
                  <li><button type="button" @click="openPhoto(photosOf(mapService), i)" :aria-label="'Open photo: ' + p.name"><img :src="p.src" :alt="p.name"></button><small x-text="p.name"></small></li>
                </template>
              </ul>
              <button type="button" class="btn ghost sm" x-show="isOwn(mapService)" @click="open(mapService.id)">Open full record</button>
              <p class="hint" x-show="!isOwn(mapService)" x-text="'For questions, contact the ' + campusName(mapService) + ' extension office.'"></p>
            </article>
          </template>
        </div>
      </template>
    </aside>
  </div>
</section>
