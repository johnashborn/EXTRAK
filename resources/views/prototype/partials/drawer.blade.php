{{-- ========== Request detail drawer ========== --}}
<div class="scrim" x-show="selected" x-transition.opacity @click="selectedId=null"></div>
<aside class="drawer" x-show="selected" x-transition:enter-start="off" x-transition:leave-end="off" role="dialog" aria-modal="true" aria-labelledby="d-title">
  <template x-if="selected">
    <div class="drawer-in" x-data="{ r: selected }" x-effect="if (selected) r = selected">
      <header class="d-head">
        <div><small x-text="r.code + ' · ' + campusName(r)"></small><h2 id="d-title" x-text="r.title"></h2>
          <span class="pill" :class="'t-'+statusTone(r)" x-text="statusText(r)"></span></div>
        <button type="button" class="icon-btn" @click="selectedId=null" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </header>

      <ol class="stepper"><template x-for="s in steps(r)" :key="s.label"><li :class="s.state"><i></i><span x-text="s.label"></span></li></template></ol>

      {{-- actions for the current state --}}
      <div class="d-actions">
        @if ($role === 'ed')
        <p class="muted" x-show="r.status==='submitted'">Waiting for the Campus Director to approve, return or decline it.</p>
        <p class="muted" x-show="r.status==='for_president'">Approved by the Campus Director. Waiting for the University President's final approval.</p>
        <template x-if="r.status==='draft'"><div class="act-row">
          <button type="button" class="btn primary" @click="submitDraft(r)" :disabled="!isDirector">Submit to Campus Director</button>
          <button type="button" class="btn ghost" @click="editRequest(r)">Edit</button>
          <button type="button" class="btn ghost danger" @click="deleteDraft(r)">Delete draft</button></div></template>
        <template x-if="r.status==='returned'"><div class="act-row">
          <button type="button" class="btn primary" @click="editRequest(r)">Edit and resubmit</button></div></template>
        <template x-if="r.status==='approved'"><div class="act-row">
          <button type="button" class="btn primary" @click="startService(r)">Mark as started</button></div></template>
        <template x-if="r.status==='ongoing'"><div class="act-row">
          <button type="button" class="btn primary" @click="askReport(r)" x-text="r.report ? 'Continue accomplishment report' : 'Write accomplishment report'"></button></div></template>
        @elseif ($role === 'cd')
        <template x-if="r.status==='submitted'"><div class="act-row">
          <button type="button" class="btn primary" @click="askDecision(r,'approve')" :disabled="!isDirector">Approve</button>
          <button type="button" class="btn ghost" @click="askDecision(r,'return')" :disabled="!isDirector">Return</button>
          <button type="button" class="btn ghost danger" @click="askDecision(r,'decline')" :disabled="!isDirector">Decline</button></div></template>
        <p class="muted" x-show="r.status==='submitted' && !isDirector">Only the Campus Director can approve, return or decline. You can add remarks.</p>
        <p class="muted" x-show="r.status==='for_president'">You approved this for the campus. Waiting for the University President's final approval.</p>
        <p class="muted" x-show="r.status==='returned'">Returned to the Extension Director to revise and resubmit.</p>
        <p class="muted" x-show="r.status==='approved'">Approved. The extension office will mark it as started.</p>
        <p class="muted" x-show="r.status==='ongoing'" x-text="r.end < TODAY ? 'The service has ended. Waiting for the accomplishment report from the extension office.' : 'Being carried out by the extension office.'"></p>
        @else
        <template x-if="r.status==='for_president'"><div class="act-row">
          <button type="button" class="btn primary" @click="askDecision(r,'approve')">Give final approval</button>
          <button type="button" class="btn ghost" @click="askDecision(r,'return')">Return</button>
          <button type="button" class="btn ghost danger" @click="askDecision(r,'decline')">Decline</button></div></template>
        <p class="muted" x-show="r.status==='for_president'" x-text="'Approved for the campus by the Campus Director of ' + campusName(r) + ' ' + waitingSince(r) + (waitingSince(r) === 1 ? ' day' : ' days') + ' ago.'"></p>
        <p class="muted" x-show="r.status==='submitted'" x-text="'Still with the Campus Director of ' + campusName(r) + '. It comes to you after campus approval.'"></p>
        <p class="muted" x-show="r.status==='returned'">Returned to the campus extension office to revise and resubmit.</p>
        <p class="muted" x-show="r.status==='approved'">You gave final approval. The extension office will mark it as started.</p>
        <p class="muted" x-show="r.status==='ongoing'" x-text="r.end < TODAY ? 'The service has ended, but the campus has not submitted its accomplishment report.' : 'Being carried out by the campus extension office.'"></p>
        @endif
      </div>

      <div class="callout warn" x-show="r.status==='returned' || r.status==='declined'">
        <b x-text="r.status==='returned' ? 'Returned by ' + person([...r.events].reverse().find(e=>e.to==='returned')?.by).name : 'Declined'"></b>
        <p x-text="[...r.events].reverse().find(e=>e.to===r.status)?.remark"></p>
      </div>

      <div class="callout bad" x-show="r.overlap">
        <template x-if="r.overlap"><div>
          <div class="ov-head"><b>Possible overlap, <span x-text="r.overlap.score"></span>% similar</b>
            <span class="pill t-muted" x-show="r.overlap.resolution" x-text="'Campus Director of ' + campusName(r) + ': ' + {allow:'allowed both', coordinate:'campuses will coordinate'}[r.overlap.resolution]"></span></div>
          @if ($role === 'ed')
          <p class="muted" x-show="!r.overlap.resolution">You can still submit. The Campus Director decides how to handle this when reviewing the request.</p>
          @elseif ($role === 'cd')
          <p class="muted" x-show="!r.overlap.resolution">When you approve, choose whether to allow both or have the campuses coordinate. To ask for changes in place, dates or scope, return it instead.</p>
          @else
          <p class="muted" x-show="r.overlap.resolution" x-text="'The Campus Director of ' + campusName(r) + ' made this decision. If the campuses should handle it differently, return the request with a remark.'"></p>
          @endif
          <div class="vs">
            <div><small>This request</small><p x-text="r.title"></p><small x-text="r.barangays.join(', ') + ', ' + fmtDate(r.start) + ' to ' + fmtDate(r.end)"></small></div>
            <div><small x-text="r.overlap.match.campus"></small><p x-text="r.overlap.match.title"></p><small x-text="r.overlap.match.barangays.join(', ') + ', ' + fmtDate(r.overlap.match.start) + ' to ' + fmtDate(r.overlap.match.end)"></small></div>
          </div>
          <p class="muted" x-show="r.overlap.terms.length">Shared: <template x-for="t in r.overlap.terms" :key="t"><mark x-text="t"></mark></template></p>
        </div></template>
      </div>

      <div class="callout warn" x-show="['submitted','for_president'].includes(r.status) && budgetFor(r).over">
        <b x-text="'Over the remaining ' + budgetFor(r).year + ' budget by ' + peso(budgetFor(r).by)"></b>
        <p>The request can still be approved. Balilihan Campus has <span x-text="peso(budgetFor(r).remaining)"></span> left that isn't committed or spent.</p>
      </div>

      <h3 class="d-sec">Details</h3>
      <dl class="details">
        <div class="wide"><dt>Description</dt><dd x-text="r.description"></dd></div>
        <div><dt>Category</dt><dd x-text="r.category"></dd></div>
        <div><dt>Barangay</dt><dd x-text="r.barangays.join(', ') + ', ' + r.municipality"></dd></div>
        <div><dt>Dates</dt><dd x-text="fmtDate(r.start) + ' to ' + fmtDate(r.end)"></dd></div>
        <div><dt>Proposed budget</dt><dd x-text="peso(r.budget)"></dd></div>
        <div><dt>Beneficiaries</dt><dd x-text="r.beneficiaries + (r.sectors.length ? ' (' + r.sectors.join(', ') + ')' : '')"></dd></div>
        <div><dt>Partners</dt><dd x-text="r.partners || 'None'"></dd></div>
        <div><dt>Requested by</dt><dd x-text="person(r.requestedBy).name + (r.encodedBy !== r.requestedBy ? ', encoded by ' + person(r.encodedBy).name : '')"></dd></div>
      </dl>

      <h3 class="d-sec">Supporting documents</h3>
      <div class="callout warn" x-show="missingDocs(r).length && ['draft','submitted','for_president','returned'].includes(r.status)">
        <b>Missing documents</b>
        <p x-text="'Not attached: ' + missingDocs(r).join(', ') + '.'"></p>
      </div>
      <p class="muted" x-show="!docsOf(r).length">No documents attached.</p>
      <ul class="docs" x-show="docsOf(r).length">
        <template x-for="(d, i) in docsOf(r)" :key="d.id">
          <li><span class="doc-ico" x-text="docExt(d)"></span>
            <span class="doc-meta"><b x-text="d.name"></b><small x-text="DOC_KINDS[d.kind] + ', ' + fmtSize(d.size) + (d.sample ? ', sample file' : '')"></small></span>
            <button type="button" class="btn sm ghost" @click="openDocs(docsOf(r), i)">View</button>
            <button type="button" class="btn sm ghost" @click="downloadDoc(d)">Download</button></li>
        </template>
      </ul>

      <template x-if="r.report"><div>
        <h3 class="d-sec">Accomplishment report</h3>
        <dl class="details">
          <div><dt>Actual dates</dt><dd x-text="fmtDate(r.report.actualStart) + ' to ' + fmtDate(r.report.actualEnd)"></dd></div>
          <div><dt>Actual budget</dt><dd x-text="peso(r.report.actualBudget)"></dd></div>
          <div><dt>People reached</dt><dd x-text="r.report.actualBeneficiaries"></dd></div>
          <div class="wide"><dt>What was done</dt><dd x-text="r.report.narrative"></dd></div>
        </dl>
        <h4 class="gallery-title" x-text="'Photos (' + photosOf(r).length + ')'"></h4>
        <p class="muted" x-show="!photosOf(r).length">No photos attached to this report.</p>
        <ul class="gallery" x-show="photosOf(r).length">
          <template x-for="(p, i) in photosOf(r)" :key="i">
            <li><button type="button" @click="openPhoto(photosOf(r), i)" :aria-label="'Open photo: ' + p.name"><img :src="p.src" :alt="p.name"></button><small x-text="p.name"></small></li>
          </template>
        </ul>
      </div></template>

      <h3 class="d-sec">History</h3>
      <ol class="timeline">
        <template x-for="(e,i) in [...r.events].reverse()" :key="i">
          <li><b x-text="person(e.by).name"></b> <span x-text="({draft:'started a draft', submitted: e.from==='returned' ? 'resubmitted it' : 'submitted it', for_president:'approved it for the campus', approved:'gave final approval', returned:'returned it', declined:'declined it', ongoing:'marked it as started', completed:'submitted the accomplishment report', archived:'archived it'})[e.to]"></span>
            <small x-text="fmtDateTime(e.at)"></small><p x-show="e.remark" x-text="e.remark"></p></li>
        </template>
      </ol>

      <h3 class="d-sec">Remarks</h3>
      <ul class="remarks">
        <template x-for="(m,i) in r.remarks" :key="i"><li><span class="avatar sm" x-text="person(m.by).initials"></span><div><b x-text="person(m.by).name"></b> <small x-text="fmtDateTime(m.at)"></small><p x-text="m.body"></p></div></li></template>
        <li x-show="!r.remarks.length" class="muted">No remarks yet.</li>
      </ul>
      <form class="remark-form" @submit.prevent="addRemark(r, $refs.remark)">
        <input x-ref="remark" placeholder="Write a remark" aria-label="Write a remark"><button type="submit" class="btn sm ghost">Post</button>
      </form>

      @if ($role === 'ed')
      <div class="sim" x-show="['submitted','for_president'].includes(r.status)">
        <p><b>Prototype only.</b> Play the next approver's part:</p>
        <div class="act-row">
          <template x-if="r.status==='submitted'"><div class="act-row">
            <button type="button" class="btn sm ghost" @click="simulate(r,'cd','approve')">Campus Director approves</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'cd','return')">Campus Director returns</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'cd','decline')">Campus Director declines</button></div></template>
          <template x-if="r.status==='for_president'"><div class="act-row">
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','approve')">President approves</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','return')">President returns</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','decline')">President declines</button></div></template>
        </div>
      </div>
      @elseif ($role === 'cd')
      <div class="sim" x-show="['for_president','returned'].includes(r.status)">
        <p><b>Prototype only.</b> Play the next person's part:</p>
        <div class="act-row">
          <template x-if="r.status==='for_president'"><div class="act-row">
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','approve')">President approves</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','return')">President returns</button>
            <button type="button" class="btn sm ghost" @click="simulate(r,'pres','decline')">President declines</button></div></template>
          <template x-if="r.status==='returned'"><div class="act-row">
            <button type="button" class="btn sm ghost" @click="simulate(r,'ed','resubmit')">Extension Director resubmits</button></div></template>
        </div>
      </div>
      @endif
    </div>
  </template>
</aside>
