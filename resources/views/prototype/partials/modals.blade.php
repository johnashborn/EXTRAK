{{-- ========== Modals ========== --}}
<div class="modal-wrap" x-show="modal" x-transition.opacity @click.self="modal=null">
  @if ($role === 'ed')
  <template x-if="modal && modal.type==='report'">
    <div class="modal wide" role="dialog" aria-modal="true" aria-labelledby="r-title">
      <h2 id="r-title">Accomplishment report</h2>
      <p class="muted" x-text="byId(modal.id).title"></p>
      <div class="two">
        <div class="field"><label for="r-s">Actual start</label><input id="r-s" type="date" x-model="modal.actualStart"></div>
        <div class="field"><label for="r-e">Actual end</label><input id="r-e" type="date" x-model="modal.actualEnd"></div>
        <div class="field"><label for="r-b">Actual budget used (₱)</label><input id="r-b" type="number" min="0" x-model="modal.actualBudget"></div>
        <div class="field"><label for="r-n">People reached</label><input id="r-n" type="number" min="0" x-model="modal.actualBeneficiaries"></div>
      </div>
      <div class="field"><label for="r-t">What was done and what changed</label><textarea id="r-t" rows="4" x-model="modal.narrative" placeholder="e.g. 34 women completed the training. 12 now supply the town's pasalubong center."></textarea></div>
      <div class="field"><label>Photos and attendance sheets</label>
        <label class="drop"><input type="file" accept="image/*" multiple class="sr" @change="addPhotos($event)">
          <svg viewBox="0 0 24 24"><path d="M12 15V4M7.5 8.5 12 4l4.5 4.5M5 15v3.5a1.5 1.5 0 0 0 1.5 1.5h11a1.5 1.5 0 0 0 1.5-1.5V15"/></svg>
          <span x-text="modal.busy ? 'Uploading photos…' : 'Add photos (JPG, PNG or WebP, up to 20 MB each)'"></span></label>
        <ul class="thumbs" x-show="modal.photos.length">
          <template x-for="(p, i) in modal.photos" :key="i">
            <li><img :src="p.src" :alt="p.name"><button type="button" @click="removePhoto(i)" :aria-label="'Remove ' + p.name">&times;</button></li>
          </template>
        </ul></div>
      <p class="err" x-show="modal.error" x-text="modal.error"></p>
      <div class="form-actions">
        <button type="button" class="btn ghost" @click="modal=null">Cancel</button>
        <button type="button" class="btn ghost" @click="saveReport(false)">Save draft</button>
        <button type="button" class="btn primary" @click="saveReport(true)" :disabled="!isDirector" :title="isDirector ? '' : 'Only the Extension Director can submit'">Submit report</button>
      </div>
    </div>
  </template>
  @endif
  @if ($role !== 'ed')
  <template x-if="modal && modal.type==='decide'">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="m-title">
      <h2 id="m-title" x-text="{approve: role === 'pres' ? 'Give final approval?' : 'Approve this request?', return:'Return to the Extension Director', decline:'Decline this request?'}[modal.kind]"></h2>
      <p class="muted" x-text="byId(modal.id).title"></p>
      <p class="muted" x-show="role === 'pres'" x-text="campusName(byId(modal.id)) + ', ' + peso(byId(modal.id).budget)"></p>
      <p x-show="modal.kind==='approve'" x-text="role === 'pres' ? 'This is the last approval. The extension office can then start the service.' : 'It goes to the University President for final approval.'"></p>
      <p x-show="modal.kind==='decline'" x-text="role === 'pres' ? 'The request is closed and will not go ahead.' : 'The request is closed and will not go to the President.'"></p>
      <fieldset class="field" x-show="modal.resolution !== null && modal.kind==='approve'">
        <legend>EXTRACK flagged a possible overlap. How should it be handled?</legend>
        <label class="radio"><input type="radio" value="allow" x-model="modal.resolution"> Allow both. The services are different enough.</label>
        <label class="radio"><input type="radio" value="coordinate" x-model="modal.resolution"> Approve, and the campuses will coordinate.</label>
        <p class="hint">To ask for changes in place, dates or scope, return the request instead.</p>
      </fieldset>
      <div class="field"><label for="m-remark" x-text="modal.kind==='approve' ? 'Remark (optional)' : 'Remark (required)'"></label>
        <textarea id="m-remark" rows="3" x-model="modal.remark" :placeholder="modal.kind==='approve' ? '' : 'What should the Extension Director change?'"></textarea></div>
      <p class="err" x-show="modal.error" x-text="modal.error"></p>
      <div class="form-actions">
        <button type="button" class="btn ghost" @click="modal=null">Cancel</button>
        <button type="button" class="btn" :class="modal.kind==='decline' ? 'danger-fill' : 'primary'" @click="confirmDecision()" x-text="{approve: role === 'pres' ? 'Give final approval' : 'Approve', return:'Return request', decline:'Decline request'}[modal.kind]"></button>
      </div>
    </div>
  </template>
  @endif
  @if ($role === 'pres')
  <template x-if="modal && modal.type==='split'">
    <div class="modal wide" role="dialog" aria-modal="true" aria-labelledby="sp-title">
      <h2 id="sp-title">Split the total among campuses</h2>
      <p class="muted" x-text="'Fills in the campus amounts from the ' + peso(splitPreview.total) + ' university budget. Nothing is saved until you press Save budgets.'"></p>
      <fieldset class="split-methods">
        <legend>How to split it</legend>
        <template x-for="sp in SPLITS" :key="sp[0]">
          <label class="split-opt" :class="{on: modal.method === sp[0]}">
            <input type="radio" name="split" :value="sp[0]" x-model="modal.method" @change="modal.error=''">
            <span><b x-text="sp[1]"></b><small x-text="sp[2]"></small></span></label>
        </template>
      </fieldset>
      <div class="field"><label for="sp-keep">Keep in the university reserve</label>
        <span class="peso-input"><span aria-hidden="true">₱</span><input id="sp-keep" type="text" inputmode="numeric" x-model="modal.keep" @input="modal.error=''" @blur="if (moneyOk(modal.keep)) modal.keep = Math.round(money(modal.keep)).toLocaleString('en-PH')"></span>
        <p class="hint">Amounts are rounded down to the nearest ₱1,000. Anything left over also stays in the reserve.</p></div>
      <table class="split-table">
        <thead><tr><th>Campus</th><th class="n">Now</th><th class="n">After the split</th></tr></thead>
        <tbody>
          <template x-for="r in splitPreview.rows" :key="r.c">
            <tr><td><i :style="'background:' + r.color"></i><span x-text="r.c"></span></td><td class="n" x-text="peso(r.now)"></td>
              <td class="n"><b x-text="peso(r.amount)"></b><small :class="r.amount >= r.now ? 'up' : 'down'" x-show="r.amount !== r.now" x-text="(r.amount > r.now ? '+' : '−') + peso(Math.abs(r.amount - r.now))"></small></td></tr>
          </template>
        </tbody>
        <tfoot><tr><td>University reserve</td><td></td><td class="n"><b x-text="peso(splitPreview.reserve)"></b></td></tr></tfoot>
      </table>
      <p class="err" x-show="modal.error" x-text="modal.error"></p>
      <div class="form-actions">
        <button type="button" class="btn ghost" @click="modal=null">Cancel</button>
        <button type="button" class="btn primary" @click="applySplit()">Fill in these amounts</button>
      </div>
    </div>
  </template>
  <template x-if="modal && modal.type==='confirmBudgets'">
    <div class="modal wide" role="dialog" aria-modal="true" aria-labelledby="cb-title">
      <h2 id="cb-title" x-text="'Save these ' + db.year + ' budgets?'"></h2>
      <p class="muted">Check the changes below. Once saved, campuses see their new budgets straight away and over-budget warnings use the new figures.</p>
      <table class="split-table confirm-table">
        <thead><tr><th>Budget</th><th class="n">Saved now</th><th class="n">New</th></tr></thead>
        <tbody>
          <tr x-show="budgetSummary.totalChanged" class="uni-row">
            <td><i class="uni"></i><span>University extension budget</span></td>
            <td class="n" x-text="peso(budgetSummary.saved)"></td>
            <td class="n"><b x-text="peso(budgetSummary.total)"></b><small :class="budgetSummary.total >= budgetSummary.saved ? 'up' : 'down'" x-text="(budgetSummary.total > budgetSummary.saved ? '+' : '−') + peso(Math.abs(budgetSummary.total - budgetSummary.saved))"></small></td></tr>
          <template x-for="x in budgetCards.filter(x => x.changed)" :key="x.c">
            <tr><td><i :style="'background:' + x.color"></i><span x-text="x.c + ' Campus'"></span></td>
              <td class="n" x-text="peso(x.b.allocation)"></td>
              <td class="n"><b x-text="peso(x.amount)"></b><small :class="x.amount >= x.b.allocation ? 'up' : 'down'" x-text="(x.amount > x.b.allocation ? '+' : '−') + peso(Math.abs(x.amount - x.b.allocation))"></small></td></tr>
          </template>
        </tbody>
        <tfoot><tr><td>University reserve after saving</td><td class="n" x-text="peso(savedReserve)"></td><td class="n"><b :class="{neg: budgetSummary.reserve < 0}" x-text="peso(budgetSummary.reserve)"></b></td></tr></tfoot>
      </table>
      <div class="callout warn" x-show="budgetSummary.reserve < 0 || budgetCards.some(x => x.changed && x.below)">
        <b>Please check</b>
        <p x-show="budgetSummary.reserve < 0" x-text="'The campus budgets add up to ' + peso(-budgetSummary.reserve) + ' more than the university extension budget.'"></p>
        <template x-for="x in budgetCards.filter(x => x.changed && x.below)" :key="x.c">
          <p x-text="x.c + ' Campus has already used ' + peso(x.used) + '. Its new budget is ' + peso(x.used - x.amount) + ' short, so it will show as over budget.'"></p>
        </template>
      </div>
      <p class="hint" x-text="budgetCards.some(x => x.changed && x.c === 'Balilihan') ? 'The Balilihan Campus Extension Director and Campus Director will be notified. Every change is recorded in the budget history.' : 'Every change is recorded in the budget history.'"></p>
      <div class="form-actions">
        <button type="button" class="btn ghost" x-init="$nextTick(() => $el.focus())" @click="modal=null">Go back and edit</button>
        <button type="button" class="btn primary" @click="saveBudgets()">Yes, save budgets</button>
      </div>
    </div>
  </template>
  <template x-if="modal && modal.type==='topup'">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tu-title" :style="'--c:' + CAMPUS_COLORS[modal.campus]">
      <h2 id="tu-title" x-text="'Top up ' + modal.campus + ' Campus'"></h2>
      <div class="topup-figs">
        <div><span>Campus budget now</span><b x-text="peso(db.allocations[modal.campus])"></b></div>
        <div><span>University reserve</span><b x-text="peso(savedReserve)"></b></div>
      </div>
      <div class="field"><label for="tu-amount">Amount to add from the reserve</label>
        <span class="peso-input"><span aria-hidden="true">₱</span><input id="tu-amount" type="text" inputmode="numeric" x-model="modal.amount" @input="modal.error=''" @blur="if (moneyOk(modal.amount)) modal.amount = Math.round(money(modal.amount)).toLocaleString('en-PH')" placeholder="0"></span>
        <p class="hint" x-show="moneyOk(modal.amount) && money(modal.amount) > 0" x-text="'The campus budget becomes ' + peso((db.allocations[modal.campus] || 0) + Math.round(money(modal.amount))) + ', and ' + peso(savedReserve - Math.round(money(modal.amount))) + ' stays in the reserve.'"></p></div>
      <div class="field"><label for="tu-reason">Reason (required)</label>
        <textarea id="tu-reason" rows="3" x-model="modal.reason" @input="modal.error=''" placeholder="e.g. To cover BAL-2026-023, which is over the campus budget"></textarea></div>
      <p class="err" x-show="modal.error" x-text="modal.error"></p>
      <div class="form-actions">
        <button type="button" class="btn ghost" @click="modal=null">Cancel</button>
        <button type="button" class="btn primary" @click="confirmTopUp()">Add to the campus budget</button>
      </div>
    </div>
  </template>
  @endif
</div>

{{-- photo viewer, for both roles --}}
<div class="lightbox" x-show="lightbox" x-transition.opacity @click.self="lightbox=null" role="dialog" aria-modal="true" aria-label="Photo">
  <template x-if="lightbox">
    <figure>
      <img :src="lightbox.photos[lightbox.i].src" :alt="lightbox.photos[lightbox.i].name">
      <figcaption><span x-text="lightbox.photos[lightbox.i].name"></span><span x-text="(lightbox.i + 1) + ' of ' + lightbox.photos.length"></span></figcaption>
      <button type="button" class="lb-close" @click="lightbox=null" aria-label="Close">&times;</button>
      <button type="button" class="lb-nav prev" x-show="lightbox.photos.length > 1" @click="stepPhoto(-1)" aria-label="Previous photo">&lsaquo;</button>
      <button type="button" class="lb-nav next" x-show="lightbox.photos.length > 1" @click="stepPhoto(1)" aria-label="Next photo">&rsaquo;</button>
    </figure>
  </template>
</div>
{{-- document viewer: PDF, photos, Word and Excel without downloading --}}
<div class="docview-wrap" x-show="docView" x-transition.opacity @click.self="closeDocs()">
  <div class="docview" role="dialog" aria-modal="true" aria-labelledby="dv-title">
    <header class="docview-head">
      <span class="doc-ico" aria-hidden="true" x-text="docCurrent ? docExt(docCurrent) : ''"></span>
      <div class="doc-meta">
        <b id="dv-title" x-text="docCurrent ? docCurrent.name : ''"></b>
        <small x-text="docCurrent ? (DOC_KINDS[docCurrent.kind] || 'Document') + ', ' + fmtSize(docCurrent.size) + (docCurrent.sample ? ', sample file' : '') : ''"></small>
      </div>
      <div class="docview-actions">
        <template x-if="docView && docView.docs.length > 1"><div class="docview-pager">
          <button type="button" class="icon-btn" @click="stepDoc(-1)" aria-label="Previous document"><svg viewBox="0 0 24 24"><path d="m15 6-6 6 6 6"/></svg></button>
          <span x-text="(docView.i + 1) + ' of ' + docView.docs.length"></span>
          <button type="button" class="icon-btn" @click="stepDoc(1)" aria-label="Next document"><svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></button>
        </div></template>
        <button type="button" class="btn sm ghost" @click="downloadDoc(docCurrent)"><svg viewBox="0 0 24 24"><path d="M12 4v11M7.5 10.5 12 15l4.5-4.5M5 19h14"/></svg>Download</button>
        <button type="button" class="icon-btn docview-close" @click="closeDocs()" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
    </header>
    <div class="docview-body">
      <p class="docview-state" x-show="docView && docView.status === 'loading'">Loading preview…</p>
      <div class="docview-state" x-show="docView && (docView.status === 'unsupported' || docView.status === 'error')">
        <p x-text="docView && docView.status === 'error' ? docView.error : 'This type of file cannot be shown in the browser. Old .doc Word files are one example.'"></p>
        <button type="button" class="btn primary" @click="downloadDoc(docCurrent)">Download to open it</button>
      </div>
      <template x-if="docView && docView.status === 'ready' && docView.format === 'image'">
        <img class="docview-img" :src="docCurrent.src" :alt="docCurrent.name">
      </template>
      <template x-if="docView && docView.status === 'ready' && docView.format === 'pdf'">
        <iframe class="docview-pdf" :src="docView.pdfUrl" :title="docCurrent.name"></iframe>
      </template>
      <div class="docview-docx" x-ref="docHost" x-show="docView && docView.format === 'docx' && docView.status === 'ready'"></div>
      <template x-if="docView && docView.status === 'ready' && docView.format === 'sheet'">
        <div class="docview-sheet">
          <div class="tabs" role="tablist" x-show="docView?.sheets.length > 1">
            <template x-for="(s, i) in (docView?.sheets || [])" :key="i">
              <button type="button" role="tab" :aria-selected="docView?.sheet === i" @click="docView.sheet = i" x-text="s.name"></button>
            </template>
          </div>
          <div class="sheet-table" x-html="docView?.sheets[docView.sheet]?.html || ''"></div>
        </div>
      </template>
    </div>
  </div>
</div>

{{-- confirmation after the main actions --}}
<div class="alert-wrap" x-show="alert" x-transition.opacity @click.self="alert=null">
  <template x-if="alert">
    <div class="alert-card" :class="'a-' + alert.tone" role="alertdialog" aria-modal="true" aria-labelledby="al-title" aria-describedby="al-msg"
      x-init="$nextTick(() => $el.querySelector('.alert-done').focus())">
      <span class="alert-ico" aria-hidden="true">
        <svg x-show="alert.tone === 'ok'" viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
        <svg x-show="alert.tone === 'warn'" viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
        <svg x-show="alert.tone === 'bad'" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </span>
      <h2 id="al-title" x-text="alert.title"></h2>
      <p id="al-msg" x-text="alert.message"></p>
      <ul class="alert-notes" x-show="alert.notes.length"><template x-for="(n, i) in alert.notes" :key="i"><li x-text="n"></li></template></ul>
      <p class="alert-next" x-show="alert.next" x-text="alert.next"></p>
      <div class="alert-actions">
        <button type="button" class="btn ghost" x-show="alert.ref" @click="alertView()">View request</button>
        <button type="button" class="btn primary alert-done" @click="alert=null">Done</button>
      </div>
    </div>
  </template>
</div>
<div class="toast" x-show="toast" x-transition :class="toast && 't-'+toast.tone" role="status"><span x-text="toast && toast.msg"></span></div>
