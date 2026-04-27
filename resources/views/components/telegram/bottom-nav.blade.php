@props(['active' => 'home'])
<nav class="bottom-nav">
    <a href="{{ route('telegram.mini-app.show') }}" class="nav-item {{ $active === 'home' ? 'active' : '' }}">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>
        <span>Home</span>
    </a>
    <a href="{{ route('telegram.mini-app.my-subscriptions') }}" class="nav-item {{ $active === 'my-pass' ? 'active' : '' }}">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        <span>My Pass</span>
    </a>
    <a href="{{ route('telegram.mini-app.qr') }}" class="nav-item {{ $active === 'qr' ? 'active' : '' }}" id="nav-qr-btn">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="5" y="5" width="3" height="3"/><rect x="16" y="5" width="3" height="3"/><rect x="5" y="16" width="3" height="3"/><path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/></svg>
        <span>My QR</span>
    </a>
    <a href="{{ route('telegram.mini-app.chat') }}" class="nav-item {{ $active === 'chat' ? 'active' : '' }}">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01M12 10h.01M16 10h.01" stroke-width="2.5"/></svg>
        <span>AI Chat</span>
    </a>
    <a href="{{ route('telegram.mini-app.subscriptions') }}" class="nav-item {{ $active === 'store' ? 'active' : '' }}">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span>Store</span>
    </a>
</nav>
