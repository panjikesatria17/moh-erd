# Role Access Matrix

Dokumen ini merangkum akses UI Procurement berdasarkan middleware di `routes/web.php`.

Tanggal acuan: 2026-03-06

## Legend

- `R` = Read/View
- `W` = Write/Create/Update/Delete/Action
- `-` = Tidak ada akses

## Matrix Per Modul

| Modul                                                 | Super Admin            | Owner            | Purchasing                               | Finance                            | Admin Gudang                | SPPG User         | Vendor Admin      |
| ----------------------------------------------------- | ---------------------- | ---------------- | ---------------------------------------- | ---------------------------------- | --------------------------- | ----------------- | ----------------- |
| Dashboard                                             | R                      | R                | R                                        | R                                  | R                           | R                 | R                 |
| Purchase Requests                                     | R/W                    | R (approve only) | R/W                                      | -                                  | -                           | R/W (scoped SPPG) | -                 |
| Purchase Orders                                       | R/W (generate from PR) | R                | R/W (generate from PR, assign requester) | W (generate invoice from PO)       | W (create delivery from PO) | -                 | R (scoped vendor) |
| Approval Queue                                        | R/W                    | R/W              | -                                        | -                                  | -                           | -                 | -                 |
| Deliveries                                            | R/W                    | R                | -                                        | W (generate invoice from delivery) | R/W                         | -                 | R (scoped vendor) |
| Stock Movements                                       | R                      | R                | -                                        | -                                  | R                           | -                 | -                 |
| Stock Alerts                                          | R                      | R                | -                                        | -                                  | R                           | -                 | -                 |
| Invoices                                              | R/W (create payment)   | R                | -                                        | R/W                                | -                           | -                 | R (scoped vendor) |
| Kwitansi                                              | R/W                    | R                | -                                        | R/W                                | -                           | -                 | -                 |
| Billing Cycles                                        | R                      | R                | -                                        | R                                  | -                           | -                 | -                 |
| Payments                                              | R                      | R                | -                                        | R                                  | -                           | -                 | -                 |
| Master Data SPPG/Vendor/Product/Price History (read)  | R                      | R                | R                                        | -                                  | -                           | -                 | -                 |
| Master Data SPPG/Vendor/Product/Price History (write) | W                      | -                | W (Product & Price History only)         | -                                  | -                           | -                 | -                 |
| Product Categories (write)                            | W                      | -                | -                                        | -                                  | -                           | -                 | -                 |
| Users & Roles                                         | R/W                    | -                | -                                        | -                                  | -                           | -                 | -                 |
| Audit Trails                                          | R                      | R                | -                                        | -                                  | -                           | -                 | -                 |
| Analytics (Vendor Performance, Price Trend)           | R                      | R                | R                                        | R                                  | -                           | -                 | -                 |

## Catatan Kebijakan Penting

1. `super_admin` memiliki akses penuh lintas modul.
2. `owner` berfokus pada approval, audit, dan monitoring, bukan CRUD operasional.
3. `purchasing` fokus PR/PO, serta dapat CRUD product dan update riwayat harga produk.
4. `finance` fokus invoice, pembayaran, billing, dan kwitansi.
5. `admin_gudang` fokus delivery dan inventory.
6. `sppg_user` hanya untuk PR dan dibatasi scope SPPG.
7. `vendor_admin` mendapatkan akses baca terbatas untuk PO, delivery, dan invoice dengan scope vendor sendiri.

## Referensi Teknis

- `routes/web.php`
- `routes/api.php`
- `resources/views/layouts/procurement.blade.php`

## API Read Access (v1)

Endpoint read API sudah disejajarkan dengan matrix UI:

1. `GET /api/v1/purchase-requests` dan `GET /api/v1/purchase-requests/{id}`:
   roles `super_admin`, `owner`, `purchasing`, `sppg_user`
2. `GET /api/v1/purchase-orders` dan `GET /api/v1/purchase-orders/{id}`:
   roles `super_admin`, `owner`, `purchasing`, `vendor_admin`
3. `GET /api/v1/deliveries` dan `GET /api/v1/deliveries/{id}`:
   roles `super_admin`, `owner`, `admin_gudang`, `vendor_admin`
4. `GET /api/v1/invoices` dan `GET /api/v1/invoices/{id}`:
   roles `super_admin`, `owner`, `finance`, `vendor_admin`

Untuk role ter-scope (`sppg_user`, `vendor_admin`), filter data di controller API diterapkan melalui helper reusable `AppliesUserRoleScope`.
