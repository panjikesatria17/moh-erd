# Vendor Logos

Simpan logo vendor pada folder ini agar otomatis muncul di PDF invoice.

## Aturan nama file

Sistem akan mencoba nama berikut (berurutan):

1. `vendor-code` (slug)
2. `vendor-name` (slug)

Dengan ekstensi yang didukung:

- `.png`
- `.jpg`
- `.jpeg`
- `.webp`

Contoh:

- `pd-mitra-utama.png`
- `vn-pdmu-01.png`
- `pt-sudirman-global-mandiri.png`
- `vn-psgm-01.png`

## Catatan

- Gunakan rasio logo asli (jangan ditarik/distorsi).
- Resolusi disarankan minimal 500px sisi terpanjang agar hasil PDF tajam.
- Jika logo vendor tidak ditemukan, sistem fallback ke logo default.
