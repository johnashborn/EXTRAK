{{-- ========== Top bar ========== --}}
<header class="top">
  <p class="where">{{ $where }}, <span x-text="db.year"></span></p>
  <div class="top-actions">
    <button type="button" class="theme-btn" @click="cycleTheme()" :aria-pressed="theme==='dark'" :title="theme==='dark' ? 'Switch to light mode' : 'Switch to dark mode'">
      <svg x-show="theme!=='dark'" viewBox="0 0 24 24"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>
      <svg x-show="theme==='dark'" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
      <span x-text="theme==='dark' ? 'Light mode' : 'Dark mode'"></span></button>
    <div class="notif-wrap" @click.outside="notifOpen=false">
      <button type="button" class="icon-btn" @click="notifOpen=!notifOpen" :aria-expanded="notifOpen" aria-label="Notifications">
        <svg viewBox="0 0 24 24"><path d="M6 9a6 6 0 1 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9M10 20a2 2 0 0 0 4 0"/></svg>
        <span class="dot" x-show="unreadCount" x-text="unreadCount"></span>
      </button>
      <div class="notif-pop" x-show="notifOpen" x-transition.opacity>
        <div class="pop-head"><b>Notifications</b><button type="button" class="link" @click="markAllRead()">Mark all as read</button></div>
        <template x-for="n in myNotifications.slice(0,6)" :key="n.id">
          <button type="button" class="notif" :class="{unread: !n.read}" @click="readNotification(n)">
            <i :class="'k-'+n.kind"></i><span x-text="n.text"></span><small x-text="fmtDateTime(n.at)"></small>
          </button>
        </template>
        <button type="button" class="pop-foot" @click="view='notifications'; notifOpen=false">See all notifications</button>
      </div>
    </div>
  </div>
</header>
