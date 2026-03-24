@php
    $currencyCode = $businessSetting->currency_code ?: 'BDT';
    $documentChecklist = collect($documentTypes)->except(['picture']);
    $copyOptions = [
        'customer' => 'Customer Copy',
        'office' => 'Office Copy',
        'both' => 'Both Copies',
    ];
    $sheetClass = $copyMode === 'both' ? 'is-double' : 'is-single';
    $totalAmount = (float) $sell->selling_price_to_customer;
    $unitPrice = $sell->quantity > 0 ? $totalAmount / $sell->quantity : $totalAmount;

    $receivedAmount = match ($sell->payment_status) {
        'paid' => $currencyCode . ' ' . number_format($totalAmount, 2),
        'unpaid' => $currencyCode . ' ' . number_format(0, 2),
        default => 'Partially received',
    };

    $dueAmount = match ($sell->payment_status) {
        'paid' => $currencyCode . ' ' . number_format(0, 2),
        'unpaid' => $currencyCode . ' ' . number_format($totalAmount, 2),
        default => 'Balance pending',
    };

    $compactTerms = 'Vehicle inspected by customer. Only checked documents are delivered. Any pending balance must be cleared as agreed.';
@endphp
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $sell->invoice_number }} | Sales Invoice</title>
        <style>
            :root {
                color-scheme: light;
                --page-bg: #e2e8f0;
                --ink: #0f172a;
                --muted: #64748b;
                --line: #cbd5e1;
                --soft: #f8fafc;
                --accent: #0f766e;
                --accent-soft: #ecfeff;
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                background: var(--page-bg);
                color: var(--ink);
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            .print-toolbar {
                position: sticky;
                top: 0;
                z-index: 20;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                padding: 14px 18px;
                background: #111827;
                color: #fff;
            }

            .toolbar-heading {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .toolbar-subtitle {
                color: #94a3b8;
                font-size: 13px;
            }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .copy-selector {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .selector-label {
                color: #cbd5e1;
                font-size: 13px;
                font-weight: 600;
            }

            .selector-chip,
            .toolbar-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 14px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                background: transparent;
                color: #fff;
                text-decoration: none;
                font-size: 13px;
                font-weight: 600;
            }

            .selector-chip.is-active {
                border-color: #14b8a6;
                background: rgba(20, 184, 166, 0.22);
                color: #ccfbf1;
            }

            .toolbar-button.primary {
                border-color: #0f766e;
                background: #0f766e;
            }

            .preview-wrap {
                padding: 16px;
            }

            .print-hint {
                width: 194mm;
                margin: 0 auto 12px;
                padding: 10px 14px;
                border-radius: 10px;
                background: #fff;
                color: var(--muted);
                font-size: 13px;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            }

            .sheet {
                width: 194mm;
                min-height: 281mm;
                margin: 0 auto;
                padding: 4mm;
                border: 1px solid #94a3b8;
                background: #fff;
                box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
            }

            .sheet.is-double {
                display: grid;
                grid-template-rows: repeat(2, minmax(0, 1fr));
                gap: 4mm;
            }

            .sheet.is-single {
                display: block;
            }

            .cashmemo {
                display: flex;
                flex-direction: column;
                gap: 2.6mm;
                border: 1px solid #334155;
                border-radius: 3.5mm;
                padding: 3.4mm;
                background: #fff;
            }

            .sheet.is-double .cashmemo {
                min-height: 132mm;
                position: relative;
            }

            .sheet.is-single .cashmemo {
                min-height: 132mm;
            }

            .sheet.is-double .cashmemo:first-child::after {
                content: '';
                position: absolute;
                left: -4mm;
                right: -4mm;
                bottom: -2.3mm;
                border-bottom: 1px dashed #94a3b8;
            }

            .memo-copy-label {
                display: inline-flex;
                align-self: flex-start;
                padding: 1.4mm 2.8mm;
                border-radius: 999px;
                background: var(--accent-soft);
                color: var(--accent);
                font-size: 8px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .memo-top {
                display: grid;
                grid-template-columns: 18mm 1fr auto;
                gap: 3mm;
                align-items: start;
                padding-bottom: 2mm;
                border-bottom: 1px dashed var(--line);
            }

            .logo-box {
                width: 18mm;
                height: 18mm;
                border-radius: 3mm;
                overflow: hidden;
                border: 1px solid var(--line);
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .logo-box img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .logo-fallback {
                width: 18mm;
                height: 18mm;
                border-radius: 3mm;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #0f766e, #1d4ed8);
                color: #fff;
                font-size: 10px;
                font-weight: 800;
            }

            .brand-name {
                margin: 0;
                font-size: 11pt;
                line-height: 1.1;
            }

            .brand-meta,
            .line-value,
            .note-text,
            .footer-text {
                color: #334155;
                font-size: 7.8pt;
                line-height: 1.35;
            }

            .brand-meta {
                margin-top: 1mm;
            }

            .memo-meta {
                display: flex;
                flex-direction: column;
                gap: 1.4mm;
                min-width: 42mm;
            }

            .meta-row {
                border: 1px solid var(--line);
                border-radius: 2.4mm;
                padding: 1.6mm 2.2mm;
                background: #fff;
            }

            .mini-label,
            .section-title,
            .line-label {
                color: var(--muted);
                font-size: 7pt;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .meta-value {
                margin-top: 0.6mm;
                font-size: 8.6pt;
                font-weight: 700;
            }

            .memo-body {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 3mm;
                flex: 1;
                min-height: 0;
            }

            .column {
                display: flex;
                flex-direction: column;
                gap: 2.6mm;
                min-height: 0;
            }

            .section {
                border: 1px solid var(--line);
                border-radius: 2.8mm;
                overflow: hidden;
                background: #fff;
            }

            .section-title {
                padding: 1.6mm 2.2mm;
                background: var(--soft);
                border-bottom: 1px solid var(--line);
            }

            .section-body {
                padding: 2mm 2.2mm;
            }

            .line-grid {
                display: grid;
                gap: 1.4mm 2.4mm;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .line-row {
                min-height: 9mm;
            }

            .line-value {
                margin-top: 0.8mm;
                padding-bottom: 0.8mm;
                border-bottom: 1px dotted #94a3b8;
                font-weight: 600;
                word-break: break-word;
            }

            .check-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1.6mm 2mm;
            }

            .check-item {
                display: flex;
                align-items: center;
                gap: 1.6mm;
                border: 1px solid var(--line);
                border-radius: 2mm;
                padding: 1.4mm 1.8mm;
                font-size: 7.4pt;
                font-weight: 600;
            }

            .check-symbol {
                width: 6mm;
                color: var(--accent);
                font-weight: 800;
            }

            .note-block {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 3mm;
            }

            .signatures {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 3mm;
                margin-top: 2mm;
            }

            .signature-line {
                padding-top: 6mm;
                border-bottom: 1px solid var(--ink);
                color: var(--muted);
                font-size: 7.6pt;
            }

            .footer-text {
                padding-top: 1.8mm;
                border-top: 1px dashed var(--line);
                text-align: center;
            }

            @media (max-width: 1000px) {
                .print-toolbar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .print-hint,
                .sheet {
                    width: 100%;
                }

                .memo-top,
                .memo-body,
                .note-block,
                .line-grid,
                .check-grid,
                .signatures {
                    grid-template-columns: 1fr;
                }
            }

            @media print {
                @page {
                    size: A4 portrait;
                    margin: 8mm;
                }

                html,
                body {
                    background: #fff;
                }

                .print-toolbar,
                .print-hint {
                    display: none !important;
                }

                .preview-wrap {
                    padding: 0;
                }

                .sheet {
                    width: 100%;
                    min-height: calc(297mm - 16mm);
                    margin: 0;
                    padding: 0;
                    border: none;
                    box-shadow: none;
                }

                .sheet.is-double .cashmemo {
                    min-height: calc((297mm - 16mm - 4mm) / 2);
                }

                .sheet.is-single .cashmemo {
                    min-height: 138mm;
                }
            }
        </style>
    </head>
    <body>
        <div class="print-toolbar">
            <div class="toolbar-heading">
                <strong>Vehicle Sales Invoice</strong>
                <span class="toolbar-subtitle">{{ $sell->invoice_number }} | {{ $sell->name }} | Demo-style A4 cash memo layout</span>
            </div>
            <div class="toolbar-actions">
                <div class="copy-selector">
                    <span class="selector-label">Print:</span>
                    @foreach ($copyOptions as $optionKey => $optionLabel)
                        <a href="{{ route('sells.invoice', ['sell' => $sell, 'copy' => $optionKey]) }}" class="selector-chip {{ $copyMode === $optionKey ? 'is-active' : '' }}">
                            {{ $optionLabel }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('sells.show', $sell) }}" class="toolbar-button">Back to Sale</a>
                <button type="button" class="toolbar-button primary" onclick="window.print()">Print Selected</button>
            </div>
        </div>

        <div class="preview-wrap">
            <div class="print-hint">
                Selected output: <strong>{{ $copyOptions[$copyMode] }}</strong>. This layout is prepared for a single A4 portrait sheet.
            </div>

            <section class="sheet {{ $sheetClass }}">
                @foreach ($copies as $copyLabel)
                    <article class="cashmemo">
                        <div class="memo-copy-label">{{ $copyLabel }}</div>

                        <div class="memo-top">
                            @if ($businessSetting->logo_path)
                                <div class="logo-box">
                                    <img src="{{ $businessSetting->logo_url }}" alt="{{ $businessSetting->display_name }}">
                                </div>
                            @else
                                <div class="logo-fallback">{{ $businessSetting->initials }}</div>
                            @endif

                            <div>
                                <h1 class="brand-name">{{ $businessSetting->display_name }}</h1>
                                <div class="brand-meta">
                                    {{ $businessSetting->address ?: 'Business address not added yet.' }}<br>
                                    Phone: {{ $businessSetting->phone ?: 'N/A' }} | Email: {{ $businessSetting->email ?: 'N/A' }}<br>
                                    Location: {{ $sell->location?->display_name ?: 'N/A' }}
                                </div>
                            </div>

                            <div class="memo-meta">
                                <div class="meta-row">
                                    <div class="mini-label">Invoice No</div>
                                    <div class="meta-value">{{ $sell->invoice_number }}</div>
                                </div>
                                <div class="meta-row">
                                    <div class="mini-label">Invoice Date</div>
                                    <div class="meta-value">{{ $sell->selling_date?->format('d M Y') ?: 'N/A' }}</div>
                                </div>
                                <div class="meta-row">
                                    <div class="mini-label">Payment</div>
                                    <div class="meta-value">{{ $sell->payment_status_label }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="memo-body">
                            <div class="column">
                                <div class="section">
                                    <div class="section-title">Customer Information</div>
                                    <div class="section-body">
                                        <div class="line-grid">
                                            <div class="line-row">
                                                <div class="line-label">Customer Name</div>
                                                <div class="line-value">{{ $sell->name }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Father's Name</div>
                                                <div class="line-value">{{ $sell->father_name ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Mobile Number</div>
                                                <div class="line-value">{{ $sell->mobile_number ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Address</div>
                                                <div class="line-value">{{ $sell->address ?: 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="section">
                                    <div class="section-title">Vehicle Description</div>
                                    <div class="section-body">
                                        <div class="line-grid">
                                            <div class="line-row">
                                                <div class="line-label">Vehicle / Product</div>
                                                <div class="line-value">{{ $sell->vehicle?->display_name ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Brand / Category</div>
                                                <div class="line-value">{{ $sell->vehicle?->brand?->name ?: 'N/A' }} / {{ $sell->vehicle?->category?->name ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Model / Color</div>
                                                <div class="line-value">{{ $sell->vehicle?->model ?: 'N/A' }} / {{ $sell->vehicle?->color ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Year</div>
                                                <div class="line-value">{{ $sell->vehicle?->year ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Registration Number</div>
                                                <div class="line-value">{{ $sell->vehicle?->registration_number ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Engine Number</div>
                                                <div class="line-value">{{ $sell->vehicle?->engine_number ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Chassis Number</div>
                                                <div class="line-value">{{ $sell->vehicle?->chassis_number ?: 'N/A' }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Quantity</div>
                                                <div class="line-value">{{ $sell->quantity }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="column">
                                <div class="section">
                                    <div class="section-title">Documents Included</div>
                                    <div class="section-body">
                                        <div class="check-grid">
                                            @foreach ($documentChecklist as $type => $label)
                                                <div class="check-item">
                                                    <span class="check-symbol">{{ $sell->documentFor($type) ? '[x]' : '[ ]' }}</span>
                                                    <span>{{ $label }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="section">
                                    <div class="section-title">Payment Summary</div>
                                    <div class="section-body">
                                        <div class="line-grid">
                                            <div class="line-row">
                                                <div class="line-label">Total Price</div>
                                                <div class="line-value">{{ $currencyCode }} {{ number_format($totalAmount, 2) }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Unit Price</div>
                                                <div class="line-value">{{ $currencyCode }} {{ number_format($unitPrice, 2) }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Received</div>
                                                <div class="line-value">{{ $receivedAmount }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Due</div>
                                                <div class="line-value">{{ $dueAmount }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Payment Method</div>
                                                <div class="line-value">{{ $sell->payment_method_label }}</div>
                                            </div>
                                            <div class="line-row">
                                                <div class="line-label">Payment Information</div>
                                                <div class="line-value">{{ $sell->payment_information ?: 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="note-block">
                            <div class="section">
                                <div class="section-title">Additional Note</div>
                                <div class="section-body">
                                    <div class="note-text">{{ $sell->extra_additional_note ?: 'No additional note recorded for this sale.' }}</div>
                                </div>
                            </div>

                            <div class="section">
                                <div class="section-title">Terms</div>
                                <div class="section-body">
                                    <div class="note-text">{{ $compactTerms }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="signatures">
                            <div class="signature-line">Customer Signature</div>
                            <div class="signature-line">Authorized Signature</div>
                        </div>

                        <div class="footer-text">
                            {{ $businessSetting->invoice_footer ?: 'Thank you for choosing BikeMart POS.' }}
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </body>
</html>
