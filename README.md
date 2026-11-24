# A-ERP: Enterprise Manufacturing System (Knitting Division)

Selamat datang di proyek **A-ERP**, sistem Enterprise Resource Planning generasi baru untuk industri manufaktur kaos kaki dan rajut (*knitting plant*).

Proyek ini dibangun untuk menggantikan sistem monolitik warisan (`capacity_system`) dengan arsitektur modern berstandar enterprise yang berkinerja tinggi, modular, dan bersih dari *table sprawl*.

---

## 📁 Struktur Direktori Saat Ini

```text
A-ERP/
├── docs/
│   └── database/
│       └── aritektur_database_capacity_system_ideal.md   # Blueprint lengkap konsolidasi 64 -> 22 tabel
├── database/
│   └── schemas/
│       ├── 00_init_full_schema.sql                       # Master DDL komprehensif (semua tabel + view + seed)
│       ├── 01_master_data.sql                            # Master Plant, Area, Mesin, Defect, User
│       ├── 02_orders_and_planning.sql                    # Orders, Style SKU, Shipments, Revisi, PPS, Planning
│       ├── 03_production_and_wip.sql                     # Output Box Rajut, Quality, Rework, WIP Routing
│       └── 04_logistics_and_maintenance.sql              # Benang Lantai, Downtime, Jarum, Tiket Mekanik
└── README.md                                             # Petunjuk inisialisasi & referensi proyek
```

---

## 🚀 Panduan Inisialisasi Database

### 1. Eksekusi Skrip Utuh Sekaligus (Rekomendasi)
Untuk menginisialisasi seluruh database `a_erp` beserta tabel, indeks, relasi, view kalkulasi sisa otomatis, dan seed master data:

```bash
# Masuk ke MySQL / MariaDB via CLI atau HeidiSQL / DBeaver
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS a_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p a_erp < database/schemas/00_init_full_schema.sql
```

### 2. Eksekusi Skrip Modular (Per-Domain)
Jika ingin mengimpor tabel per domain fungsional:
1. `database/schemas/01_master_data.sql`
2. `database/schemas/02_orders_and_planning.sql`
3. `database/schemas/03_production_and_wip.sql`
4. `database/schemas/04_logistics_and_maintenance.sql`

---

## 💡 Keunggulan Desain Database A-ERP

1. **Konsolidasi 65% Tabel:** Dari 64 tabel legacy yang terfragmentasi diringkas menjadi 22 tabel solid.
2. **Eliminasi String Foreign Keys:** Mengganti join `VARCHAR` dengan `BIGINT UNSIGNED` terindeks, mempercepat query JOIN hingga 10x pada jutaan baris box produksi.
3. **Pemberantasan God-Mode Update Sisa:** Sisa order dihitung secara dinamis dan aman melalui view `view_order_shipment_progress`.
4. **Pemisahan Delivery Aman:** Perubahan tanggal pengiriman tidak lagi memecah baris order atau memutuskan relasi ke mesin rajut.
5. **Dukungan Partitioning Skala Besar:** Tabel transaksi tinggi (`production_boxes`, `wip_movements`) dirancang kompatibel dengan pemartisian bulanan/tahunan.

---

## 📖 Dokumentasi Lengkap
Pelajari arsitektur mendalam, analisis *table sprawl*, serta pemetaan relasi ERD pada dokumen:
[docs/database/arsitektur_database_capacity_system_ideal.md](docs/database/arsitektur_database_capacity_system_ideal.md)
