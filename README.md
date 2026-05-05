<div align="center">

# 💊 Medicare

### *Your health, served faster.*

A kiosk-based pharmacy management system for medicine ordering with a real-time queue display — built for seamless customer and staff experience.

[![Status](https://img.shields.io/badge/Status-Active-4A9D6F?style=flat-square)](https://github.com/Jamaica81828282/Medicare)
[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Academic-EBE0E3?style=flat-square)](#license)

</div>

---

## 📖 About

**Medicare** is a full-stack pharmacy management system designed around a self-service kiosk experience. Customers can browse and order medicines directly from the kiosk, while a separate real-time queue display keeps the counter organized and patients informed — reducing wait times and minimizing manual staff effort.

---

## ✨ Features

- 🖥️ **Self-Service Kiosk** — Customer-facing interface for browsing and ordering medicines without staff assistance
- 📺 **Real-Time Queue Display** — Separate screen showing live queue status and ticket numbers
- 💊 **Medicine Catalog** — Browse medicines by category with details and availability
- 🛒 **Order Management** — Place, track, and manage medicine orders
- 👨‍💼 **Admin Dashboard** — Manage inventory, orders, and queue from a central panel
- 📦 **Inventory Management** — Track medicine stock levels and get low-stock alerts
- 🧾 **Transaction Records** — Full order history and receipts
- 🔒 **Authentication** — Secure staff and admin login system

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel (PHP) |
| Frontend | Blade Templates, Tailwind CSS, JavaScript |
| Database | MySQL |
| Build Tool | Vite |
| Local Server | XAMPP (Apache + MySQL) |

---

## 📁 Project Structure

```
Medicare/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Request handlers
│   │   └── Middleware/     # Auth & guards
│   └── Models/             # Eloquent models
├── config/                 # App configuration
├── database/
│   ├── migrations/         # Database schema
│   └── seeders/            # Sample data
├── public/                 # Public assets (CSS, JS, images)
├── resources/
│   └── views/              # Blade templates (kiosk, queue, admin)
├── routes/
│   └── web.php             # Application routes
├── storage/                # Logs & file uploads
├── .env.example            # Environment config template
└── artisan                 # Laravel CLI
```

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org) (PHP 8.1+, MySQL, Apache)
- [Composer](https://getcomposer.org)
- [Node.js](https://nodejs.org) v18+

### 1. Clone the repository

```bash
git clone https://github.com/Jamaica81828282/Medicare.git
cd Medicare
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Then open `.env` and update your database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medicare
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run database migrations & seeders

```bash
php artisan migrate --seed
```

### 6. Build assets & start the server

```bash
npm run dev
php artisan serve
```

Visit `http://localhost:8000` in your browser.

> 💡 **Tip:** For the kiosk and queue display, open them in separate browser windows or screens for the best experience.

---

## 🖥️ System Views

| View | URL | Description |
|---|---|---|
| Kiosk | `/kiosk` | Customer self-service ordering screen |
| Queue Display | `/queue` | Real-time queue board for the counter |
| Admin Panel | `/admin` | Staff dashboard for managing everything |

---

## 🤝 Contributing

This project is currently for academic use. Pull requests are welcome for bug fixes and improvements.

1. Fork the repo
2. Create your branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m 'Add your feature'`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## 📄 License

Academic Use Only — © 2025 Medicare

---

<div align="center">
  <sub>Built with ❤️ using Laravel · Making pharmacy visits faster and smarter</sub>
</div>
