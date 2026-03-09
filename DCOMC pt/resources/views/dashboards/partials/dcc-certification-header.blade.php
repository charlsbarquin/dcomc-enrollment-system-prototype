{{-- DCC Institutional Header for COR and Class Masterlist (match Certification of Grades) --}}
@php
    $documentTitle = $documentTitle ?? 'CERTIFICATE OF REGISTRATION';
@endphp
<div class="dcc-cert-header text-center">
    <img src="{{ asset('images/logo.png') }}" alt="Daraga Community College" class="dcc-logo" onerror="this.style.display='none'">
    <p class="dcc-line-republic">Republic of the Philippines</p>
    <p class="dcc-line-college">DARAGA COMMUNITY COLLEGE</p>
    <p class="dcc-line-address">Salvacion, Daraga, Albay, 4501</p>
    <p class="dcc-office-registrar">Office of the College Registrar</p>
    <h2 class="dcc-doc-title">{{ $documentTitle }}</h2>
</div>
