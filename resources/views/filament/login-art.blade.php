{{-- Brand panel shown on the left of the login and password reset pages. --}}
@if (request()->routeIs('filament.admin.auth.*'))
    <aside class="extrak-art">
        <div class="x-brand">
            <img src="{{ asset('images/logo.png') }}" alt="" onerror="this.style.display='none'">
            <b>EXTRACK</b>
        </div>

        <div>
            <div class="x-eyebrow">Bohol Island State University · Extension Services</div>
            <h2>One view of every <em>barangay</em> we serve.</h2>
            <p class="x-lead">Record, review and report community work across every campus, and see overlaps before they happen.</p>
        </div>

        <div>
            <div class="x-campuses-label">Six campuses, one record</div>
            <div class="x-chips">
                <span>Main</span><span>Balilihan</span><span>Bilar</span>
                <span>Calape</span><span>Candijay</span><span>Clarin</span>
            </div>
        </div>
    </aside>
@endif
