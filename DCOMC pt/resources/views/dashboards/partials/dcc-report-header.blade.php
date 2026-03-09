{{-- DCC Formal Header for all printable System Reports (absolute consistency). Use $officeLine for Dean (e.g. Office of the Dean). --}}
<div class="dcc-report-header" style="text-align: center;">
    <img src="{{ asset('images/logo.png') }}" alt="Daraga Community College" class="dcc-logo" onerror="this.style.display='none'" />
    <p class="dcc-line-republic" style="margin: 0 0 4px;">Republic of the Philippines</p>
    <p class="dcc-line-college" style="margin: 0 0 4px;">DARAGA COMMUNITY COLLEGE</p>
    <p class="dcc-line-address" style="margin: 0 0 12px;">Salvacion, Daraga, Albay, 4501</p>
    <p class="dcc-office-registrar" style="margin: 0 0 8px; font-size: 16px; font-style: italic;">{{ $officeLine ?? 'Office of the College Registrar' }}</p>
</div>
