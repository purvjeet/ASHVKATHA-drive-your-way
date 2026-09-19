# ASHVKATHA — Smart Vehicle Rental & Fleet Management System

> **Drive Your Way** — Premier Fleet Rental & Underground Supercar Acquisition Platform.  
> Built strictly with **Vanilla PHP 8**, **MySQL / MariaDB (PDO)**, **HTML5**, and **CSS3**.

---

## 📌 Project Overview

**ASHVKATHA** is a full-stack automotive rental, fleet management, and exclusive vehicle acquisition platform tailored for high-volume enterprise operations and enthusiast performance markets across Gujarat.

The system is architected around two core business models:
1. **Public Fleet Rental Portal**: On-demand self-drive car rentals across 6 flagship hubs (Ahmedabad, Rajkot, Gandhinagar, Junagadh, Amreli, Surendranagar) with categorized tiers (Hatchback, Sedan, SUV, 4x4, EV, Hybrid, Performance, MPV), coupon validation, deposit handling, and structured technical spec-sheets.
2. **Underground Night Market**: Private title allocations for non-road-legal, track-spec supercars and collector builds (60% direct valuation), featuring single-buyer exclusivity locking, enclosed trailer / air transport logistics, and crypto/wire escrow protocols.
3. **Dedicated Dashboards**:
   - **Customer Portal**: Self-service booking tracking, Night Market acquisition garage, invoice downloads, vouchers, and profile management.
   - **Administrator Control Panel**: Real-time KPI metrics, active acquisition volume tracking (with deal cancellation deductions), fleet inventory management, maintenance logs, discount engine, and customer oversight.

---

## 🛠 Tech Stack

- **Backend**: Pure PHP 8 (Clean MVC-inspired procedural structure, PDO prepared statements, session-based role authorization)
- **Database**: MySQL / MariaDB (InnoDB, Foreign Key integrity, UTF-8 mb4) with automatic SQLite zero-setup fallback
- **Frontend**: Semantic HTML5 & Modern Vanilla CSS3 (Custom Glassmorphic design, CSS Grid & Flexbox, responsive typography)
- **Icons & Typography**: Font Awesome 6.5, Google Fonts (*Outfit*, *Schibsted Grotesk*, *Syne*)
- **Dependencies**: **Zero** bloated JavaScript frameworks or external node packages. Lightweight, high performance, and universally maintainable.

---

## 📂 Project Structure

```text
ashvkatha/
├── assets/
│   ├── css/                       # Modular, high-contrast stylesheets
│   │   ├── admin.css              # Administrator control panel styling
│   │   ├── booking.css            # 4-step rental checkout flow
│   │   ├── dashboard.css          # Customer portal layout & statistics
│   │   ├── forms.css              # Unified form controls, inputs, and buttons
│   │   ├── global.css             # Base CSS variables, typography, and utility classes
│   │   ├── home.css               # Landing page hero, search bar, and showcase
│   │   ├── navbar.css             # Floating capsule header navigation
│   │   ├── night-market.css       # Obsidian dark-glass Night Market theme
│   │   ├── responsive.css         # Mobile & tablet viewports (768px / 480px)
│   │   └── vehicles.css           # Browse fleet filter sidebar & spec cards
│   └── img/
│       ├── night-market/          # 20 high-res collector supercar assets (car_1.jpg - car_20.jpg)
│       └── vehicles/              # 28 real production fleet vehicle images (swift.jpg, thar.jpg, etc.)
│
├── database/
│   ├── database.sql               # Master MySQL/MariaDB schema dump with seed data
│   ├── night_market_inserts.sql   # Standalone seed file for Night Market inventory
│   └── ashvkatha_fallback.sqlite  # Zero-configuration local SQLite database fallback
│
├── includes/
│   ├── auth.php                   # Authentication guards (require_login, require_admin)
│   ├── database.php               # PDO database connection with auto-reconnect and fallback
│   ├── functions.php              # Utility functions (currency formatting, sanitization, flash messages)
│   ├── header.php                 # Global HTML <head>, meta tags, and font imports
│   ├── navbar.php                 # Dynamic brand capsule navbar with active state tracking
│   ├── footer.php                 # Footer with quick links, operational hours, and copyright
│   ├── admin-sidebar.php          # Admin control panel navigation sidebar
│   └── customer-sidebar.php       # Customer portal navigation sidebar
│
├── customer/                      # Customer Portal (Authenticated)
│   ├── dashboard.php              # Overview of rentals, garage orders, and spend
│   ├── bookings.php               # Rental booking history & status filters
│   ├── booking-details.php        # Detailed voucher and schedule breakdown
│   ├── night-market-orders.php    # Acquired collector builds & transit tracking
│   ├── invoices.php               # Printable GST tax invoices
│   ├── payments.php               # Transaction logs and escrow status
│   └── profile.php                # Personal details, driving license, and contact info
│
├── admin/                         # Administrator Control Panel (Role: admin)
│   ├── dashboard.php              # KPI stat cards, revenue analytics, quick actions
│   ├── vehicles.php               # Fleet inventory catalog table
│   ├── add-vehicle.php            # Add new fleet rental vehicle form
│   ├── edit-vehicle.php           # Edit fleet vehicle specs and pricing
│   ├── bookings.php               # Manage rental reservations (approve/complete/cancel)
│   ├── night-market-vehicles.php  # Night Market stock & status toggles
│   ├── night-market-orders.php    # Acquisition deals, volume ledger, and cancellation
│   ├── customers.php              # Registered user accounts directory
│   ├── locations.php              # Fleet hub network & branch management
│   ├── discounts.php              # Coupon & promotional code management
│   ├── maintenance.php            # Service schedules and maintenance expense tracker
│   ├── payments.php               # Payment audit logs and escrow custody
│   ├── reports.php                # Business performance and fleet utilization
│   ├── employees.php              # Fleet manager and staff accounts
│   └── settings.php               # Platform parameters & operational configuration
│
├── index.php                      # Public Landing Page & Instant Search Engine
├── vehicles.php                   # Browse Fleet Catalog with multi-filter query engine
├── vehicle-details.php            # Vehicle dossier, 3-column spec boxes, and booking card
├── booking.php                    # 4-Step Rental Reservation & Billing Engine
├── night-market.php               # Underground Night Market Supercar Catalog
├── night-market-details.php       # Night Market Dossier, Exclusivity Lock & Escrow Checkout
├── about.php                      # Company vision, fleet standards, and leadership
├── contact.php                    # Multi-hub contact points and career applications
├── login.php                      # User & Administrator Authentication Gateway
├── register.php                   # Customer Account Onboarding Form
├── logout.php                     # Secure Session Destruction & Redirect
└── README.md                      # Comprehensive Technical Documentation
```

---

## 🚀 Quick Setup & Installation (XAMPP / Localhost)

### 1. Requirements
- **XAMPP** (PHP 8.0 or higher + Apache + MariaDB / MySQL)
- Web Browser (Chrome, Edge, Firefox, Safari)

### 2. Steps to Run

1. **Clone or Copy Project**:
   Copy the `ashvkatha` project folder into your XAMPP web root:
   ```text
   C:\xampp\htdocs\ashvkatha
   ```

2. **Start Apache & MySQL**:
   - Open **XAMPP Control Panel**.
   - Click **Start** for both **Apache** and **MySQL**.

3. **Import the Database**:
   - Open your browser and navigate to: `http://localhost/phpmyadmin/`
   - Click **New** in the left sidebar and create a database named: `ashvkatha_db` (Collation: `utf8mb4_general_ci`).
   - Click the **Import** tab at the top.
   - Choose the file `database/database.sql` from `C:\xampp\htdocs\ashvkatha\database\database.sql`.
   - Click **Import** at the bottom.

4. **Access the Website**:
   - Open your browser and visit:  
     **[http://localhost/ashvkatha/](http://localhost/ashvkatha/)**

> **Note**: If MySQL is unavailable or offline, the platform automatically switches to the built-in SQLite database at `database/ashvkatha_fallback.sqlite` with zero manual intervention required.

---

## 🔐 Default Demo Accounts

| Role | Email Address | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@ashvkatha.com` | `Ashvkatha@123` | Full access to `/admin/` control panel |
| **Fleet Manager** | `manager@ashvkatha.com` | `Ashvkatha@123` | Operations, maintenance, and vehicle inventory |
| **Verified Customer** | `rajesh@example.com` | `Ashvkatha@123` | Bookings, Night Market garage, and invoices |

---

## 🛡 Security & Engineering Highlights

1. **Prepared SQL Statements**: 100% of database queries execute through PDO prepared statements, mitigating SQL injection risks.
2. **Password Hashing**: User authentication utilizes PHP's `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt/Argon2).
3. **Role-Based Access Control (RBAC)**: Centralized middleware guards (`require_login()`, `require_admin()`) protect customer and administrative endpoints.
4. **Input Sanitization**: Global helper `sanitize()` prevents cross-site scripting (XSS) across all form inputs and query parameters.
5. **Single-Buyer Exclusivity Lock**: Night Market builds are atomically locked (`sold_out`) upon order confirmation to ensure 1-of-1 ownership. Deals cancelled by administrators automatically deduct acquisition volume (`-MINS`) and restore vehicle availability.

---

## 📄 License & Attribution

Developed for academic demonstration and commercial fleet operations.  
© 2026 **ASHVKATHA** — All Rights Reserved.
