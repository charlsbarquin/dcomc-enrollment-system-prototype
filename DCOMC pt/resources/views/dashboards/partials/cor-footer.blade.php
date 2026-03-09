{{-- COR footer: Printed by and Date at bottom left --}}
<div class="cor-footer-printed">
    <p style="margin: 0 0 4px;">Printed by: {{ $printed_by ?? (auth()->user()->name ?? '—') }}</p>
    <p style="margin: 0;">Date: {{ $printed_date ?? now()->format('M d, Y') }}</p>
</div>
