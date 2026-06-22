body {
    font-family: 'dejavusans', sans-serif;
    font-size: 8pt;
    color: #222;
    line-height: 1.3;
}

/* Repeating page header (mPDF running header) */
.page-header {
    border-bottom: 2px solid #222;
    padding-bottom: 5px;
    display: table;
    width: 100%;
}

.page-header-left {
    display: table-cell;
    vertical-align: bottom;
}

.page-header-right {
    display: table-cell;
    vertical-align: bottom;
    text-align: right;
}

.day-title {
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.meta {
    font-size: 7pt;
    color: #555;
    margin-top: 2px;
}

/* Customer card. It is a table (not a div) because mPDF only renders a full
   border on a table when it sits inside another table cell. The margin-bottom
   is the vertical gap used both when measuring and when laying out columns. */
table.card {
    width: 100%;
    border: 1.5px solid #bbb;
    background-color: #ffffff;
    /* separate (not collapse): mPDF drops the outer border of a table that
       collapses its borders when it contains a nested table (the products). */
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 5px;
}

table.card > tbody > tr > td {
    padding: 5px 7px;
}

.card-header {
    border-bottom: 1px solid #ddd;
    padding-bottom: 3px;
    margin-bottom: 4px;
    display: table;
    width: 100%;
}

.card-header-left {
    display: table-cell;
    vertical-align: top;
}

.card-name {
    font-size: 8pt;
    font-weight: bold;
}

.card-phone {
    font-size: 6.5pt;
    color: #555;
    margin-top: 1px;
}

.card-header-right {
    display: table-cell;
    vertical-align: top;
    text-align: right;
}

.card-empty-label {
    font-size: 6.5pt;
    color: #aaa;
    font-style: italic;
    margin-top: 2px;
}

.pickup-badge {
    display: inline-block;
    background-color: #222;
    color: #fff;
    font-size: 6pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1px 5px;
    border-radius: 3px;
}

/* Products table inside card */
.products {
    width: 100%;
    border-collapse: collapse;
}

.products td {
    font-size: 7pt;
    padding: 1px 0;
    vertical-align: top;
}

.products .art {
    color: #888;
    width: 52px;
    padding-right: 5px;
}

.products .weight {
    color: #999;
}

.products .qty {
    text-align: right;
    font-weight: bold;
    width: 26px;
    padding-left: 4px;
}

.products tr:not(:last-child) td {
    border-bottom: 1px solid #f0f0f0;
}

.notes {
    margin-top: 3px;
    padding-top: 3px;
    border-top: 1px solid #ddd;
    font-size: 6.5pt;
    color: #444;
    line-height: 1.25;
}

.notes strong {
    color: #222;
}
