---
paths:
  - 'app/Support/GroqInsightClient.php,app/Actions/AnswerBusinessInsightAction.php,app/Http/Controllers/Owner/AiInsightController.php'
---

# Owner

## AI Insight hanya merender laporan Nadi yang tervalidasi
Groq hanya memilih fungsi laporan yang diizinkan beserta parameter tervalidasi; jangan tampilkan teks bebas model atau jalankan SQL dari model. Angka dan jawaban dirender aplikasi dengan business dari middleware owner aktif. Simpan konteks/riwayat per pengguna dan usaha di server; jangan menerima riwayat dari browser atau mengirim hasil query ke model.
