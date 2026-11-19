# HydroEdu – Hydration & Education
> **Platform Edukasi dan Pemantau Hidrasi Berbasis Sains untuk Mendukung Gaya Hidup Sehat**

[![PHP Version](https://img.shields.io/badge/PHP-^8.0-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Chart.js](https://img.shields.io/badge/Chart.js-Analytics-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)
[![SDG 3](https://img.shields.io/badge/SDG%203-Good%20Health%20%26%20Well--being-4C9F38?style=for-the-badge)](https://sdgs.un.org/goals/goal3)
[![SDG 4](https://img.shields.io/badge/SDG%204-Quality%20Education-C5192D?style=for-the-badge)](https://sdgs.un.org/goals/goal4)

---

## 1. Deskripsi Proyek
**HydroEdu (Hydration & Education)** adalah aplikasi web terintegrasi yang dirancang untuk mengatasi permasalahan dehidrasi dan rendahnya literasi kesehatan cairan pada kalangan pelajar dan masyarakat umum. 

Aplikasi ini menggabungkan dua fitur utama:
1. **Pemantau Kesehatan (Health Tracking):** Menghitung kebutuhan cairan personal berdasarkan kondisi fisik dan aktivitas, melacak asupan air secara *real-time*, serta menyajikan analisis grafik konsumsi.
2. **Pusat Edukasi Sains (Scientific Literacy):** Menyajikan artikel ilmiah populer tentang fungsi organ tubuh, serta evaluasi interaktif melalui kuis bergamifikasi untuk membangun kebiasaan minum yang berkelanjutan.

Proyek ini dikembangkan sebagai pemenuhan **Responsi 2 (Proyek Akhir) Praktikum Pemrograman Web I (2026)**, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman.

---

## 2. Latar Belakang & Keterkaitan SDGs

### Latar Belakang
Penelitian medis menunjukkan bahwa dehidrasi ringan (kekurangan cairan 1–2% dari massa tubuh) sudah cukup untuk menurunkan fungsi kognitif, daya konsentrasi, serta memori jangka pendek mahasiswa saat belajar. Namun, kesadaran masyarakat sering kali terbatas pada asumsi kaku bahwa "semua orang wajib minum 2 liter", tanpa memahami bahwa kebutuhan metabolisme setiap individu sangat dipengaruhi oleh berat badan, intensitas aktivitas, dan suhu lingkungan. Di sisi lain, aplikasi pelacak air yang ada saat ini cenderung pasif dan tidak menyertakan literasi edukatif.

### Relevansi SDGs (Sustainable Development Goals)
HydroEdu secara spesifik mendukung implementasi agenda PBB 2030:

* **SDG 3: Good Health and Well-being (Tujuan 3: Kehidupan Sehat dan Sejahtera)**
  * *Target 3.4 & 3.d:* Meningkatkan pencegahan penyakit metabolisme dan disfungsi ginjal melalui pemantauan hidrasi harian serta pembiasaan pola hidup preventif.
* **SDG 4: Quality Education (Tujuan 4: Pendidikan Berkualitas)**
  * *Target 4.7:* Menjamin bahwa seluruh pengguna memperoleh pengetahuan ilmiah dan literasi gaya hidup sehat melalui kurikulum artikel terstruktur dan kuis evaluasi berbasis sains.

---

## 3. Implementasi 9 Modul Logika Bisnis
Sesuai dengan ketentuan Bab II Poin 2.3 Buku Panduan Praktikum Pemrograman Web I, sistem ini mengimplementasikan **minimal 9 modul** yang saling terintegrasi:

| No | Modul | Logika Bisnis & Fitur Utama | Keterkaitan SDGs |
|:--:|---|---|:---:|
| **1** | **Autentikasi & Profil Pengguna** | Sistem pendaftaran akun, autentikasi sesi login (hash password Bcrypt), pengaturan profil fisik (BB, aktivitas, gender), dan hak akses peran (*Role-Based: User vs Admin*). | Fondasi Sistem |
| **2** | **Daily Hydration Tracker** | Log konsumsi air real-time dengan tombol cepat (+250ml gelas, +600ml botol, +1000ml tumbler, dan custom ml), visualisasi animasi level air botol, progress bar, serta opsi hapus riwayat. | **SDG 3** |
| **3** | **Smart Calculator & Reminder** | Kalkulator estimasi kebutuhan cairan berdasarkan rumus klinis (30-35 ml/kg BB + faktor aktivitas/cuaca) yang dapat disimpan langsung ke profil, serta pengaturan alarm pengingat berbasis Web Desktop Notification. | **SDG 3** |
| **4** | **Pusat Edukasi & Artikel Sains** | Katalog artikel edukatif (Kesehatan Ginjal, Performa Otak, Tips Hidrasi, Mitos & Fakta), counter *views*, filter kategori dinamis, dan kotak pencarian artikel. | **SDG 4** |
| **5** | **Quiz & Mini Edu-Game Hidrasi** | Evaluasi wawasan interaktif dengan bank soal pilihan ganda, kalkulasi skor instan, review jawaban beserta pembahasan ilmiah medis, serta integrasi pembuka lencana. | **SDG 4** |
| **6** | **Gamifikasi & Achievement Badges** | Sistem apresiasi lencana pencapaian (*Tegukan Pertama*, *Target Tuntas*, *Pahlawan Hidrasi*, *Cendekia Hidrasi*, dan *Sahabat Komunitas*) berdasarkan pemicu aksi pengguna. | **SDG 3 & 4** |
| **7** | **Dashboard Statistik & Visualisasi Grafik** | Pemantauan pola asupan air 7 hari terakhir secara visual menggunakan grafik batang interaktif (Chart.js) yang dibandingkan dengan target harian. | **SDG 3** |
| **8** | **HydroCommunity (Forum Diskusi)** | Wadah berbagi pengalaman dan tips sehat antar pengguna dengan fitur CRUD postingan topik, kategorisasi thread, serta sistem komentar interaktif. | **SDG 3 & 4** |
| **9** | **Admin Control Panel (CMS)** | Halaman kontrol khusus administrator untuk manajemen konten publikasi artikel edukasi, pengelolaan bank soal kuis, serta pemantauan metrik sistem. | Manajemen Konten |

---

## 4. Teknologi yang Digunakan

* **Frontend:**
  * HTML5 (Struktur semantik)
  * CSS3 & [Bootstrap v5.3.3](https://getbootstrap.com/) (Tata letak responsif & modern)
  * [Bootstrap Icons](https://icons.getbootstrap.com/) (Ikonografi UI)
  * JavaScript (ES6+ Vanilla) (Manipulasi DOM kalkulator, validasi, dan Web Notification API)
  * [Chart.js](https://www.chartjs.org/) (Rendering analitik grafik konsumsi air)
* **Backend:**
  * PHP 8.x Native (Pendekatan prosedural-modular terstruktur)
  * PHP Session Management & Bcrypt Password Hashing
* **Database & Server:**
  * MySQL / MariaDB (Relational Database dengan relasi *Foreign Key* dan *Cascade*)
  * Apache Web Server (XAMPP Environment)

---

## 5. Struktur Direktori Proyek

```text
hydroedu/
├── admin/
│   └── index.php              # Modul 9: CMS Admin (Kelola Artikel & Soal Kuis)
├── assets/
│   └── css/
│       └── style.css          # Styling kustom, palet warna, & animasi botol air
├── config/
│   └── koneksi.php            # Koneksi database MySQLi, Base URL dinamis & fungsi helper
├── database/
│   └── hydroedu.sql           # Skema DDL tabel dan data awal (seeder) 9 modul
├── includes/
│   ├── header.php             # Tag <head>, CDN Bootstrap 5.3, Fonts, & Chart.js
│   ├── navbar.php             # Navigasi responsif (terkondisi status login)
│   └── footer.php             # Footer SDGs 3 & 4, identitas kelompok, & bundle script
├── pages/
│   ├── badges.php             # Modul 6: Galeri lencana & pencapaian gamifikasi
│   ├── calculator.php         # Modul 3: Kalkulator air klinis & setting alarm web
│   ├── edukasi.php            # Modul 4: Katalog artikel & pembaca detail materi
│   ├── forum.php              # Modul 8: HydroCommunity forum diskusi & komentar
│   ├── kuis.php               # Modul 5: Kuis interaktif & pembahasan ilmiah
│   ├── login.php              # Modul 1: Form masuk akun pengguna & admin
│   ├── logout.php             # Modul 1: Pembersihan sesi otentikasi
│   └── register.php           # Modul 1: Pendaftaran akun baru & hitung target otomatis
├── dashboard.php              # Modul 2 & 7: Dashboard pelacak harian & grafik
├── index.php                  # Landing page publik & widget kalkulator cepat
└── README.md                  # Dokumentasi resmi repository GitHub
```

---

## 6. Panduan Instalasi dan Konfigurasi

### Prasyarat Sistem
* XAMPP (disarankan versi dengan PHP $\ge$ 8.0)
* Web Browser modern (Google Chrome / Brave / Mozilla Firefox)
* Git (opsional)

### Langkah Instalasi

1. **Clone atau Unduh Repository:**
   Salin repository ini ke dalam direktori `htdocs` XAMPP Anda:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/username-kelompok/hydroedu.git
   ```
   *(Atau unduh format ZIP lalu ekstrak folder `hydroedu` ke dalam `C:\xampp\htdocs\`)*

2. **Jalankan Layanan Apache & MySQL:**
   * Buka **XAMPP Control Panel**.
   * Klik tombol **Start** pada modul **Apache** dan **MySQL**.

3. **Impor Database MySQL:**
   * Buka peramban dan akses: `http://localhost/phpmyadmin/`
   * Buat database baru dengan nama: `hydroedu`
   * Pilih database `hydroedu` tersebut, lalu klik tab **Import**.
   * Klik **Choose File** dan pilih file: `hydroedu/database/hydroedu.sql`
   * Klik tombol **Go / Kirim** di bagian bawah hingga muncul pesan impor sukses.

4. **Konfigurasi Koneksi (Opsional):**
   Pengaturan koneksi default terletak pada file [config/koneksi.php](config/koneksi.php). Secara bawaan, pengaturan telah disesuaikan dengan konfigurasi standar XAMPP:
   ```php
   $db_host = "localhost";
   $db_user = "root";
   $db_pass = "";
   $db_name = "hydroedu";
   ```

---

## 7. Panduan Menjalankan Aplikasi di Localhost

1. Pastikan modul Apache dan MySQL di XAMPP tetap berjalan (*running*).
2. Buka web browser Anda, lalu ketik alamat berikut pada address bar:
   ```text
   http://localhost/hydroedu/
   ```
3. **Akun Pengujian Demo (Tersedia Otomatis dari Seeder):**
   Untuk kemudahan penilaian responsi, telah disediakan akun siap pakai:

   * **Akun Administrator (Modul CMS):**
     * **Email:** `admin@hydroedu.id`
     * **Password:** `password123`
     * *Fitur:* Akses penuh ke Panel Admin (CMS), kelola artikel edukasi, dan kelola bank soal kuis.
   * **Akun Pengguna Biasa (User):**
     * **Email:** `budi@gmail.com`
     * **Password:** `password123`
     * *Fitur:* Pelacak harian, koleksi lencana, kalkulator air, kuis, dan forum diskusi.

---

## 8. Profil Anggota Tim (Kelompok 7)

Proyek ini disusun dan diselesaikan secara kolaboratif oleh **Kelompok 7**, Praktikum Pemrograman Web I, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman:

| Foto Profil | Data Mahasiswa | Peran & Tanggung Jawab Utama | Kontribusi Modul |
|:---:|---|---|---|
| <img src="https://ui-avatars.com/api/?name=Anggota+1&background=0284c7&color=fff&size=100" width="80" style="border-radius:50%"> | **[Rafa Al-Ghazirah]**<br>NIM: `H1D025047`<br>Informatika UNSOED | **Lead Frontend & UI/UX Specialist**<br>Merancang antarmuka sistem yang adaptif, tata letak navigasi, integrasi assets, dan visualisasi interaktif botol air. | Modul 2 (Visual Tracker UI), Modul 4 (Artikel Edukasi Sains). |
| <img src="https://ui-avatars.com/api/?name=Anggota+2&background=10b981&color=fff&size=100" width="80" style="border-radius:50%"> | **[Alifian Iqbal Prabowo]**<br>NIM: `H1D025023`<br>Informatika UNSOED | **Lead Backend & Database Architect**<br>Merancang skema normalisasi database relasional, logika sesi autentikasi pengguna, dan komputasi kebutuhan cairan. | Modul 1 (Autentikasi & Profil), Modul 3 (Kalkulator Hidrasi & Reminder). |
| <img src="https://ui-avatars.com/api/?name=Anggota+3&background=f59e0b&color=fff&size=100" width="80" style="border-radius:50%"> | **[Sekar Gendis Maheswari]**<br>NIM: `H1D025004`<br>Informatika UNSOED | **Interactive Features & Gamification Developer**<br>Mengembangkan alur kuis interaktif, mekanisme scoring edukasi, sistem klaim lencana (badges), dan forum diskusi. | Modul 5 (Kuis Hidrasi Interaktif), Modul 6 (Gamifikasi Lencana), Modul 8 (HydroCommunity). |
| <img src="https://ui-avatars.com/api/?name=Anggota+4&background=8b5cf6&color=fff&size=100" width="80" style="border-radius:50%"> | **[Dede Hidayatul 'Aziz]**<br>NIM: `H1D025036`<br>Informatika UNSOED | **Data Analytics, CMS Specialist & Quality Assurance**<br>Mengintegrasikan analitik Chart.js grafik konsumsi air, panel kontrol CMS admin, serta pengujian sistem secara menyeluruh. | Modul 7 (Statistik Grafik Mingguan), Modul 9 (CMS Admin & Bank Soal). |

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah lisensi terbuka [MIT License](LICENSE) - bebas digunakan untuk kepentingan pembelajaran akademis dan edukasi kesehatan.
