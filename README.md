# DocHome — Real-Time Home Care Platform

DocHome is a home-care platform built with **Laravel 10** that connects patients with caregivers and medical centers. It exposes a RESTful API for the mobile/web apps, with real-time chat and notifications powered by Laravel broadcasting and events.

## Features

- **Patients & caregivers** — separate profiles, caregiver details and medical centers
- **Services & categories** — browse care services by category
- **Bookings** — request and manage home-care visits
- **Real-time chat** — conversations between patients and caregivers with live updates
- **Notifications** — instant alerts through broadcasting / events
- **Payments & wallets** — online payments, caregiver earnings and wallet balances
- **Ratings & reports** — rate caregivers and report issues
- **Admin panel** — manage users, caregivers, centers, services and bookings

## Tech Stack

PHP · Laravel 10 · RESTful API · Laravel Broadcasting & Events · MySQL

## Data Model

`User` · `Caregiver` · `CaregiverDetail` · `Center` · `Category` · `Service` · `Booking` · `Chat` · `ChatParticipant` · `Message` · `Payment` · `Wallet` · `Earning` · `Rating` · `Report` · `Admin`

## Getting Started

```bash
git clone https://github.com/mhmdatya72/DocHome-laravel.git
cd DocHome-laravel
cp .env.example .env
composer install
php artisan key:generate
# set your DB_* and broadcasting values in .env
php artisan migrate
php artisan serve
```

## Author

**Mohamed Atya Hawash** — Senior Full-Stack Laravel Developer
[Portfolio](https://mohamedatya-dev.vercel.app) · [LinkedIn](https://www.linkedin.com/in/mohamedatya-dev/)
