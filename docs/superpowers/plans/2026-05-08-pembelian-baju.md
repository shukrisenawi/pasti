# Pelaksanaan Pembelian Baju

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membina modul pembelian baju lengkap untuk admin dan guru dengan integrasi n8n, status bayaran, dan default saiz guru.

**Architecture:** Modul baharu menggunakan satu jadual kempen pembelian dan satu jadual respon guru. Controller akan ikut pola modul permintaan guru sedia ada, manakala paparan guna Blade biasa supaya konsisten dengan modul pengumuman dan dashboard semasa.

**Tech Stack:** Laravel, Blade, Livewire sedia ada pada dashboard, PHPUnit, MySQL, n8n webhook service

---

### Task 1: Skema data pembelian baju

**Files:**
- Create: `database/migrations/2026_05_08_130000_add_default_baju_size_to_gurus_table.php`
- Create: `database/migrations/2026_05_08_130100_create_shirt_purchases_table.php`
- Create: `database/migrations/2026_05_08_130200_create_shirt_purchase_responses_table.php`
- Modify: `app/Models/Guru.php`
- Create: `app/Models/ShirtPurchase.php`
- Create: `app/Models/ShirtPurchaseResponse.php`

- [ ] Tambah kolum saiz lalai guru dan jadual pembelian/respon
- [ ] Tambah relation model
- [ ] Pastikan enum saiz dikongsi pada model pembelian

### Task 2: Ujian ciri utama controller

**Files:**
- Create: `tests/Feature/ShirtPurchaseManagementTest.php`

- [ ] Tulis ujian gagal untuk cipta pembelian, respon guru, tandakan bayar, approve, dan n8n broadcast
- [ ] Jalankan ujian fokus dan sahkan ia gagal pada sebab yang betul

### Task 3: Implementasi backend

**Files:**
- Create: `app/Http/Controllers/ShirtPurchaseController.php`
- Modify: `routes/web.php`
- Modify: `app/Services/N8nWebhookService.php`
- Modify: `database/seeders/N8nSettingsSeeder.php`

- [ ] Tambah route admin dan guru
- [ ] Implementasi senarai, cipta pembelian, borang guru, simpan respon, tandakan bayar, approve, dan keluarkan senarai
- [ ] Tambah template n8n untuk pembelian baju

### Task 4: Paparan UI

**Files:**
- Create: `resources/views/shirt-purchases/index.blade.php`
- Create: `resources/views/shirt-purchases/form.blade.php`
- Create: `resources/views/shirt-purchases/show.blade.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Modify: `resources/views/components/bottom-nav.blade.php`
- Modify: `resources/views/dashboard.blade.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `lang/en/messages.php`

- [ ] Tambah menu desktop dan mobile
- [ ] Tambah kad dashboard guru
- [ ] Paparkan senarai pembelian, borang guru, dan senarai admin

### Task 5: Pengesahan

**Files:**
- Modify: fail yang disentuh dalam task sebelum ini jika perlu

- [ ] Jalankan ujian fokus
- [ ] Jalankan `php artisan test`
- [ ] Jalankan `npm run build`
- [ ] Semak perubahan akhir sebelum `git add`, `git commit`, dan `git push`
