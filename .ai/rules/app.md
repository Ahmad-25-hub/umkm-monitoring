---
paths:
  - 'app/**'
  - 'app/**/*Sales*.php'
  - 'app/**/*Task*.php'
---

# App

## Pisahkan identitas dari akses usaha
Jangan menaruh business_id atau role pada users. Dashboard pemilik hanya memakai business_memberships dengan role owner dan status active; active_business_id di session harus selalu divalidasi terhadap membership tersebut sebelum digunakan.

## Kode undangan tidak disimpan mentah
Simpan hanya hash kode undangan pada business_invitation_codes. Kode mentah boleh ditampilkan sekali melalui flash session setelah registrasi atau rotasi; rotasi harus mengganti hash lama dan hanya dapat dilakukan owner aktif.

## TikTok sales imports are tenant-scoped and idempotent
Interpret TikTok Seller timestamps in Asia/Jakarta. Deduplicate by business + platform + Order ID, aggregate quantities/items for multi-line orders, and count Order Amount only once per order. Do not persist buyer, recipient, phone, or address fields from exports.

## Daily task calendar and history use WIB
Task schedule dates and due_at values are civil wall-clock values entered in Asia/Jakarta (Task::TIMEZONE); keep the application timezone and event timestamps in UTC. Generate a separate occurrence per task + assignee + local date, retaining prior statuses/notes. Page fallback generation must be scoped to the active business (and employee on employee pages); never reset historical rows.
