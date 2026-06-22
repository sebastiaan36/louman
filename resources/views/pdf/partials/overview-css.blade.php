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

/* Only vertical padding on the card cell, so divider lines run edge-to-edge;
   horizontal insets are applied to the inner content instead. */
table.card > tbody > tr > td {
    padding: 5px 0;
}

/* Real table (not display:table divs, which mPDF lays out poorly) so the
   contact info sits right-aligned at the same height as the company name. */
table.card-header {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 4px;
    /* Border on the TABLE, not the cells: on some PHP/mPDF builds cell (td)
       borders are not drawn while table borders are (like the outer card box). */
    border-bottom: 1px solid #ddd;
}

table.card-header > tbody > tr > td {
    padding-bottom: 3px;
    vertical-align: top;
}

.card-header-left {
    text-align: left;
    padding-left: 7px;
}

.card-header-right {
    text-align: right;
    padding-right: 7px;
}

.card-name {
    font-size: 8pt;
    font-weight: bold;
}

.card-phone {
    font-size: 6.5pt;
    color: #555;
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

/* One table per product line; the divider between products is a table border
   (top), because cell borders are not drawn on the production mPDF build. */
table.product {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

table.product.product-divider {
    border-top: 1px solid #e5e5e5;
}

table.product td {
    font-size: 7pt;
    padding: 1px 0;
    vertical-align: top;
}

table.product .art {
    color: #888;
    width: 59px;
    padding-left: 7px;
    padding-right: 5px;
}

table.product .weight {
    color: #999;
}

table.product .qty {
    text-align: right;
    font-weight: bold;
    width: 33px;
    padding-left: 4px;
    padding-right: 7px;
}

/* A table (not a div) so the divider line spans the full card width — mPDF
   shrinks a block div to its content width inside a cell. */
table.notes {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 3px;
    /* Border on the table, not the cell (see card-header). */
    border-top: 1px solid #ddd;
}

table.notes td {
    padding: 3px 7px 0 7px;
    font-size: 6.5pt;
    color: #444;
    line-height: 1.25;
}

table.notes strong {
    color: #222;
}
