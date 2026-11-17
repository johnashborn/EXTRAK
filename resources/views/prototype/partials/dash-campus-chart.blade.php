{{-- ========== Services by campus: one slim column per campus, each segment one service. Waiting requests aren't services yet, so they sit under the campus as a gold tag. ========== --}}
<article class="panel cc" aria-labelledby="cc-title">
  <header class="panel-head">
    <h2 id="cc-title">Services by campus, <span x-text="db.year"></span></h2>
  </header>

  <div class="cc-body">
    <div class="cc-side">
      <p class="cc-say" x-text="campusChart.summary"></p>
      <dl class="cc-key">
        <div><dt><i class="done" aria-hidden="true"></i>Completed</dt><dd x-text="campusChart.counts.done"></dd></div>
        <div><dt><i class="live" aria-hidden="true"></i>Ongoing</dt><dd x-text="campusChart.counts.live"></dd></div>
        <div><dt><i class="avg" aria-hidden="true"></i>Average per campus</dt><dd x-text="campusChart.avgLabel"></dd></div>
        <div><dt><i class="wait" aria-hidden="true"></i>Waiting for you</dt><dd x-text="campusChart.counts.wait"></dd></div>
      </dl>
      <p class="cc-note" x-show="campusChart.max">Each segment is one service. Select one to open it.</p>
    </div>

    <div class="cc-plot" x-show="campusChart.max" x-ref="ccPlot" :style="'--max:' + campusChart.max" @keydown.escape="chartTip.on = false">
      <div class="cc-layer cc-grid" aria-hidden="true">
        <template x-for="t in campusChart.ticks" :key="t">
          <i :class="{ base: t === 0 }" :style="'bottom:' + (t / campusChart.max * 100) + '%'"><span x-text="t"></span></i>
        </template>
      </div>
      <div class="cc-layer cc-avg-layer" aria-hidden="true">
        <div class="cc-avg" :style="'bottom:' + (campusChart.avg / campusChart.max * 100) + '%'"><span>Average <b x-text="campusChart.avgLabel"></b></span></div>
      </div>

      <ul class="cc-rows">
        <template x-for="row in campusChart.rows" :key="row.c">
          <li class="cc-row">
            <div class="cc-track" role="group" :aria-label="row.c + ' Campus: ' + row.services + (row.services === 1 ? ' service' : ' services') + ', ' + row.waiting + ' waiting for you'">
              <template x-for="(u, i) in row.units" :key="u.r.id">
                <button type="button" class="cc-unit" :class="u.cls" :style="'--i:' + i" @click="open(u.r.id)"
                  @mouseenter="showChartTip($event, u)" @mouseleave="chartTip.on = false" @focus="showChartTip($event, u)" @blur="chartTip.on = false"
                  :aria-label="u.r.title + ', ' + statusText(u.r)"></button>
              </template>
            </div>
            <div class="cc-label">
              <button type="button" class="cc-name" @click="openList('all', row.c)" :aria-label="'See all requests from ' + row.c + ' Campus'" x-text="row.c"></button>
              <span class="cc-count"><span x-text="row.services"></span><span class="cc-w" x-text="row.services === 1 ? ' service' : ' services'"></span></span>
              <button type="button" class="pill t-wait cc-pill" x-show="row.waiting" @click="openList('waiting', row.c)" :aria-label="row.waiting + ' from ' + row.c + ' Campus waiting for your approval'"><span x-text="row.waiting"></span><span class="cc-w"> waiting</span></button>
            </div>
          </li>
        </template>
      </ul>

      <div class="cc-tip" role="tooltip" x-show="chartTip.on" :class="chartTip.kind" :style="'left:' + chartTip.x + 'px;top:' + chartTip.y + 'px'">
        <b x-text="chartTip.title"></b>
        <span x-text="chartTip.status"></span>
        <small x-text="chartTip.place"></small>
      </div>
    </div>
  </div>
</article>
