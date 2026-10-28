{{-- ========== Notifications ========== --}}
<section x-show="view==='notifications'">
  <div class="page-head"><div><h1>Notifications</h1><p>{{ $notifIntro }}</p></div>
    <button type="button" class="btn ghost" @click="markAllRead()">Mark all as read</button></div>
  <div class="panel flush">
    <template x-for="n in myNotifications" :key="n.id">
      <button type="button" class="notif big" :class="{unread: !n.read}" @click="readNotification(n)">
        <i :class="'k-'+n.kind"></i><span x-text="n.text"></span><small x-text="fmtDateTime(n.at)"></small></button>
    </template>
  </div>
</section>
