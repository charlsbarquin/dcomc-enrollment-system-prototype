{{-- Shared DCOMC redesign: match Students Explorer & Manual Registration. Include in <head>. --}}
<style>
    .font-heading { font-family: 'Figtree', sans-serif; }
    .font-data { font-family: 'Roboto', sans-serif; }
    .forms-canvas { background: #f3f4f6; }
    .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
    .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
    .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
    .btn-primary:hover { background: #1D3A8A; }
    .btn-primary:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
    .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s, border-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
    .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
    .btn-back-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); color: #fff; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s; border: none; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
    .btn-back-hero:hover { background: rgba(255,255,255,0.3); }
    .btn-white-hero { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1rem; border-radius: 0.5rem; border: 2px solid rgba(255,255,255,0.6); background: #fff; color: #1f2937; font-size: 0.875rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; cursor: pointer; font-family: 'Roboto', sans-serif; }
    .btn-white-hero:hover { background: #f9fafb; }
    .btn-white-hero:focus { outline: none; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1E40AF; }
    .btn-action-edit { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; cursor: pointer; font-family: 'Roboto', sans-serif; text-decoration: none; }
    .btn-action-edit:hover { background: #f9fafb; }
    .btn-action-readonly { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.75rem; font-weight: 600; transition: background-color 0.2s; text-decoration: none; cursor: pointer; font-family: 'Roboto', sans-serif; border: none; }
    .btn-action-readonly:hover { background: #1D3A8A; }
    /* Admin buttons: solid, rounded, no underline */
    .admin-btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.5rem; background: #1E40AF; color: #fff; font-size: 0.875rem; font-weight: 600; font-family: 'Roboto', sans-serif; text-decoration: none; border: none; cursor: pointer; transition: background 0.2s; }
    .admin-btn-primary:hover { background: #1D3A8A; color: #fff; text-decoration: none; }
    .admin-btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 2px solid #d1d5db; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 600; font-family: 'Roboto', sans-serif; text-decoration: none; cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    .admin-btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; text-decoration: none; }
    .admin-btn-danger { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.5rem; background: #dc2626; color: #fff; font-size: 0.875rem; font-weight: 600; font-family: 'Roboto', sans-serif; text-decoration: none; border: none; cursor: pointer; transition: background 0.2s; }
    .admin-btn-danger:hover { background: #b91c1c; color: #fff; text-decoration: none; }
    .admin-btn-success { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.5rem; background: #059669; color: #fff; font-size: 0.875rem; font-weight: 600; font-family: 'Roboto', sans-serif; text-decoration: none; border: none; cursor: pointer; transition: background 0.2s; }
    .admin-btn-success:hover { background: #047857; color: #fff; text-decoration: none; }
    .pill { display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; font-family: 'Roboto', sans-serif; }
    .pill-success { background: #059669; color: #fff; }
    .pill-warning { background: #d97706; color: #fff; }
    .pill-danger { background: #dc2626; color: #fff; }
    .pill-neutral { background: #6b7280; color: #fff; }
    .card-forms { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .table-header-dcomc { background: #1E40AF; color: #fff; }
    .table-header-dcomc th { padding: 0.75rem 1rem; font-family: 'Figtree', sans-serif; font-weight: 600; text-align: left; }
    .table-header-dcomc th.text-right { text-align: right; }
    .admin-table-wrap { border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    .admin-table-wrap table { border-collapse: collapse; }
    .admin-table-wrap tbody tr { transition: background 0.15s ease; }
    .admin-table-wrap tbody tr:hover { background: rgba(239, 246, 255, 0.6); }
    .admin-table-wrap tbody td { font-family: 'Roboto', sans-serif; padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; }
    .admin-table-wrap tbody tr:last-child td { border-bottom: none; }
    .card-dcomc-top { border-top: 10px solid #1E40AF; }
    .admin-pagination .pagination { display: flex; flex-wrap: wrap; gap: 0.25rem; }
    .admin-pagination .page-link { padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: #1E40AF; color: #fff !important; border: none; text-decoration: none; font-family: 'Roboto', sans-serif; font-size: 0.875rem; }
    .admin-pagination .page-link:hover { background: #1D3A8A; }
    .admin-pagination .page-item.disabled .page-link { background: #94a3b8; cursor: not-allowed; }
    .admin-pagination .page-item.active .page-link { background: #1D3A8A; font-weight: 600; }
    .folder-card-dcomc { border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff; overflow: hidden; transition: all 0.2s ease; text-decoration: none; color: inherit; display: block; }
    .folder-card-dcomc:hover { box-shadow: 0 4px 12px rgba(30, 64, 175, 0.12); border-color: #1E40AF; transform: translateY(-1px); }
    .folder-preview-dcomc { height: 100px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc, #eff6ff); border-bottom: 1px solid #eef2f7; }
    /* Admin loading bar (top); hides when .admin-loading-done */
    .admin-loading-bar { position: fixed; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #1E40AF 0%, #60A5FA 50%, #1E40AF 100%); background-size: 200% 100%; animation: admin-loading-shimmer 1.2s ease-in-out infinite; z-index: 9999; transition: opacity 0.2s ease-out; }
    .admin-loading-bar.admin-loading-done { opacity: 0; pointer-events: none; }
    @keyframes admin-loading-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    /* Table skeleton (optional) */
    .admin-table-skeleton tbody tr td { height: 2.5rem; }
    .admin-table-skeleton .skeleton-cell { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; animation: admin-loading-shimmer 1.2s ease-in-out infinite; border-radius: 0.25rem; height: 1rem; }
    /* Empty state */
    .admin-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; }
    .admin-empty-state-icon { width: 4rem; height: 4rem; margin-bottom: 1rem; color: #94a3b8; }
    .admin-empty-state-title { font-family: 'Figtree', sans-serif; font-size: 1.125rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem; }
    .admin-empty-state-text { font-size: 0.875rem; color: #64748b; margin-bottom: 1rem; max-width: 24rem; }
    [x-cloak] { display: none !important; }
</style>
