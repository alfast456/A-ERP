<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label Box Produksi #{{ $box->no_box }} | {{ $box->box_barcode }}</title>
    <style>
        @page {
            size: 100mm 75mm;
            margin: 2mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 4mm;
            background: #fff;
            color: #000;
        }
        .label-container {
            border: 2px solid #000;
            padding: 3mm;
            box-sizing: border-box;
            height: 94%;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .header .barcode-str {
            font-family: monospace;
            font-size: 11pt;
            font-weight: bold;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
            font-size: 9pt;
            margin-bottom: 3mm;
        }
        .row {
            margin-bottom: 1.5mm;
        }
        .label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #444;
            display: block;
        }
        .val {
            font-weight: bold;
            font-size: 10pt;
        }
        .val-big {
            font-size: 14pt;
            font-weight: 900;
        }
        .footer-barcode {
            text-align: center;
            border-top: 1.5px dashed #000;
            padding-top: 2mm;
            margin-top: 2mm;
        }
        .barcode-mock {
            font-family: 'Courier New', Courier, monospace;
            font-size: 20pt;
            letter-spacing: 3px;
            font-weight: bold;
            display: block;
            margin-bottom: 1mm;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px; background: #f3f4f6; padding: 10px; border-radius: 6px;">
        <button onclick="window.print()" style="padding: 6px 14px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            🖨️ Cetak Label Sekarang
        </button>
        <span style="margin-left: 10px; font-size: 12px; color: #555;">Ukuran standar: 100mm x 75mm (Thermal Sticker)</span>
    </div>

    <div class="label-container">
        <div class="header">
            <div>
                <h1>A-ERP KNITTING</h1>
                <span style="font-size: 7.5pt; font-weight: bold;">LABEL KONTROL BOX RAJUT</span>
            </div>
            <div style="text-align: right;">
                <div class="barcode-str">{{ $box->box_barcode ?? ('BX-' . $box->id) }}</div>
                <div style="font-size: 8pt;">TGL: {{ $box->production_date ? $box->production_date->format('d/m/Y') : date('d/m/Y') }}</div>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="row">
                    <span class="label">Nomor PO / Buyer</span>
                    <span class="val">{{ $box->shipment?->orderStyle?->order?->po_number ?? '-' }} ({{ $box->shipment?->orderStyle?->order?->buyer_name ?? '-' }})</span>
                </div>
                <div class="row">
                    <span class="label">Kode Style / Model</span>
                    <span class="val">{{ $box->shipment?->orderStyle?->style_code ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Ukuran (Size) & Warna</span>
                    <span class="val">{{ $box->shipment?->size ?? '-' }} / {{ $box->shipment?->color ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Area & Mesin</span>
                    <span class="val">{{ $box->factoryArea?->name ?? '-' }} | Mc #{{ $box->machine?->machine_number ?? '-' }}</span>
                </div>
            </div>

            <div style="border-left: 1.5px solid #000; padding-left: 3mm;">
                <div class="row">
                    <span class="label">Nomor Box Fisik</span>
                    <span class="val-big">#{{ $box->no_box }}</span>
                </div>
                <div class="row">
                    <span class="label">Shift Kerja</span>
                    <span class="val-big">SHIFT {{ $box->shift == 1 ? 'A' : ($box->shift == 2 ? 'B' : ($box->shift == 3 ? 'C' : $box->shift)) }}</span>
                </div>
                <div class="row">
                    <span class="label">Kuantitas Output (Pcs)</span>
                    <span class="val-big" style="color: #000;">{{ number_format($box->good_pcs) }} <span style="font-size: 9pt;">PCS</span></span>
                </div>
                <div class="row">
                    <span class="label">Berat Timbangan (Kg)</span>
                    <span class="val">{{ $box->weight_kg ? number_format($box->weight_kg, 2) . ' KG' : '-' }}</span>
                </div>
            </div>
        </div>

        <div class="footer-barcode">
            <div class="barcode-mock">||| | ||||| || |||| ||| |||||</div>
            <div style="font-size: 8pt; font-family: monospace; font-weight: bold;">*{{ $box->box_barcode ?? ('BX-' . $box->id) }}*</div>
        </div>
    </div>
</body>
</html>
