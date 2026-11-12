<style>
    :root { --admin-sidebar-width: 238px; }
    body { padding-left: var(--admin-sidebar-width) !important; }
    body > .nav { display: none !important; }
    .admin-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 20;
        width: var(--admin-sidebar-width);
        min-height: 100vh;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 28px;
        padding: 24px 17px;
        background: #182329;
        color: #fff;
        font: 14px/1.5 Inter, ui-sans-serif, system-ui, sans-serif;
    }
    .admin-sidebar__brand {
        display: block;
        padding: 0 0 18px;
        border-bottom: 1px solid #405057;
        color: #fff;
        font-weight: 850;
        letter-spacing: .14em;
        text-decoration: none;
        text-align: center;
    }
    .admin-sidebar__logo { display:block; width:52px; height:52px; margin:0 auto 8px; object-fit:contain; border-radius:50%; }
    .admin-sidebar__brand-name { display:block; }
    .admin-sidebar__role { display:block; margin-top:2px; color:#d1bb83; font-size:10px; letter-spacing:.08em; }
    .admin-sidebar__links {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    .admin-sidebar__links a {
        display: block;
        padding: 11px 8px 11px 10px !important;
        border-radius: 7px;
        color: #fff;
        text-decoration: none;
        text-align: left;
        transition: background-color .15s ease;
    }
    .admin-sidebar__links a:hover,
    .admin-sidebar__links a:focus-visible {
        background: #2b3b42;
        outline: none;
    }
    .admin-sidebar__links a.is-active {
        background: #2b3b42;
        box-shadow: inset 3px 0 0 #c3a25c;
    }
    .admin-sidebar__links a:focus-visible {
        box-shadow: 0 0 0 2px #c3a25c;
    }
    .admin-sidebar__logout {
        width: max-content;
        min-width: 78px;
        margin-top: auto;
        padding: 9px 13px;
        border: 1px solid #657178;
        border-radius: 6px;
        background: transparent;
        color: #fff;
        cursor: pointer;
        text-align: center;
        white-space: nowrap;
    }
    .admin-back {
        position: fixed;
        top: 24px;
        bottom: auto;
        right: 30px;
        z-index: 9999;
        display: inline-flex !important;
        padding: 8px 12px;
        border: 1px solid #d8d2c5;
        border-radius: 6px;
        background: #fff;
        color: #756238;
        font: 700 13px/1.2 Inter, ui-sans-serif, system-ui, sans-serif;
        text-decoration: none;
    }
    .admin-back:hover { background: #faf7ee; border-color: #c3a25c; }
    @media (max-width: 700px) {
        body { padding-left: 0 !important; }
        .admin-sidebar {
            position: static;
            width: 100%;
            min-height: auto;
        }
        .admin-sidebar__links { flex: none; }
        .admin-sidebar__logout { margin-top: 12px; }
    }
</style>
<aside class="admin-sidebar" aria-label="Admin navigation">
    <a class="admin-sidebar__brand" href="{{ route('staff.bookings.index') }}"><img class="admin-sidebar__logo" src="{{ asset('images/cerberus-logo.jpg') }}" alt="Cerberus logo"><span class="admin-sidebar__brand-name">CERBERUS</span><span class="admin-sidebar__role">{{ auth()->user()->role === 'admin' ? 'ADMIN' : 'STAFF' }}</span></a>
    <nav class="admin-sidebar__links">
        <a class="{{ request()->routeIs('staff.bookings.*') ? 'is-active' : '' }}" href="{{ route('staff.bookings.index') }}">Bookings</a>
        @if(auth()->user()->role === 'admin')
            <a class="{{ request()->routeIs('staff.users.*') ? 'is-active' : '' }}" href="{{ route('staff.users.index') }}">Staff accounts</a>
        @endif
    </nav>
    @if(!request()->routeIs('staff.bookings.index'))
        <a class="admin-back" href="{{ route('staff.bookings.index') }}" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">← Back</a>
    @endif
    <form method="post" action="{{ route('staff.logout') }}">
        @csrf
        <button class="admin-sidebar__logout" type="submit">Sign out</button>
    </form>
</aside>
