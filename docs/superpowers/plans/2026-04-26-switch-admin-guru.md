# Switch Admin Guru Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambah pertukaran mod admin dan guru pada akaun yang mempunyai kedua-dua role tanpa perlu logout.

**Architecture:** Kekalkan sesi login pada `users` yang sama, kemudian gunakan flag sesi untuk menentukan sama ada akaun sedang dilayan sebagai admin atau guru. Route admin akan dipagar dengan middleware khas, manakala layout dan controller utama akan membaca helper mod pada model `User`.

**Tech Stack:** Laravel, Blade, session middleware, PHPUnit feature tests, Vite build

---

### Task 1: Tambah ujian aliran switch mod

**Files:**
- Modify: `tests/Feature/ImpersonationBackNavigationTest.php`
- Test: `tests/Feature/ImpersonationBackNavigationTest.php`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Write minimal implementation for route, helper, dan middleware**
- [ ] **Step 4: Run test to verify it passes**

### Task 2: Kemas kini layout dan dashboard ikut mod

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Tambah helper mod sesi pada model pengguna**
- [ ] **Step 2: Guna helper itu pada layout dan dashboard**
- [ ] **Step 3: Jalankan semula ujian feature berkaitan**

### Task 3: Verifikasi akhir

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ImpersonationController.php`
- Modify: `app/Http/Middleware/EnsureAdminMode.php`

- [ ] **Step 1: Pastikan route admin dipagar ketika mod guru**
- [ ] **Step 2: Jalankan `php artisan test` untuk skop ciri ini**
- [ ] **Step 3: Jalankan `npm run build`**
- [ ] **Step 4: Commit dan push**
