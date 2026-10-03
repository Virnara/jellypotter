# 🍹 Jelly Potter

A Web-based Ordering and Franchise Management System for Jelly Potter beverage outlets, built with native PHP, MySQL, and AJAX.

[![Live Demo](https://img.shields.io/badge/Live--Demo-InfinityFree-000000?style=for-the-badge&logo=php&logoColor=white)](http://jellypotter.infinityfreeapp.com/)
[![GitHub Repository](https://img.shields.io/badge/Repository-jellypotter-blue?style=for-the-badge&logo=github)](https://github.com/Virnara/jellypotter)
![Backend](https://img.shields.io/badge/Backend-PHP--Native-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

---

## 📸 Project Preview

<div align="center">
  <div style="display: flex; overflow-x: auto; gap: 12px; padding-bottom: 10px;">
    <img src="img/01_home.png" alt="Hero Section" width="650" style="border-radius: 8px;">
    <img src="img/02_featured.png" alt="Ramuan Favorit" width="650" style="border-radius: 8px;">
  </div>
  <p><sub>↔️ <i>Geser ke kanan/kiri untuk melihat seluruh antarmuka aplikasi</i></sub></p>
</div>

### Customer Ordering & Menu Catalog

<div align="center">
  <div style="display: flex; overflow-x: auto; gap: 12px; padding-bottom: 10px;">
    <img src="img/03_menu.png" alt="Daftar Menu" width="650" style="border-radius: 8px;">
  </div>
</div>

### Dashboard Management

<div align="center">
  <div style="display: flex; overflow-x: auto; gap: 12px; padding-bottom: 10px;">
    <img src="img/04_dashboard.png" alt="Dashboard Admin" width="650" style="border-radius: 8px;">
    <img src="img/05_kasir.png" alt="Sistem Kasir" width="650" style="border-radius: 8px;">
  </div>
</div>

---

## ✨ Overview
**Jelly Potter** is a dynamic web application designed to streamline order taking, cart management, and sales monitoring for beverage outlets.

Built with native PHP and MySQL, the application enables interactive item additions without page reloads using AJAX, manages real-time order processing, and provides an administrative dashboard for monitoring business operations. The application is production-deployed on cloud hosting with remote database mapping.

---

## 🎯 Objectives
This project aims to:
- Provide an intuitive digital menu catalog featuring diverse beverage options (Red Velvet, Taro, etc.).
- Enable asynchronous cart management (add, update, delete items) for seamless customer interaction.
- Centralize transactional data and order processing into a structured MySQL database.
- Present a dedicated admin dashboard for order tracking and inventory management.

---

## 🚀 Features
- **Interactive Menu Catalog:** Grid display of available drinks with responsive imagery and detailed pricing.
- **Asynchronous Cart Management:** Dynamic item insertion using `api/ajax_tambah_keranjang.php` without refreshing the browser.
- **Order Fetching & State Tracking:** Automated order retrieval (`api/fetch_pesanan.php`) for operational processing.
- **Role-Based Authentication:** Dedicated login and session management for administrative and customer operations (`auth/`).
- **Admin Management Panel:** Comprehensive management suite for tracking transactions, viewing sales reports, and managing menu items (`admin/`).
- **Live Cloud Deployment:** Fully hosted on cloud infrastructure with custom database connections.

---

## 🛠 Tech Stack Matrix

| Category | Technology / Tool | Specification / Usage |
| :--- | :--- | :--- |
| **Backend** | PHP (Native) | Core server-side business logic and endpoint handling |
| **Database** | MySQL | Relational database engine for menu items and orders |
| **Frontend** | HTML5, CSS3, JavaScript | User interface structure, styling, and client routines |
| **Asynchronous Engine** | AJAX (Fetch/jQuery) | Background request handling without page reloads |
| **Web Server / Hosting** | InfinityFree (Apache/Nginx) | Cloud server deployment platform |
| **Development IDE** | Visual Studio Code | Code development environment |

---

## 📂 Project Structure
```text
jellypotter/
├── admin/                  # Admin dashboard & management modules
│   ├── dashboard.php
│   ├── laporan.php
│   ├── pelanggan.php
│   ├── pesanan.php
│   ├── tambah_menu.php
│   ├── tampil.php
│   └── transaksi.php
├── api/                    # Background endpoints & AJAX handlers
│   ├── ajax_tambah_keranjang.php
│   ├── fetch_pesanan.php
│   ├── proses_checkout.php
│   └── proses_pesanan.php
├── auth/                   # Authentication & session control
│   ├── login.php
│   ├── logout.php
│   └── signup.php
├── config/                 # Environment & database drivers
│   ├── koneksi.php.example
│   └── koneksi.php         (git-ignored)
├── includes/               # Reusable UI layout components
│   └── navbar_pengunjung.php
├── img/                    # Static image assets & upload directories
├── .gitignore
├── detail.php
├── edit.php
├── hapus.php
├── index.php
├── katalog.php
├── keranjang.php
└── README.md
⚙️ Database Configuration & Setup
1. Database Schema (jelly_db)
Ensure your MySQL environment includes the required relational tables for menu items, customer transactions, and administrative accounts.

2. Local Connection (config/koneksi.php)
For local execution using XAMPP/WAMP, copy config/koneksi.php.example to config/koneksi.php and configure your database connection driver:

PHP
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "jelly_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
🛠️ Installation & Setup
1. Repository Cloning
Bash
git clone [https://github.com/Virnara/jellypotter.git](https://github.com/Virnara/jellypotter.git)
cd jellypotter
2. Local Deployment (XAMPP)
Move the jellypotter directory into your local web server root (C:/xampp/htdocs/jellypotter).

Open phpMyAdmin (http://localhost/phpmyadmin) and create a database named jelly_db.

Import your project .sql file into jelly_db.

Configure config/koneksi.php with your local database credentials.

Access the application in your browser at http://localhost/jellypotter/index.php.

🌐 Live Web Demo
The application is deployed live on cloud hosting and accessible online:

👉 Access Live Demo Here

🛣️ Roadmap & Future Enhancements
[x] Responsive digital catalog and item detail views.

[x] Asynchronous AJAX cart additions.

[x] Relational MySQL database structure for orders and inventory.

[x] Modular PHP architecture (admin/, api/, auth/, config/, includes/).

[x] Role-based access control (RBAC) for Admin and User authentication.

[ ] PDF receipt generation for completed customer transactions.

[ ] Financial report analytics with visual charts (Chart.js).

👨‍💻 Author
Radel Virdiana
Web Developer • IoT Developer

Building practical, modern digital solutions combining software systems and embedded hardware technology.

🌐 Portfolio: virnara.github.io

🐙 GitHub: @Virnara

📺 YouTube: @Virnara

📄 License
This project is open-source and available under the MIT License.