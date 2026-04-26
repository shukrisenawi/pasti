# Reka Bentuk Switch Admin Guru

**Tarikh:** 2026-04-26

**Matlamat:** Membolehkan akaun yang mempunyai role `admin` dan `guru` bertukar antara mod admin dan mod guru tanpa logout, dengan syarat sesi bermula daripada login akaun admin.

## Ringkasan

Sistem sedia ada sudah menggunakan satu jadual `users` dengan email unik. Oleh itu, kes "email admin dan guru sama" paling konsisten ditafsirkan sebagai satu akaun yang sama mempunyai kedua-dua role `admin` dan `guru`.

Penyelesaian akan menambah mod sesi:

- mod lalai: `admin`
- mod alternatif: `guru`

Apabila akaun admin yang juga guru menekan ikon switch, sesi ditandakan sebagai mod guru. Akaun yang sama kekal login, tetapi UI dan akses admin akan dilayan seperti guru sahaja. Apabila pengguna menekan switch semula, sesi kembali ke mod admin.

## Peraturan

- ikon switch hanya dipaparkan jika pengguna mempunyai role admin/master admin dan role guru, serta mempunyai profil `guru`
- pertukaran ke mod guru hanya dibenarkan daripada sesi login admin
- pengguna yang login terus sebagai guru tidak akan melihat ikon switch ke admin
- mod guru akan menyembunyikan menu admin dan menutup laluan admin

## Perubahan Kod

- tambah helper pada `User` untuk menentukan mod semasa
- tambah controller action dan route untuk tukar mod sesi
- tambah middleware untuk menyekat route admin apabila sesi sedang dalam mod guru
- kemas kini layout dan paparan dashboard supaya ikut mod semasa
- tambah ujian feature untuk ikon switch, pertukaran mod, dan sekatan akses admin semasa mod guru

## Pengujian

- feature test: admin+guru boleh masuk mod guru
- feature test: mod guru boleh kembali ke mod admin
- feature test: guru login biasa tidak melihat switch ke admin
- `npm run build`
