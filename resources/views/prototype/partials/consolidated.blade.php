{{-- ========== Consolidated report: a preview of what the archive filters show, saved as PDF or for Excel ========== --}}
<div class="report-wrap" x-show="reportOpen" x-transition.opacity @click.self="reportOpen=false">
  <div class="report-view" role="dialog" aria-modal="true" aria-labelledby="rp-title">
    <header class="report-bar">
      <b x-text="activity ? 'Export accomplishment report' : 'Export report'"></b>
      <div class="report-actions">
        <button type="button" class="btn sm primary" data-autofocus @click="printConsolidated()"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V3h10v5M7 17H4v-7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7h-3M7 14h10v7H7z"/></svg>Save as PDF</button>
        <button type="button" class="btn sm ghost" @click="downloadConsolidated()"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11M7.5 10.5 12 15l4.5-4.5M5 19h14"/></svg>Download for Excel</button>
        <button type="button" class="icon-btn" @click="reportOpen=false" aria-label="Close"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
    </header>
    <div class="report-body">
      <template x-if="consolidated">
        <article class="report-paper">
          <header class="rp-head">
            <img src="{{ asset('images/logo.png') }}" alt="" width="64" height="64">
            <div>
              <p class="rp-school">Bohol Island State University</p>
              <p class="rp-office" x-text="consolidated.scope + ' · Extension Services'"></p>
            </div>
          </header>
          <h2 class="rp-title" id="rp-title">Consolidated Accomplishment Report</h2>
          <dl class="rp-meta">
            <template x-for="m in consolidated.meta" :key="m[0]">
              <div><dt x-text="m[0]"></dt><dd x-text="m[1]"></dd></div>
            </template>
          </dl>

          <div class="rp-totals">
            <div><b x-text="consolidated.totals.services"></b><span>Services completed</span></div>
            <div><b x-text="consolidated.totals.reached.toLocaleString('en-PH')"></b><span>People reached</span></div>
            <div><b x-text="consolidated.totals.barangays"></b><span>Barangays served</span></div>
            <div><b x-text="peso(consolidated.totals.spent)"></b><span>Amount spent</span></div>
          </div>

          <template x-if="consolidated.byCampus.length > 1">
            <section>
              <h3>Summary by campus</h3>
              <div class="rp-scroll"><table class="rp-table">
                <thead><tr><th>Campus</th><th class="n">Services</th><th class="n">People reached</th><th class="n">Amount spent</th></tr></thead>
                <tbody><template x-for="g in consolidated.byCampus" :key="g.name"><tr><td x-text="g.name + ' Campus'"></td><td class="n" x-text="g.services"></td><td class="n" x-text="g.reached.toLocaleString('en-PH')"></td><td class="n" x-text="peso(g.spent)"></td></tr></template></tbody>
              </table></div>
            </section>
          </template>

          <section>
            <h3>Summary by category</h3>
            <div class="rp-scroll"><table class="rp-table">
              <thead><tr><th>Category</th><th class="n">Services</th><th class="n">People reached</th><th class="n">Amount spent</th></tr></thead>
              <tbody><template x-for="g in consolidated.byCategory" :key="g.name"><tr><td x-text="g.name"></td><td class="n" x-text="g.services"></td><td class="n" x-text="g.reached.toLocaleString('en-PH')"></td><td class="n" x-text="peso(g.spent)"></td></tr></template></tbody>
            </table></div>
          </section>

          <section>
            <h3>Services completed</h3>
            <div class="rp-scroll"><table class="rp-table rp-services">
              <thead><tr><th>Code</th><th>Service</th><th>Dates</th><th class="n">Reached</th><th class="n">Spent</th></tr></thead>
              <tbody><template x-for="s in consolidated.rows" :key="s.id"><tr>
                <td class="rp-code" x-text="s.code"></td>
                <td><b x-text="s.title"></b><small x-text="(consolidated.showCampus ? s.campus + ' Campus · ' : '') + s.place"></small><small x-text="s.category"></small><small class="rp-outcome" x-show="s.outcome" x-text="s.outcome"></small></td>
                <td class="rp-dates"><span x-text="fmtDate(s.start)"></span><small x-text="'to ' + fmtDate(s.end)"></small></td>
                <td class="n" x-text="s.reached.toLocaleString('en-PH')"></td>
                <td class="n" x-text="peso(s.spent)"></td>
              </tr></template></tbody>
              <tfoot><tr><td colspan="3">Total</td><td class="n" x-text="consolidated.totals.reached.toLocaleString('en-PH')"></td><td class="n" x-text="peso(consolidated.totals.spent)"></td></tr></tfoot>
            </table></div>
          </section>

          <div class="rp-signs">
            <template x-for="s in consolidated.signers" :key="s.label">
              <div><small x-text="s.label"></small><b x-text="s.name"></b><span x-text="s.title"></span></div>
            </template>
          </div>
          <p class="rp-foot" x-text="'Generated by EXTRACK on ' + todayLabel()"></p>
        </article>
      </template>

      {{-- one activity: a sample layout until the extension office shares its own form --}}
      <template x-if="activity">
        <article class="report-paper">
          <header class="rp-head">
            <img src="{{ asset('images/logo.png') }}" alt="" width="64" height="64">
            <div>
              <p class="rp-school">Bohol Island State University</p>
              <p class="rp-office" x-text="activity.campus + ' · Extension Services'"></p>
            </div>
          </header>
          <div class="rp-act-head">
            <h2 class="rp-title" id="rp-title">Accomplishment Report</h2>
            <p class="rp-act" x-text="activity.title"></p>
          </div>
          <dl class="rp-meta">
            <template x-for="m in activity.meta" :key="m[0]">
              <div><dt x-text="m[0]"></dt><dd x-text="m[1]"></dd></div>
            </template>
          </dl>

          <section>
            <h3>Planned and actual</h3>
            <div class="rp-scroll"><table class="rp-table rp-compare">
              <thead><tr><th></th><th>Planned</th><th>Actual</th></tr></thead>
              <tbody><template x-for="c in activity.compare" :key="c[0]"><tr><td x-text="c[0]"></td><td x-text="c[1]"></td><td><b x-text="c[2]"></b></td></tr></template></tbody>
            </table></div>
          </section>

          <section x-show="activity.description">
            <h3>Description of the activity</h3>
            <p class="rp-text" x-text="activity.description"></p>
          </section>

          <section>
            <h3>What was done</h3>
            <p class="rp-text" x-text="activity.narrative || 'No narrative was written.'"></p>
          </section>

          <section x-show="activity.photos.length">
            <h3>Photo documentation</h3>
            <ul class="rp-photos">
              <template x-for="(ph, i) in activity.photos" :key="i">
                <li><img :src="ph.src" :alt="ph.name" width="220" height="165"><small x-text="ph.name"></small></li>
              </template>
            </ul>
            <p class="rp-more" x-show="activity.morePhotos" x-text="'And ' + activity.morePhotos + ' more in EXTRACK.'"></p>
          </section>

          <div class="rp-signs">
            <template x-for="s in activity.signers" :key="s.label">
              <div><small x-text="s.label"></small><b x-text="s.name"></b><span x-text="s.title"></span></div>
            </template>
          </div>
          <p class="rp-foot" x-text="'Generated by EXTRACK on ' + todayLabel()"></p>
        </article>
      </template>
    </div>
  </div>
</div>
