  <div class="me">
    <span class="avatar" x-text="me.initials"></span>
    <span><b x-text="me.name"></b><small x-text="me.title"></small></span>
  </div>
  <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="signout">
    @csrf
    <button type="submit"><svg viewBox="0 0 24 24"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11"/></svg>Sign out</button>
  </form>
