---
paths:
  - 'app/**/Sales*.php,app/Actions/*Sales*.php'
---

# Actions

## TikTok sales imports are tenant-scoped and idempotent
Interpret TikTok Seller timestamps in Asia/Jakarta. Deduplicate by business + platform + Order ID, aggregate quantities/items for multi-line orders, and count Order Amount only once per order. Do not persist buyer, recipient, phone, or address fields from exports.
