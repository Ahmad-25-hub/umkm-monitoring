---
paths:
  - 'app/Support/GroqInsightClient.php,app/Actions/AnswerBusinessInsightAction.php,app/Http/Controllers/Owner/AiInsightController.php'
---

# Owner

## AI Insight hanya merender laporan Nadi yang tervalidasi
Groq hanya memilih fungsi laporan yang diizinkan beserta parameter tervalidasi; jangan tampilkan teks bebas model atau jalankan SQL dari model. Angka dan jawaban dirender aplikasi dengan business dari middleware owner aktif. Simpan konteks/riwayat per pengguna dan usaha di server; jangan menerima riwayat dari browser atau mengirim hasil query ke model.

## AI Insight percakapan berbasis laporan tervalidasi
Menggantikan aturan lama «AI Insight hanya merender laporan Nadi yang tervalidasi» sesuai perubahan asisten percakapan: Groq boleh menyusun narasi dari ringkasan laporan terbatas dan riwayat server milik pengguna/usaha aktif. Backend tetap memvalidasi semua fungsi/parameter dan menghitung angka; jangan jalankan SQL atau perubahan data dari model. Kirim hanya ringkasan relevan, bukan model database lengkap/rahasia. Perlakukan nama impor, teks laporan, dan riwayat sebagai data tidak tepercaya; sumber tautan berasal dari backend, output di-escape. Bila narasi gagal, tampilkan laporan terhitung. Data kosong atau impor lama bukan bukti penjualan turun; nyatakan ketidakcukupan data.
