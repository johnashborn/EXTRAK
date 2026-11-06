{{-- ========== Notifications ========== --}}
<section x-show="view==='notifications'">
  <div class="page-head"><h1>Notifications</h1>
    <button type="button" class="btn ghost" @click="markAllRead()">Mark all as read</button></div>
  <div class="panel flush">
    <p class="empty" x-show="!myNotifications.length">No notifications yet.</p>
    <template x-for="n in myNotifications" :key="n.id">
      <button type="button" class="notif big" :class="{unread: !n.read}" @click="readNotification(n)">
        <i :class="'k-'+n.kind"></i><span x-text="n.text"></span><small x-text="fmtDateTime(n.at)"></small></button>
    </template>
  </div>
</section>
