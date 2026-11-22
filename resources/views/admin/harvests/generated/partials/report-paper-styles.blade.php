  /* The saved report is laid out as A4 sheets. On screen it reads like a PDF
     viewer; in print/download each sheet becomes one physical page. */
  @page { size: A4; margin: 16mm; }
  .ahr-report-paper-host { width: 100%; max-width: 100%; min-width: 0; }
  .report-paper { width: 100%; margin: 0 auto; color: #3d4a40; font-size: 12px; line-height: 1.6; }
  .rp-document { display: flex; flex-direction: column; align-items: center; gap: 22px; }
  .rp-sheet-frame { width: 210mm; }

  .rp-sheet {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 210mm;
    min-height: 297mm;
    padding: 15mm 16mm 13mm;
    background: #fff;
    border: 1px solid #dfe6da;
    border-radius: 6px;
    box-shadow: 0 8px 22px rgba(20, 61, 35, .08);
  }
  .rp-sheet::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 12px; background: #8faf94; border-radius: 6px 6px 0 0; }

  .rp-header { display: flex; align-items: center; gap: 14px; padding-bottom: 13px; border-bottom: 2px solid #b9cbb5; }
  .rp-logo { width: 52px; height: 52px; object-fit: contain; flex: 0 0 auto; }
  .rp-org { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
  .rp-org strong { font-size: 13px; font-weight: 800; letter-spacing: .02em; color: #2f4a38; }
  .rp-org span { font-size: 9.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #708274; }
  .rp-org small { font-size: 8.5px; color: #829488; }
  .rp-date { margin-left: auto; font-size: 10.5px; color: #708274; text-align: right; white-space: nowrap; }
  .rp-date span { display: block; font-size: 9px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }

  .rp-running-head {
    display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
    padding-bottom: 8px; margin-bottom: 18px; border-bottom: 1px solid #dfe6da;
    font-size: 9.5px; letter-spacing: .08em; text-transform: uppercase; color: #708274;
  }
  .rp-running-head strong { color: #2f4a38; font-weight: 800; }

  .rp-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin: 18px 0 12px; }
  .rp-title-copy { min-width: 0; }
  .rp-document-type { margin: 0 0 4px; font-size: 9.5px; font-weight: 800; letter-spacing: .2em; color: #4a6352; }
  .rp-title { margin: 0; font-size: 22px; color: #2f4a38; line-height: 1.25; }
  .rp-years { margin: 5px 0 0; font-size: 10.5px; font-weight: 500; letter-spacing: .02em; color: #708274; }
  .rp-section-title { margin: 0 0 4px; font-size: 14px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #2f4a38; }
  .rp-section-note { margin: 0 0 16px; font-size: 10.5px; color: #708274; }

  table.rp-facts { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
  table.rp-facts th { width: 96px; padding: 5px 10px 5px 0; text-align: left; vertical-align: top; color: #708274; font-weight: 700; }
  table.rp-facts td { padding: 5px 0; color: #3d4a40; }

  .rp-figures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 20px; }
  .rp-figure { border: 1px solid #dfe6da; border-radius: 8px; padding: 9px 11px; background: #fbfcfa; }
  .rp-figure span { display: block; font-size: 9px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #708274; }
  .rp-figure strong { display: block; margin-top: 3px; font-size: 14px; color: #2f4a38; font-variant-numeric: tabular-nums; }
  .rp-figure small { display: block; margin-top: 1px; font-size: 9.5px; color: #829488; }

  .rp-block { margin: 0 0 14px; break-inside: avoid; page-break-inside: avoid; }
  .rp-block h2 { font-size: 10.5px; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; color: #2f4a38; margin: 0 0 6px; padding-bottom: 4px; border-bottom: 1px solid #d7e0d4; }
  .rp-block p { margin: 0 0 7px; text-align: justify; }
  .rp-block ul, .rp-block ol { margin: 0; padding-left: 19px; }
  .rp-block li { margin-bottom: 5px; text-align: justify; }

  .rp-table { width: 100%; border-collapse: collapse; margin: 12px 0 18px; font-size: 10px; break-inside: avoid; page-break-inside: avoid; }
  .rp-table caption { margin-bottom: 6px; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #2f4a38; }
  .rp-table th, .rp-table td { padding: 6px 7px; border: 1px solid #dfe6da; text-align: right; font-variant-numeric: tabular-nums; }
  .rp-table th { background: #f5f8f3; color: #526657; font-size: 9px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
  .rp-table th:first-child, .rp-table td:first-child { text-align: left; }
  .rp-table tbody tr:nth-child(even) { background: #fcfdfb; }
  .rp-table-season { margin-bottom: 10px; }

  .rp-chart-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 12px; margin-bottom: 18px; }
  .rp-chart-card { border: 1px solid #dfe6da; border-radius: 8px; padding: 12px 14px; background: #fbfcfa; break-inside: avoid; page-break-inside: avoid; }
  .rp-chart-wide { grid-column: 1 / -1; }
  .rp-chart-card h3 { margin: 0 0 3px; font-size: 10.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #2f4a38; }
  .rp-chart-note { margin: 0 0 8px; font-size: 9px; color: #829488; }
  .rp-chart-empty { margin: 14px 0; font-size: 10px; color: #879a8c; }
  .rp-svg { display: block; width: 100%; height: auto; }
  .rp-grid { stroke: #e4ebe1; stroke-width: 1; }
  .rp-axis { fill: #829488; font-size: 9px; font-family: "Segoe UI", Arial, sans-serif; }
  .rp-bar-wet { fill: #a9c9df; }
  .rp-bar-dry { fill: #e3c9af; }
  .rp-legend { display: flex; justify-content: center; gap: 18px; margin-top: 4px; font-size: 9.5px; color: #708274; }
  .rp-legend-center { margin-top: 0; }
  .rp-legend span { display: inline-flex; align-items: center; gap: 5px; }
  .rp-swatch { display: inline-block; width: 9px; height: 9px; border-radius: 2px; }
  .rp-swatch.wet { background: #a9c9df; }
  .rp-swatch.dry { background: #e3c9af; }

  .rp-donut { display: block; width: 118px; height: 118px; margin: 0 auto 4px; }
  .rp-donut-track { fill: none; stroke: #eef2ec; stroke-width: 14; }
  .rp-donut-wet { fill: none; stroke: #a9c9df; stroke-width: 14; stroke-linecap: round; }
  .rp-donut-dry { fill: none; stroke: #e3c9af; stroke-width: 14; }
  .rp-donut-value { fill: #2f4a38; font-size: 20px; font-weight: 800; font-family: "Segoe UI", Arial, sans-serif; }
  .rp-donut-caption { fill: #829488; font-size: 8px; font-family: "Segoe UI", Arial, sans-serif; }

  .rp-hbar-row { display: grid; grid-template-columns: 92px minmax(0, 1fr) 96px; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 9.5px; }
  .rp-hbar-label { color: #3d4a40; font-weight: 700; }
  .rp-hbar-track { display: block; height: 9px; border-radius: 999px; background: #eef2ec; overflow: hidden; }
  .rp-hbar-fill { display: block; height: 100%; border-radius: inherit; background: #8faf94; }
  .rp-hbar-value { text-align: right; color: #708274; font-variant-numeric: tabular-nums; }

  .rp-attestation { display: flex; gap: 36px; margin-top: 28px; font-size: 10px; }
  .rp-attestation > div { flex: 1; }
  .rp-attestation span { display: block; font-weight: 700; color: #708274; }
  .rp-attestation strong { display: block; margin-top: 14px; font-weight: 600; color: #3d4a40; }
  .rp-attest-noted strong { min-height: 52px; }

  .rp-sheet-footer {
    display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
    margin-top: auto; padding-top: 10px; border-top: 1px solid #dfe6da;
    font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #879a8c;
  }
  .rp-outside-note { max-width: 720px; margin: 18px auto 0; font-size: 9.5px; line-height: 1.6; color: #829488; text-align: center; }

  /* Overview mode: all three sheets shown together as paper thumbnails. */
  .ahr-report-paper-host.overview .rp-document {
    display: grid;
    grid-template-columns: repeat(3, 224px);
    gap: 16px;
    justify-content: center;
    align-items: start;
  }
  .ahr-report-paper-host.overview .rp-sheet-frame {
    width: 224px;
    height: 317px;
    overflow: hidden;
    border-radius: 4px;
    background: #fff;
    box-shadow: 0 8px 20px rgba(20, 61, 35, .14);
  }
  .ahr-report-paper-host.overview .rp-sheet-frame:hover { outline: 2px solid #8faf94; }
  .ahr-report-paper-host.overview .rp-sheet {
    transform: scale(.282);
    transform-origin: top left;
    border: 0;
    box-shadow: none;
  }
  .ahr-report-paper-host.overview .rp-outside-note { display: none; }

  @media (max-width: 760px) {
    .ahr-report-paper-host.overview .rp-document { grid-template-columns: repeat(2, 224px); }
  }
  @media (max-width: 520px) {
    .ahr-report-paper-host.overview .rp-document { grid-template-columns: 224px; }
  }

  @media (max-width: 820px) {
    /* The frame kept its A4 width before, so the sheet looked zoomed/cropped
       on phones. Both frame and sheet now follow the available width. */
    .rp-document { width: 100%; }
    .rp-sheet-frame { width: 100%; }
    .rp-sheet { width: 100%; min-height: 0; padding: 20px 16px 18px; }
    .rp-header { flex-wrap: wrap; }
    .rp-date { margin-left: 0; width: 100%; text-align: left; }
    .rp-figures { grid-template-columns: repeat(2, 1fr); }
    .rp-chart-grid { grid-template-columns: 1fr; }
    .rp-title-row { flex-direction: column; gap: 12px; }
    .rp-title { font-size: 19px; }
  }

  @media print {
    html, body { background: #fff !important; }
    .ahr-page, .ahr-main, .ahr-report-paper-host, .ahr-lang-panel { margin: 0 !important; padding: 0 !important; max-width: none !important; }
    .ahr-report-toolbar, .ahr-topbar, .ahr-page-head, .ahr-kpis, .ahr-confirm-overlay, .ahr-download-toast,
    .ahr-ai-fab, .ahr-ai-panel { display: none !important; }
    .rp-document { display: block; }
    .rp-sheet-frame { width: auto; }
    .ahr-report-paper-host.overview .rp-document { display: block; }
    .ahr-report-paper-host.overview .rp-sheet-frame { width: auto; height: auto; overflow: visible; box-shadow: none; border-radius: 0; }
    .ahr-report-paper-host.overview .rp-sheet { transform: none; }
    .rp-outside-note { display: none; }
    .rp-sheet {
      width: auto; min-height: 0; margin: 0; padding: 0;
      border: 0; border-radius: 0; box-shadow: none;
      break-after: page; page-break-after: always;
    }
    .rp-sheet:last-child { break-after: auto; page-break-after: auto; }
  }
