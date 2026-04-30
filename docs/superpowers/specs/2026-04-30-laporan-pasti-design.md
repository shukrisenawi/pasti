# Reka Bentuk Menu Laporan PASTI

Tarikh: 2026-04-30

## Ringkasan

Tambah satu menu baharu untuk admin bernama `Laporan PASTI` yang memaparkan jadual laporan berasaskan maklumat elaun guru terbaru. Halaman ini khusus untuk kegunaan `master_admin` dan `admin` dalam mod admin.

## Objektif

- Memudahkan admin melihat ringkasan terkini maklumat elaun guru mengikut PASTI.
- Menyediakan satu jadual padat yang boleh diimbas dengan cepat.
- Memastikan admin biasa hanya melihat data PASTI di bawah jagaan mereka.

## Skop

Dalam skop:

- Menu baharu `Laporan PASTI` dalam navigasi admin.
- Route dan controller baharu untuk laporan.
- Paparan jadual laporan.
- Query untuk mengambil rekod maklumat elaun terbaru bagi setiap guru.
- Susunan ikut nama PASTI dan nama guru.
- Semua paparan teks jadual dalam huruf besar.

Di luar skop:

- Export Excel atau PDF.
- Filter lanjutan.
- Carian langsung.
- Perubahan pada proses isi maklumat elaun guru.

## Sumber Data

### Guru

Ambil daripada jadual `gurus`:

- `active`
- `name`
- `kad_pengenalan`
- `phone`
- `pasti_id`

### Maklumat elaun terbaru

Ambil daripada jadual `guru_salary_requests`:

- rekod terbaru per guru
- `elaun`
- `gaji`

Nota:

- Struktur semasa hanya mempunyai `gaji` dan `elaun`.
- Untuk memenuhi keperluan laporan, kolum `ELAUN TAMBAHAN` akan menggunakan nilai `gaji` buat masa ini.

### PASTI

Ambil daripada jadual `pastis`:

- `name`
- `address`

## Akses

- `master_admin`: boleh lihat semua rekod.
- `admin`: hanya boleh lihat guru di bawah `assignedPastis`.
- `guru`: tiada akses.

## Definisi Status

- Jika `gurus.active = true`, papar `GURU`
- Jika `gurus.active = false`, papar `BERHENTI`

## Susunan Data

Susunan lalai:

1. `NAMA PASTI` menaik
2. `NAMA GURU` menaik

## Reka Bentuk Paparan

Halaman akan guna layout admin sedia ada dengan satu kad utama yang mengandungi jadual.

Kolum jadual:

1. `STATUS`
2. `NAMA GURU`
3. `NO KAD PENGENALAN`
4. `NO HP`
5. `ELAUN`
6. `ELAUN TAMBAHAN`
7. `NAMA PASTI`
8. `ALAMAT`

Peraturan paparan:

- Semua nilai teks dipaparkan dalam huruf besar.
- Nilai wang dipaparkan sebagai `RM` dengan dua titik perpuluhan.
- Jika data tiada, gunakan `-`.

## Senibina Pelaksanaan

### Route

Tambah route baharu untuk admin:

- `GET /laporan-pasti`

Nama route dicadangkan:

- `pasti-reports.index`

### Controller

Buat controller baharu, contohnya:

- `PastiReportController`

Tanggungjawab:

- semak akses pengguna
- bina query laporan
- hantar dataset ke view

### View

Buat view baharu, contohnya:

- `resources/views/pasti-reports/index.blade.php`

Tanggungjawab:

- render header halaman
- render jadual laporan
- render keadaan kosong jika tiada data

## Strategi Query

Query akan:

- bermula daripada `gurus`
- gabung dengan `pastis`
- ambil rekod `guru_salary_requests` terbaru untuk setiap guru
- kekalkan guru walaupun tiada rekod elaun, supaya laporan masih lengkap

Logik ini membolehkan:

- satu baris satu guru
- data elaun paling baru sahaja
- susunan ikut PASTI

## Pengendalian Keadaan Khas

- Guru tanpa PASTI: masih dipaparkan dengan `NAMA PASTI` dan `ALAMAT` sebagai `-` jika query akses membenarkan.
- Guru tanpa rekod elaun: `ELAUN` dan `ELAUN TAMBAHAN` dipaparkan sebagai `-`.
- Akaun ujian bernama `TEST`: tidak perlu ditapis kecuali kemudian ada arahan tambahan.

## Ujian

Tambah ujian feature untuk:

- admin boleh lihat menu dan halaman laporan
- admin biasa hanya nampak guru di bawah PASTI jagaan
- status `GURU` dan `BERHENTI` dipaparkan betul
- jadual memaparkan data elaun terbaru sahaja untuk setiap guru
- semua kolum utama dipaparkan

## Risiko dan Mitigasi

Risiko:

- Kekeliruan istilah `ELAUN TAMBAHAN` kerana struktur sebenar hanya ada `gaji`.

Mitigasi:

- Implement ikut keputusan semasa: peta `gaji` kepada `ELAUN TAMBAHAN`.
- Jika kemudian anda mahu beza sebenar antara `gaji`, `elaun`, dan `elaun tambahan`, kita boleh tambah medan baharu dalam fasa seterusnya.
