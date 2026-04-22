# Perbualan Mesej Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menukar modul mesej sedia ada kepada perbualan direct dan bulk dengan compose oleh guru, pemformatan `@nama` / `@pasti`, serta notifikasi FCM melalui aliran database notification sedia ada.

**Architecture:** Kekalkan jadual mesej sedia ada sebagai asas perbualan untuk elak migrasi data besar. Tambah lapisan helper pada model, controller, dan formatter kandungan supaya mesej awal dan balasan boleh dipaparkan sebagai satu thread chat. Semua notifikasi kekal menggunakan channel `database` supaya listener FCM projek terus berfungsi.

**Tech Stack:** Laravel, Blade, PHPUnit, Notifications, FCM listener sedia ada, Vite.

---

## File Structure

- Modify: `app/Http/Controllers/AdminMessageController.php`
- Modify: `app/Models/AdminMessage.php`
- Modify: `app/Notifications/AdminMessageReceivedNotification.php`
- Modify: `app/Notifications/AdminMessageReplyNotification.php`
- Create: `app/Support/ConversationMessageFormatter.php`
- Modify: `routes/web.php`
- Modify: `resources/views/messages/form.blade.php`
- Modify: `resources/views/messages/index.blade.php`
- Modify: `resources/views/messages/show.blade.php`
- Modify: `lang/ms/messages.php`
- Modify: `lang/en/messages.php`
- Create: `tests/Feature/AdminMessageConversationTest.php`
- Create: `tests/Unit/Support/ConversationMessageFormatterTest.php`

## Task 1: Tambah Ujian Formatter

**Files:**
- Create: `tests/Unit/Support/ConversationMessageFormatterTest.php`
- Create: `app/Support/ConversationMessageFormatter.php`

- [ ] Tulis ujian gagal untuk token `@nama` dan `@pasti`
- [ ] Jalankan ujian unit formatter dan sahkan ia gagal
- [ ] Tulis implementasi minimum formatter
- [ ] Jalankan semula ujian dan sahkan ia lulus

## Task 2: Ubah Aliran Compose Perbualan

**Files:**
- Modify: `app/Http/Controllers/AdminMessageController.php`
- Modify: `app/Models/AdminMessage.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminMessageConversationTest.php`

- [ ] Tulis ujian gagal untuk admin compose direct, admin compose bulk, dan guru compose
- [ ] Jalankan ujian feature dan sahkan ia gagal
- [ ] Laksana validasi compose direct/bulk/guru dan penerima sasaran
- [ ] Jalankan ujian feature dan sahkan ia lulus

## Task 3: Kemas Kini Notifikasi Perbualan

**Files:**
- Modify: `app/Notifications/AdminMessageReceivedNotification.php`
- Modify: `app/Notifications/AdminMessageReplyNotification.php`
- Modify: `tests/Feature/AdminMessageConversationTest.php`

- [ ] Tulis ujian gagal untuk payload notifikasi menggunakan label perbualan
- [ ] Jalankan ujian berkaitan dan sahkan ia gagal
- [ ] Laksana payload notifikasi yang serasi dengan direct dan bulk
- [ ] Jalankan ujian berkaitan dan sahkan ia lulus

## Task 4: Tukar UI Kepada Gaya Chat

**Files:**
- Modify: `resources/views/messages/form.blade.php`
- Modify: `resources/views/messages/index.blade.php`
- Modify: `resources/views/messages/show.blade.php`
- Modify: `lang/ms/messages.php`
- Modify: `lang/en/messages.php`

- [ ] Ubah borang compose untuk pilihan direct / bulk dan guru compose
- [ ] Ubah senarai perbualan untuk label, pratonton, dan metadata perbualan
- [ ] Ubah halaman detail kepada thread chat dengan bubble dan borang mesej di bawah
- [ ] Semak terjemahan yang perlu ditambah atau dikemas kini

## Task 5: Pengesahan Akhir

**Files:**
- Test: `tests/Unit/Support/ConversationMessageFormatterTest.php`
- Test: `tests/Feature/AdminMessageConversationTest.php`

- [ ] Jalankan ujian unit dan feature berkaitan mesej
- [ ] Jalankan `npm run build`
- [ ] Semak `git diff`
- [ ] Commit dengan mesej Bahasa Melayu
- [ ] Push ke `origin` branch semasa
