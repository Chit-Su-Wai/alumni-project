<style>
    .mobile-menu-backdrop { display: none; position: fixed; top: 5rem; left: 0; right: 0; bottom: 0; z-index: 55; background: rgba(15, 23, 42, 0.38); -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px); opacity: 0; transition: opacity 0.3s ease; }
    .mobile-menu-backdrop.active { display: block; opacity: 1; }
    .mobile-menu-overlay { position: fixed; left: 0; right: 0; top: 5rem; z-index: 60; width: 100%; max-height: calc(100vh - 5rem); overflow-y: auto; padding-top: 0; margin-top: 0; border-top: none; transform: translateY(-100%); opacity: 0; visibility: hidden; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s; pointer-events: none; }
    .mobile-menu-overlay.active { transform: translateY(0); opacity: 1; visibility: visible; pointer-events: auto; }
    body.mobile-menu-open { overflow: hidden !important; }
    .menu-btn-icon { display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; line-height: 1; transition: transform 0.2s ease; }
    .menu-btn-icon.is-open { transform: rotate(90deg); }
    @media (min-width: 1024px) { .mobile-menu-overlay.lg-hide, .mobile-menu-backdrop.lg-hide { display: none !important; } }
    @media (min-width: 768px) { .mobile-menu-overlay.md-hide, .mobile-menu-backdrop.md-hide { display: none !important; } }

    .auth-main { min-height: calc(100vh - 6rem); }
    .auth-card { min-height: 640px; }
    .auth-card-hero { min-height: 640px; }
    .auth-form-panel { min-height: 640px; }
    .auth-alerts { min-height: 0; }
    .auth-alerts.has-message { min-height: 3.25rem; }
</style>
