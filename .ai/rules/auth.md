---
paths:
  - 'app/Support/PasswordResetOtp.php,app/Http/Controllers/Auth/*Password*.php'
---

# Auth

## Password recovery OTP uses shared cache
Password recovery for both login roles uses cache-backed OTP and single-use reset grants, independently of config/auth.php password-broker settings. Keep the cache shared across application instances so resend limits, attempt counts, and grant consumption cannot be bypassed across sessions or servers. Email delivery is synchronous so SMTP failures are shown immediately without requiring a queue worker.
