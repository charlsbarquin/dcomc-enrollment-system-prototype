{{-- Shared styles for COR and Class Masterlist (DCC institutional layout) --}}
<style>
    .dcc-logo { width: 80px; height: 80px; object-fit: contain; margin: 0 auto 6px; display: block; }
    .dcc-line-republic { font-family: 'Times New Roman', serif; font-size: 14px; margin: 0 0 4px; color: #000; }
    .dcc-line-college { font-family: 'Times New Roman', serif; font-size: 22px; font-weight: bold; color: #1E40AF; margin: 0 0 4px; }
    .dcc-line-address { font-family: 'Times New Roman', serif; font-size: 13px; margin: 0 0 8px; color: #374151; }
    .dcc-office-registrar { font-family: 'Times New Roman', serif; font-size: 18px; font-weight: normal; margin: 0 0 12px; color: #1f2937; font-style: normal; }
    .dcc-doc-title { font-family: 'Times New Roman', serif; font-size: 16px; font-weight: bold; text-decoration: underline; margin: 0 0 20px; color: #000; }
    .dcc-cert-body { font-family: 'Times New Roman', serif; font-size: 12px; line-height: 1.5; color: #1f2937; margin-bottom: 16px; text-align: left; }
    .dcc-cert-body .to-whom { font-weight: bold; margin-bottom: 8px; }
    .dcc-cert-body .narrative { margin-bottom: 12px; }
    .dcc-cert-body .student-name { font-weight: bold; }
    .dcc-date-line { font-family: 'Times New Roman', serif; font-size: 12px; margin: 12px 0 20px; text-align: left; }
    .dcc-meta-line { font-family: 'Times New Roman', serif; font-size: 12px; margin: 0 0 6px; color: #374151; }
    .dcc-table-wrap { width: 100%; margin-bottom: 24px; border-collapse: collapse; font-family: 'Times New Roman', serif; font-size: 12px; }
    .dcc-table-wrap table { width: 100%; border-collapse: collapse; font-family: 'Times New Roman', serif; font-size: 12px; }
    .dcc-table-wrap th,
    .dcc-table-wrap td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
    .dcc-table-wrap th { font-weight: bold; background: #fff; }
    .dcc-table-wrap tbody tr:nth-child(even) { background: #fafafa; }
    .dcc-signatory-block { margin-top: 28px; }
    .dcc-certified-label { font-family: 'Times New Roman', serif; font-size: 12px; margin: 0 0 24px; text-align: left; }
    .dcc-signatory-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 40px; max-width: 480px; }
    .dcc-signatory-item { flex: 1; min-width: 0; }
    .dcc-signature-line { border-bottom: 1px solid #000; height: 24px; margin-bottom: 4px; }
    .dcc-signatory-name { font-family: 'Times New Roman', serif; font-size: 12px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase; }
    .dcc-signatory-title { font-family: 'Times New Roman', serif; font-size: 11px; margin: 0; color: #374151; }
    .dcc-footer { margin-top: 32px; padding-top: 12px; text-align: center; }
    .dcc-motto { font-family: 'Times New Roman', serif; font-size: 12px; margin: 0; color: #374151; }
    /* COR institutional header */
    .cor-header { font-family: 'Times New Roman', serif; text-align: center; }
    .cor-logo { width: 80px; height: 80px; object-fit: contain; margin: 0 auto 6px; display: block; }
    .cor-line-republic { font-size: 14px; margin: 0 0 4px; color: #000; }
    .cor-line-college { font-size: 22px; font-weight: bold; color: #1E40AF; margin: 0 0 4px; }
    .cor-line-address { font-size: 13px; margin: 0 0 4px; color: #374151; }
    .cor-line-emails { font-size: 11px; margin: 0 0 6px; color: #374151; }
    .cor-office { font-size: 18px; font-style: italic; margin: 0 0 8px; color: #1f2937; }
    .cor-doc-title { font-size: 18px; font-weight: bold; text-decoration: underline; margin: 0 0 20px; color: #000; border: none; }
    /* COR student general information */
    .cor-info-box { border: 1px solid #000; margin-bottom: 16px; font-family: 'Times New Roman', serif; font-size: 12px; }
    .cor-info-header { border-bottom: 1px solid #000; padding: 8px 12px; font-weight: bold; text-align: center; text-transform: uppercase; }
    .cor-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .cor-info-col { padding: 6px 12px; border-bottom: 1px solid #e5e7eb; }
    .cor-info-col:nth-child(odd) { border-right: 1px solid #e5e7eb; }
    .cor-info-row { display: flex; padding: 4px 0; }
    .cor-info-label { font-weight: bold; min-width: 10em; flex-shrink: 0; }
    .cor-info-value { color: #1f2937; }
    /* COR tables (subjects, fees) */
    .cor-table { width: 100%; border-collapse: collapse; font-family: 'Times New Roman', serif; font-size: 12px; }
    .cor-table th, .cor-table td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
    .cor-table th { font-weight: bold; background: #fff; }
    /* COR signatory: student sig right, approved by two columns */
    .cor-signatory-section { margin-top: 24px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 24px; }
    .cor-student-sig { flex: 0 0 auto; min-width: 180px; }
    .cor-student-sig-line { border-bottom: 1px solid #000; height: 28px; margin-bottom: 4px; }
    .cor-approved-by { flex: 1; min-width: 280px; }
    .cor-approved-by-label { font-family: 'Times New Roman', serif; font-size: 12px; margin: 0 0 16px; text-align: center; }
    .cor-approved-row { display: flex; justify-content: center; gap: 48px; margin-top: 8px; }
    .cor-approved-item { text-align: center; min-width: 140px; }
    .cor-footer-printed { font-family: 'Times New Roman', serif; font-size: 11px; margin-top: 24px; padding-top: 12px; color: #374151; }
    @media print {
        @page { size: A4 portrait; margin: 0.5in; }
        .no-print { display: none !important; }
        body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        .dcc-print-page, .cor-page { margin: 0 !important; padding: 0.5in !important; box-shadow: none !important; background: #fff !important; max-width: none !important; }
        .dcc-print-page * { box-shadow: none !important; }
        /* Hide sidebars and non-print UI (student sidebar, registrar sidebar, etc.) */
        #registrar-sidebar, .role-sidebar, .student-sidebar, .no-print, [class*="no-print"], #filter-panel, .btn, button:not(.dcc-print-only), a[href]:not(.dcc-print-only) { display: none !important; }
        a[href].dcc-print-only { display: inline !important; }
    }
</style>
