---
paths:
  - 'app/Http/Controllers/Account/**,app/Http/Requests/Account/**,resources/views/account/**,resources/views/components/account/**'
---

# Account

## Personal account controls live in the profile dropdown
Keep Profil Saya, Pengaturan Akun, and POST logout in the top-right profile dropdown, not the sidebar. Email changes use pending_email plus a temporary signed URL that only the same authenticated account may use; the primary email stays unchanged until verification. Store avatars on the public disk under avatars/{user_id} and only delete files from that user's directory.
