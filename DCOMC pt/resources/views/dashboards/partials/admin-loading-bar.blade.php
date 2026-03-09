{{-- Page-level loading indicator; hide when DOM is ready. Include once per admin page if not using admin-shell. --}}
<div id="admin-page-loading" class="admin-loading-bar" aria-hidden="true"></div>
<script>
(function(){ var el = document.getElementById('admin-page-loading'); if(el) document.addEventListener('DOMContentLoaded', function(){ el.classList.add('admin-loading-done'); }); })();
</script>
