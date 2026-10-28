  <article class="panel">
    <header class="panel-head"><h2>Barangays reached in Balilihan</h2><p class="sub"><b x-text="coverage.reached"></b> of <span x-text="coverage.total"></span> barangays have had an approved service</p></header>
    <div class="coverage">
      <ul class="cov-bars">
        <template x-for="c in coverage.rows" :key="c.name">
          <li><span x-text="c.name"></span><i :style="'--n:' + c.n"></i><b x-text="c.n"></b></li>
        </template>
      </ul>
      <div class="cov-gap">
        <p>Not yet reached</p>
        <div class="chips"><template x-for="b in coverage.notReached" :key="b"><span x-text="b"></span></template></div>
      </div>
    </div>
  </article>
