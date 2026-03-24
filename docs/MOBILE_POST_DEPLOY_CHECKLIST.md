# Mobile Post-Deploy Checklist

Checklist ini dipakai setelah upload release ke hosting, khusus untuk memastikan masalah "akses aplikasi" dan layout mobile tidak terulang.

## 1. Validasi File Deploy

- Pastikan file `public/hot` tidak ada di server.
- Pastikan folder `public/build` ada dan berisi file CSS/JS hasil build terbaru.
- Pastikan `storage/framework/vite.hot` juga tidak ada (normal untuk production).

## 2. Refresh Cache Aplikasi

Jalankan dari root project:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Jika server memakai OPcache/FPM, lakukan reload service sesuai panel/SSH yang tersedia.

## 3. Cek Konfigurasi Environment

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` sesuai domain aktif (https://domain-anda)
- `SESSION_SECURE_COOKIE=true` untuk HTTPS

## 4. Uji Halaman Welcome di HP

Gunakan HP Android/iOS nyata (bukan hanya device emulation):

- Buka halaman welcome dari mode normal browser.
- Lakukan hard refresh (clear cache browser jika perlu).
- Pastikan heading, logo, dan tombol tidak bertumpuk.
- Pastikan tombol `MASUK APLIKASI` terlihat penuh dan dapat diklik.
- Pastikan halaman bisa di-scroll normal jika konten melebihi tinggi layar.

## 5. Uji Login dan Redirect

- Klik `MASUK APLIKASI` dari welcome.
- Login dengan user valid.
- Pastikan tidak muncul pesan error akses saat session baru dibuat.
- Logout, lalu coba akses ulang halaman dashboard untuk memastikan redirect ke login bekerja.

## 6. Uji Stabilitas Asset

- Buka DevTools remote (jika tersedia) dan cek tidak ada 404 pada `/build/assets/...`.
- Pastikan tidak ada request ke `http://[::1]:5173` atau `localhost:5173`.

## 7. Uji Cepat Multi-Peran (Opsional)

- Owner: dashboard tampil normal.
- Purchasing: menu dan dashboard tampil normal.
- SPPG user: data ter-scope sesuai SPPG.

## 8. Recovery Cepat Jika Layout Kacau Lagi

Urutan tindakan cepat:

1. Hapus `public/hot` jika muncul lagi.
2. Jalankan ulang `php artisan optimize:clear`.
3. Upload ulang `public/build` dari release terbaru.
4. Hard refresh browser HP.
