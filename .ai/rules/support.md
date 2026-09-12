---
paths:
  - app/Support/GroqInsightClient.php
---

# Support

## GPT OSS memakai satu fungsi untuk rencana gabungan
GPT-OSS 20B/120B di Groq tidak mendukung parallel tool calling. Gunakan satu plan_reports berisi 1–4 laporan, validasi seluruh daftar sebelum membaca data, dan jangan mengganti ini dengan beberapa tool call paralel. Narasi memakai ringkasan terhitung terbaru; riwayat hanya konteks. Batasi waktu total dan sediakan laporan cadangan bila komposisi gagal.
