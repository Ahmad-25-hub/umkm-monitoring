---
paths:
  - 'app/**/Sales*.php,app/Actions/*Sales*.php'
  - 'app/Actions/*Sales*.php'
---

# Actions

## TikTok sales imports are tenant-scoped and idempotent
Interpret TikTok Seller timestamps in Asia/Jakarta. Deduplicate by business + platform + Order ID, aggregate quantities/items for multi-line orders, and count Order Amount only once per order. Do not persist buyer, recipient, phone, or address fields from exports.

## Marketplace sales imports are source-aware and privacy-limited
Accept TikTok Seller CSV and Shopee Seller XLSX through the shared sales-import endpoint. Interpret timestamps in Asia/Jakarta, deduplicate by business + platform + marketplace order ID, aggregate multi-line items, and count order totals once. Never persist buyer usernames, recipient names, phone numbers, shipping addresses, or buyer notes from marketplace exports.

## Offline sales use confirmation and skip existing transactions
Penjualan offline (manual dan template XLSX) tersedia untuk owner/karyawan aktif dan memakai sales_orders platform offline. Nomor transaksi dinormalisasi huruf besar dan unik per usaha+platform; unggahan ulang melewati transaksi yang ada, tidak menimpa seperti impor marketplace. Pratinjau harus disimpan di server, dibatasi session+user+usaha+peran dengan masa berlaku, dan diperiksa lagi saat konfirmasi. Hitung rupiah bulat di server; tanggal penjualan mengikuti WIB.
