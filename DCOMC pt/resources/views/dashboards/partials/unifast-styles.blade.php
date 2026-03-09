{{-- Shared UNIFAST frontend: fonts, hero gradient, focus styles, table/button utilities --}}
<style>
    .font-heading { font-family: 'Figtree', sans-serif; }
    .font-data { font-family: 'Roboto', sans-serif; }
    .hero-gradient { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%); }
    .input-dcomc-focus:focus { outline: none; border-color: #1E40AF; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2); }
    .btn-dcomc-primary { background: #1E40AF !important; color: #fff !important; }
    .btn-dcomc-primary:hover { background: #1D3A8A !important; color: #fff !important; }
    .btn-dcomc-white-pill { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-weight: 700; color: #1E40AF; background: #fff; text-decoration: none; transition: background-color 0.2s, color 0.2s; border: none; cursor: pointer; font-family: 'Figtree', sans-serif; }
    .btn-dcomc-white-pill:hover { background: #f3f4f6; color: #1D3A8A; }
    .btn-dcomc-white-pill:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.4); }
    .table-header-dcomc { background: #1E40AF !important; }
    .hover-bg-blue-50:hover { background-color: rgba(239, 246, 255, 0.5) !important; }
    .unifast-focus-visible a:focus-visible,
    .unifast-focus-visible button:focus-visible,
    .unifast-focus-visible [tabindex="0"]:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.4); border-radius: 0.25rem; }
</style>
