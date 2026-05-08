# Reka Bentuk Pembelian Baju

## Tujuan

Tambah modul pembelian baju yang membolehkan admin membuka kempen pembelian untuk guru dalam PASTI di bawah tugasan admin tersebut, guru mengisi saiz dan status bayaran, dan admin mengeluarkan senarai pembelian ke n8n.

## Skop

- Admin cipta pembelian dengan tajuk dan keterangan.
- Sistem sasarkan pembelian kepada guru aktif, bukan pembantu, dalam PASTI tugasan admin.
- Sistem hantar notifikasi ke n8n group guru apabila pembelian dicipta.
- Guru isi atau kemaskini saiz, kuantiti, catatan, dan tanda sudah bayar.
- Saiz terakhir guru disimpan pada profil guru untuk jadi nilai lalai bagi pembelian baharu.
- Admin boleh lihat senarai respon, tandakan bayaran secara manual, dan approve guru yang sudah bayar.
- Admin boleh keluarkan senarai ke n8n yang hanya mengandungi guru yang sudah isi saiz.

## Reka Bentuk Data

### Jadual `shirt_purchases`

- `id`
- `title`
- `description`
- `created_by`
- `sent_to_n8n_at`
- `last_broadcast_at`
- `timestamps`

### Jadual `shirt_purchase_responses`

- `id`
- `shirt_purchase_id`
- `guru_id`
- `size`
- `notes`
- `quantity`
- `submitted_at`
- `paid_at`
- `paid_marked_by`
- `approved_at`
- `approved_by`
- `timestamps`

### Kolum baharu pada `gurus`

- `default_baju_size`

## Aliran Pengguna

### Admin

1. Buka menu `Pembelian Baju`.
2. Lihat tab senarai pembelian dan borang cipta pembelian.
3. Bila pembelian dicipta, sistem hasilkan rekod respon sasaran untuk semua guru yang layak dan hantar mesej n8n.
4. Admin buka detail pembelian untuk lihat semua guru sasaran, respon, bayaran, dan kelulusan.
5. Admin boleh tandakan `Dah Bayar` secara manual.
6. Admin boleh approve guru yang sudah bayar.
7. Admin boleh klik `Keluarkan Senarai` untuk hantar tajuk, guru yang sudah isi saiz, saiz, kuantiti, dan status bayaran ke n8n.

### Guru

1. Guru nampak pembelian pada dashboard jika ada pembelian yang belum diisi atau belum selesai.
2. Guru buka halaman `Pembelian Baju`.
3. Guru isi `saiz`, `catatan`, `kuantiti` default `1`, dan pilihan `dah bayar`.
4. Bila simpan, `submitted_at` dikemaskini dan `default_baju_size` guru disimpan.

## Paparan

- Menu desktop admin di bawah `Laporan/Aktiviti`.
- Menu desktop guru sebagai pautan terus.
- Bottom navigation guru tambah item `Baju`.
- Dashboard guru tambah kad tindakan jika ada pembelian yang belum diisi.
- Halaman indeks guna pola serupa `announcements` dan `guru-salary-information`.

## Integrasi n8n

Tambah dua template teks:

- `n8n_text_shirt_purchase_request`
- `n8n_text_shirt_purchase_list`

Template pertama dihantar ketika kempen dicipta. Template kedua dihantar ketika admin klik `Keluarkan Senarai`.

## Kawalan Akses

- Admin dan master admin boleh cipta, lihat, tanda bayar, approve, dan keluarkan senarai.
- Admin biasa hanya boleh akses guru/pembelian dalam PASTI tugasan mereka.
- Guru hanya boleh kemaskini respon sendiri.

## Ujian

- Cipta pembelian hanya sasarkan guru aktif dalam PASTI tugasan admin.
- Guru simpan respon dan `default_baju_size` dikemaskini.
- Admin boleh tanda bayar dan approve.
- Broadcast n8n hanya mengandungi guru yang sudah isi saiz.
- Dashboard guru memaparkan pembelian tertunda.
