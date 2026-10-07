<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accepted Import File Types
    |--------------------------------------------------------------------------
    |
    | Every bulk importer in the system (products, members) validates against
    | this single list. Validation messages, the accept= attribute on the file
    | pickers, and the UI copy are all derived from it, so this is the only
    | place that needs editing when the accepted formats change.
    |
    | Current decision (October 2026): CSV and XLSX only. These are the two
    | formats Philippine associations and government offices actually produce
    | — XLSX is the default save format of MS Excel and of Google Sheets, and
    | CSV is the open-data/tabular interchange format prescribed for government
    | data (DBM-OPS/PCDSPO JMC 2015-01). Legacy .xls and .json were dropped:
    | .xls is an unsupported binary format, and .json is not something a
    | non-technical user ever produces by hand.
    |
    | To widen the list later (once the association confirms it is needed):
    |
    |   1. Add the extension to the array below, e.g. 'xls'.
    |   2. If it is a spreadsheet, make sure a reader handles it —
    |      AdminUserImportController::readRawRows() dispatches per extension.
    |      ProductImportService already auto-detects via PhpSpreadsheet.
    |
    | Nothing else changes: every message, template and file picker follows
    | this list automatically.
    |
    */

    'extensions' => ['csv', 'xlsx'],

    /*
    |--------------------------------------------------------------------------
    | Template Formats
    |--------------------------------------------------------------------------
    |
    | Subset of the above for which a blank download template exists. A format
    | may be importable without us offering a template for it.
    |
    */

    'template_extensions' => ['csv', 'xlsx'],

];
