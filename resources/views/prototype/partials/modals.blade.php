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
          <span x-text="modal.busy ? 'Adding photos…' : 'Add photos (JPG or PNG). They are kept in this browser only.'"></span></label>
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
<div class="toast" x-show="toast" x-transition :class="toast && 't-'+toast.tone" role="status"><span x-text="toast && toast.msg"></span></div>
