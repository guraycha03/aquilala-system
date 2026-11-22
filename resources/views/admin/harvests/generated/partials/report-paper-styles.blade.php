  .report-paper { max-width: 860px; margin: 0 auto; padding: 30px 34px 34px; background: #fff; color: #243128; border: 1px solid #dfe6da; border-radius: 10px; box-shadow: 0 8px 24px rgba(20, 61, 35, .07); font-size: 12.5px; line-height: 1.65; }
  .rp-header { display: flex; align-items: center; gap: 14px; padding-bottom: 13px; border-bottom: 2px solid #3f5a46; }
  .rp-logo { width: 52px; height: 52px; object-fit: contain; flex: 0 0 auto; }
  .rp-org { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
  .rp-org strong { font-size: 13px; font-weight: 800; letter-spacing: .02em; color: #16351f; }
  .rp-org span { font-size: 9.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #6b7d70; }
  .rp-date { margin-left: auto; font-size: 10.5px; color: #6b7d70; text-align: right; white-space: nowrap; }
  .rp-title { margin: 18px 0 10px; font-size: 22px; color: #16351f; line-height: 1.3; }
  table.rp-facts { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 11.5px; }
  table.rp-facts th { width: 92px; padding: 5px 10px 5px 0; text-align: left; vertical-align: top; color: #6b7d70; font-weight: 700; }
  table.rp-facts td { padding: 5px 0; color: #243128; }
  .rp-figures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 22px; }
  .rp-figure { border: 1px solid #dfe6da; border-radius: 8px; padding: 9px 11px; background: #fbfcfa; }
  .rp-figure span { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6b7d70; }
  .rp-figure strong { display: block; margin-top: 3px; font-size: 15px; color: #16351f; font-variant-numeric: tabular-nums; }
  .rp-figure small { display: block; margin-top: 1px; font-size: 10px; color: #6b7d70; }
  .rp-block { margin: 0 0 16px; break-inside: avoid; }
  .rp-block h2 { font-size: 13px; color: #16351f; margin: 0 0 6px; padding-bottom: 4px; border-bottom: 1px solid #e6ebe2; }
  .rp-block p { margin: 0 0 7px; }
  .rp-block ul, .rp-block ol { margin: 0; padding-left: 19px; }
  .rp-block li { margin-bottom: 5px; }
  .rp-rule { border: none; border-top: 1px solid #dfe6da; margin: 24px 0 10px; }
  .rp-foot { font-size: 10px; color: #879a8c; margin: 0; }
  @media (max-width: 700px) {
    .report-paper { padding: 20px 16px 24px; }
    .rp-header { flex-wrap: wrap; }
    .rp-date { margin-left: 0; text-align: left; width: 100%; }
    .rp-figures { grid-template-columns: repeat(2, 1fr); }
    .rp-title { font-size: 19px; }
  }
