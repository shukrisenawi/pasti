# Reka Bentuk Perbualan Mesej

**Tarikh:** 2026-04-22

**Matlamat:** Menukar modul mesej sedia ada daripada gaya surat bertajuk kepada konsep perbualan yang menyokong chat 1-1, mesej pukal admin, balasan dalam thread yang sama, serta notifikasi terus ke FCM.

## Ringkasan

Sistem sedia ada sudah mempunyai jadual `admin_messages`, `admin_message_replies`, dan `admin_message_recipients`. Daripada memperkenalkan subsistem baharu, modul ini akan dinaik taraf supaya jadual semasa mewakili `perbualan` dan mesej-mesej dalam perbualan.

Reka bentuk ini mengekalkan keserasian data lama sambil menambah:

- guru boleh memulakan perbualan baharu
- admin boleh memulakan perbualan 1-1 atau pukal
- paparan ditukar kepada gaya chat
- mesej menyokong emoji dan teks bebas
- token `@nama` dan `@pasti` digantikan dengan data penghantar
- notifikasi database sedia ada terus diproses ke FCM melalui listener projek

## Model Konsep

### Perbualan

`admin_messages` dianggap sebagai kepala perbualan:

- `sent_to_all = true` menandakan perbualan pukal
- jika penerima seorang sahaja dan `sent_to_all = false`, ia dianggap perbualan direct
- mesej awal dalam rekod `admin_messages.body` ialah mesej pertama dalam thread

### Mesej Dalam Perbualan

- mesej awal: `admin_messages.body`
- mesej susulan: `admin_message_replies`

Paparan akan menyatukan kedua-dua sumber ini sebagai aliran mesej tunggal.

## Peraturan Akses

### Admin / Master Admin

- boleh lihat perbualan yang mereka cipta
- `master_admin` juga boleh lihat semua perbualan
- boleh mula perbualan direct dengan seorang guru
- boleh hantar mesej bulk kepada semua guru atau guru terpilih
- boleh balas perbualan yang boleh diakses

### Guru

- boleh lihat perbualan yang mereka menjadi peserta
- boleh memulakan perbualan baharu kepada semua `master_admin` dan admin yang ditugaskan pada PASTI sendiri
- boleh membalas perbualan direct atau bulk yang mereka sertai
- dalam thread bulk, guru boleh melihat mesej dan balasan semua peserta dalam thread itu

## UI

### Senarai Perbualan

Halaman inbox akan berubah daripada kad statik kepada senarai perbualan:

- nama atau label perbualan
- pratonton mesej terkini
- masa aktiviti terakhir
- bilangan peserta untuk bulk
- petunjuk direct atau bulk

### Paparan Thread

Halaman detail akan memaparkan:

- bubble chat bagi mesej awal dan semua balasan
- kedudukan bubble berdasarkan penghantar
- maklumat peserta perbualan
- borang mesej di bawah seperti chat
- sokongan lampiran kekal

## Pemformatan Kandungan

Sebelum mesej disimpan / dipaparkan, kandungan akan diproses:

- `@nama` -> nama samaran penghantar, atau nama biasa jika tiada
- `@pasti` -> nama PASTI penghantar jika ada

Emoji tidak memerlukan logik khas kerana textarea dan pangkalan data sudah menyokong teks Unicode.

## Notifikasi

Setiap mesej baharu yang mewakili perbualan atau balasan akan terus menghasilkan notifikasi database:

- perbualan baharu: `AdminMessageReceivedNotification`
- balasan: `AdminMessageReplyNotification`

Oleh sebab projek sudah mendaftarkan listener `SendDatabaseNotificationToFcm`, notifikasi tersebut akan terus diteruskan ke FCM untuk pengguna yang mempunyai token sah.

## Perubahan Kod Utama

- `AdminMessageController` akan disusun semula untuk menyokong compose oleh guru, compose bulk/direct oleh admin, dan label perbualan
- `AdminMessage` akan menerima helper untuk jenis perbualan dan nama paparan
- notifikasi mesej akan guna label perbualan baharu, bukan bergantung pada `title` sahaja
- view `messages/index`, `messages/show`, dan `messages/form` akan ditukar kepada pengalaman chat
- ujian baharu akan meliputi aliran compose dan pemformatan token

## Ralat dan Tepi Kes

- jika guru tiada profil `guru` atau PASTI, sistem akan menolak compose baharu
- jika admin pilih mod direct tetapi penerima tiada, sistem akan pulangkan ralat validasi
- jika token `@nama` atau `@pasti` tiada konteks, formatter akan gantikan dengan rentetan kosong atau fallback yang selamat

## Pengujian

- feature test untuk admin direct, admin bulk, dan guru compose
- unit test untuk formatter `@nama` / `@pasti`
- pengesahan notifikasi database diwujudkan kepada penerima sasaran
- `npm run build` untuk sahkan view masih dibina dengan baik
