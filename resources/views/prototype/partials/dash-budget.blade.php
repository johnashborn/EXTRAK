    <article class="panel budget">
      <header class="panel-head"><h2><span x-text="db.year"></span> extension budget</h2></header>
      <p class="big-num" x-text="peso(budget.remaining)"></p>
      <p class="sub">remaining of <span x-text="peso(budget.allocation)"></span></p>
      <div class="bar" role="img" :aria-label="'Spent ' + peso(budget.spent) + ', committed ' + peso(budget.committed) + ', pending ' + peso(budget.pending)">
        <i class="b-spent" :style="'width:' + budget.pct(budget.spent) + '%'"></i>
        <i class="b-committed" :style="'width:' + budget.pct(budget.committed) + '%'"></i>
        <i class="b-pending" :style="'width:' + budget.pct(Math.min(budget.pending, Math.max(budget.remaining,0))) + '%'"></i>
      </div>
      <dl class="legend-list">
        <div><dt><i class="b-spent"></i>Spent</dt><dd x-text="peso(budget.spent)"></dd></div>
        <div><dt><i class="b-committed"></i>Committed</dt><dd x-text="peso(budget.committed)"></dd></div>
        <div><dt><i class="b-pending"></i>Pending approval</dt><dd x-text="peso(budget.pending)"></dd></div>
      </dl>
      <p class="note" x-show="budget.pending > budget.remaining" x-text="'If every pending request is approved, the campus goes ' + peso(budget.pending - budget.remaining) + ' over its budget.'"></p>
    </article>
