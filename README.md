# 🎯 Laravel Game Ticket Scanner API

A Laravel-powered REST API backend for a mobile app that allows users to:
- Register and log in
- Purchase usage plans (via Stripe/PayPal)
- Upload betting or lottery tickets (image format)
- Extract ticket details using OCR
- Automatically verify results based on game type
- Track ticket history and manage favorites
- Edit user profile and optionally translate ticket text

---

## 🚀 Features

- 🔐 Token-based API Authentication
- 🛒 Plan Purchase Integration (Stripe/PayPal)
- 🖼️ Ticket Upload (PNG/JPG)
- 🔍 OCR Integration for extracting text from images
- 🌐 Optional Ticket Translation (e.g., German → English)
- 🧠 Game Result Verification (Swisslos scraping/matching)
- 📋 Ticket Listing, Detail View, History
- ⭐ Favorite Tickets
- 👤 Profile Editing
- 🧪 Postman API Test Collection
- 📄 Terms and Scope Well Defined (see `/docs`)

---

## 🧱 Tech Stack

- Laravel (12 stable version)
- Sanctum (for API authentication)
- MariaDB
- Tesseract OCR / Google Cloud Vision (OCR engine)
- Google Translate API (optional)
- Stripe / PayPal SDKs
- Laravel Queues (for async image/OCR processing)
- Postman (for testing)

---

## 📦 Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve