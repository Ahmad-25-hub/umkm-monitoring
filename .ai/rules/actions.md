---
paths:
  - 'app/**/Sales*.php,app/Actions/*Sales*.php'
---

# Actions

## TikTok sales imports are tenant-scoped and idempotent
Interpret TikTok Seller timestamps in Asia/Jakarta. Deduplicate by business + platform + Order ID, aggregate quantities/items for multi-line orders, and count Order Amount only once per order. Do not persist buyer, recipient, phone, or address fields from exports.

## Marketplace sales imports are source-aware and privacy-limited
Accept TikTok Seller CSV and Shopee Seller XLSX through the shared sales-import endpoint. Interpret timestamps in Asia/Jakarta, deduplicate by business + platform + marketplace order ID, aggregate multi-line items, and count order totals once. Never persist buyer usernames, recipient names, phone numbers, shipping addresses, or buyer notes from marketplace exports.
