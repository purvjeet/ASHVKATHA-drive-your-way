<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Database Connection Helper using PDO with auto-schema creation support.
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'ashvkatha_db');

function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Try connecting to MySQL directly
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 1,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If database doesn't exist, try connecting to MySQL without dbname and create it
        try {
            $raw_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $raw_pdo = new PDO($raw_dsn, DB_USER, DB_PASS, [PDO::ATTR_TIMEOUT => 1]);
            $raw_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now connect to the newly created database
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Execute schema if sql file exists
            $sqlFile = __DIR__ . '/../database/database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
            return $pdo;
        } catch (PDOException $ex) {
            // Fallback to SQLite in-memory / local file for zero-setup demo environments
            $sqlitePath = __DIR__ . '/../database/ashvkatha_fallback.sqlite';
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Auto initialize SQLite tables if sqlite file is empty or new
            initialize_sqlite_fallback($pdo);
            return $pdo;
        }
    }
}

function initialize_sqlite_fallback($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            phone TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'customer',
            driving_license TEXT,
            address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS vehicle_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            icon TEXT NOT NULL,
            description TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            city_name TEXT NOT NULL,
            location_address TEXT NOT NULL,
            contact_phone TEXT NOT NULL,
            status TEXT DEFAULT 'active'
        );

        CREATE TABLE IF NOT EXISTS vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            location_id INTEGER NOT NULL,
            brand TEXT NOT NULL,
            model TEXT NOT NULL,
            year INTEGER NOT NULL,
            reg_number TEXT NOT NULL UNIQUE,
            fuel_type TEXT NOT NULL,
            transmission TEXT NOT NULL,
            seating_capacity INTEGER NOT NULL,
            daily_rate REAL NOT NULL,
            hourly_rate REAL NOT NULL,
            deposit_amount REAL NOT NULL,
            status TEXT DEFAULT 'available',
            insurance_expiry DATE DEFAULT '2026-12-31',
            puc_expiry DATE DEFAULT '2026-12-31',
            image_url TEXT NOT NULL,
            mileage TEXT NOT NULL,
            description TEXT NOT NULL,
            features TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            booking_code TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            vehicle_id INTEGER NOT NULL,
            pickup_location_id INTEGER NOT NULL,
            dropoff_location_id INTEGER NOT NULL,
            pickup_datetime DATETIME NOT NULL,
            return_datetime DATETIME NOT NULL,
            total_days INTEGER NOT NULL,
            base_amount REAL NOT NULL,
            extra_charges REAL DEFAULT 0,
            discount_amount REAL DEFAULT 0,
            tax_amount REAL DEFAULT 0,
            deposit_amount REAL DEFAULT 0,
            total_amount REAL NOT NULL,
            status TEXT DEFAULT 'confirmed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            booking_id INTEGER NOT NULL,
            transaction_id TEXT NOT NULL UNIQUE,
            payment_method TEXT NOT NULL,
            amount REAL NOT NULL,
            payment_status TEXT DEFAULT 'completed',
            payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS maintenance_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_id INTEGER NOT NULL,
            service_type TEXT NOT NULL,
            service_date DATE NOT NULL,
            next_service_date DATE NOT NULL,
            cost REAL NOT NULL,
            description TEXT NOT NULL,
            status TEXT DEFAULT 'scheduled'
        );

        CREATE TABLE IF NOT EXISTS discounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            promo_code TEXT NOT NULL UNIQUE,
            discount_type TEXT NOT NULL,
            discount_value REAL NOT NULL,
            min_booking_amount REAL DEFAULT 0,
            expiry_date DATE NOT NULL,
            status TEXT DEFAULT 'active'
        );

        CREATE TABLE IF NOT EXISTS night_market (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_name TEXT NOT NULL,
            brand TEXT NOT NULL,
            fuel_type TEXT NOT NULL,
            subtitle TEXT NOT NULL,
            category TEXT NOT NULL,
            badge_type TEXT NOT NULL DEFAULT 'Non-Road-Legal',
            daily_rate REAL NOT NULL,
            image_url TEXT NOT NULL,
            specs_json TEXT NOT NULL,
            features TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'available',
            is_returning INTEGER DEFAULT 0,
            is_featured INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS night_market_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_code TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            car_id INTEGER NOT NULL,
            buyer_name TEXT NOT NULL,
            buyer_email TEXT NOT NULL,
            buyer_phone TEXT NOT NULL,
            delivery_option TEXT NOT NULL,
            delivery_address TEXT NOT NULL,
            acquisition_price REAL NOT NULL,
            tax_amount REAL DEFAULT 0.00,
            total_amount REAL NOT NULL,
            payment_method TEXT NOT NULL,
            payment_status TEXT DEFAULT 'completed',
            order_status TEXT DEFAULT 'confirmed',
            transaction_id TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed night market if empty
    $nm_count = $pdo->query("SELECT COUNT(*) FROM night_market")->fetchColumn();
    if ($nm_count == 0) {
        $nm_file = __DIR__ . '/../database/night_market_inserts.sql';
        if (file_exists($nm_file)) {
            $lines = array_filter(array_map('trim', explode("\n", file_get_contents($nm_file))));
            foreach ($lines as $line) {
                if (!empty($line)) $pdo->exec($line);
            }
        }
    }

    // Seed data for SQLite if empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO users (id, full_name, email, password, phone, role, driving_license, address) VALUES
            (1, 'System Administrator', 'admin@ashvkatha.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98765 43210', 'admin', 'DL-ADMIN-001', 'HQ Ashvkatha Tower, Junagadh'),
            (2, 'Fleet Manager', 'manager@ashvkatha.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98765 43211', 'manager', 'DL-MGR-002', 'Fleet Hub 1, Ahmedabad'),
            (3, 'Rajesh Kumar', 'rajesh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 91234 56789', 'customer', 'DL-GJ03-2022091', '12 Ring Road, Rajkot'),
            (4, 'Priya Patel', 'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 99887 76655', 'customer', 'DL-GJ01-2023042', '45 Satellite, Ahmedabad');

            INSERT INTO vehicle_categories (id, name, slug, icon, description) VALUES
            (1, 'Hatchback', 'hatchback', 'car-rear', 'Agile, economic city hatchbacks ideal for easy parking & daily commutes.'),
            (2, 'Sedan', 'sedan', 'car-side', 'Refined executive sedans offering smooth rides & spacious comfort.'),
            (3, 'SUV', 'suv', 'truck-monster', 'High ground-clearance SUVs built for family trips & highway cruising.'),
            (4, '4x4 / Off-road', 'offroad', 'truck-pickup', 'Rugged 4WD vehicles engineered for extreme terrains & mountain trails.'),
            (5, 'Electric', 'electric', 'bolt', 'Zero-emission smart electric vehicles with instant torque & tech features.'),
            (6, 'Hybrid', 'hybrid', 'leaf', 'Ultra-efficient strong hybrid vehicles for long range & low emissions.'),
            (7, 'Sports / Performance', 'sports', 'gauge-high', 'Exotic high-performance speedsters delivering pure driving passion.'),
            (8, 'MPV / 7-Seater', 'mpv', 'van-shuttle', 'Spacious 7-8 seater MPVs ideal for large family vacations & group tours.');

            INSERT INTO locations (id, city_name, location_address, contact_phone, status) VALUES
            (1, 'Ahmedabad', 'SG Highway Premium Lounge, Bodakdev, Ahmedabad', '+91 79 2400333', 'active'),
            (2, 'Rajkot', 'Airport Road Terminal, Kalawad Road, Rajkot', '+91 281 2300222', 'active'),
            (3, 'Gandhinagar', 'Infocity Hub, Sector 07, Gandhinagar', '+91 79 23220555', 'active'),
            (4, 'Junagadh', 'Ashvkatha Central Hub, Gir Road, Junagadh', '+91 285 2200111', 'active'),
            (5, 'Amreli', 'Station Road Hub, Near Circle, Amreli', '+91 2792 220044', 'active'),
            (6, 'Surendranagar', 'Highway Express Hub, Surendranagar', '+91 2752 230055', 'active');

            INSERT INTO vehicles (id, category_id, location_id, brand, model, year, reg_number, fuel_type, transmission, seating_capacity, daily_rate, hourly_rate, deposit_amount, status, insurance_expiry, puc_expiry, image_url, mileage, description, features) VALUES
            (1, 1, 1, 'Maruti', 'Swift ZXi', 2025, 'GJ-01-RK-1000', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (2, 1, 1, 'Hyundai', 'i20 Asta', 2024, 'GJ-01-RK-1001', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'rented', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (3, 1, 1, 'Tata', 'Altroz XZ', 2025, 'GJ-01-RK-1002', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2025) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (4, 1, 1, 'Maruti', 'Baleno Alpha', 2024, 'GJ-01-RK-1003', 'Petrol', 'Automatic', 5, 2300, 280, 4500, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=1000&q=80', '22.9 kmpl', 'Maruti Baleno Alpha (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Heads-Up Display, 360 Camera, 9-inch SmartPlay Touchscreen'),
            (5, 1, 1, 'Tata', 'Tiago XZ+', 2025, 'GJ-01-RK-1004', 'Petrol', 'Automatic', 5, 2000, 240, 3500, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '20.0 kmpl', 'Tata Tiago XZ+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Harman Sound System, Rear Camera, Automatic Climate Control'),
            (6, 1, 1, 'Maruti', 'Swift ZXi', 2024, 'GJ-01-RK-1005', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (7, 2, 1, 'Maruti', 'Dzire ZXi+', 2025, 'GJ-01-RK-1006', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'reserved', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (8, 2, 1, 'Honda', 'Amaze VX', 2024, 'GJ-01-RK-1007', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (9, 2, 1, 'Honda', 'City ZX', 2025, 'GJ-01-RK-1008', 'Petrol', 'Automatic', 5, 4000, 480, 7000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1000&q=80', '17.8 kmpl', 'Honda City ZX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, ADAS Level 2, LaneWatch Camera, Leather Seats'),
            (10, 2, 1, 'Hyundai', 'Verna SX Turbo', 2024, 'GJ-01-RK-1009', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'maintenance', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1619682817481-e994b7d45599?auto=format&fit=crop&w=1000&q=80', '18.5 kmpl', 'Hyundai Verna SX Turbo (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Ventilated Front Seats, Bose 8-Speaker Audio, Dual Screen Layout'),
            (11, 2, 1, 'Skoda', 'Slavia Style', 2025, 'GJ-01-RK-1010', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1000&q=80', '19.4 kmpl', 'Skoda Slavia Style (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Virtual Cockpit, Electric Sunroof, Wireless Apple CarPlay'),
            (12, 2, 1, 'Maruti', 'Dzire ZXi+', 2024, 'GJ-01-RK-1011', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (13, 2, 1, 'Honda', 'Amaze VX', 2025, 'GJ-01-RK-1012', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (14, 3, 1, 'Tata', 'Nexon Fearless', 2024, 'GJ-01-RK-1013', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2024) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (15, 3, 1, 'Maruti', 'Brezza ZXi+', 2025, 'GJ-01-RK-1014', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (16, 3, 1, 'Hyundai', 'Creta SX(O)', 2024, 'GJ-01-RK-1015', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (17, 3, 1, 'Kia', 'Seltos GTX+', 2025, 'GJ-01-RK-1016', 'Petrol', 'Automatic', 5, 6000, 720, 10000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&w=1000&q=80', '17.0 kmpl', 'Kia Seltos GTX+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Dual Panoramic Display, Smart Air Purifier, Head-Up Display'),
            (18, 3, 1, 'Maruti', 'Grand Vitara Alpha', 2024, 'GJ-01-RK-1017', 'Petrol', 'Automatic', 5, 5200, 620, 9000, 'rented', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '21.1 kmpl', 'Maruti Grand Vitara Alpha (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, ALLGRIP AWD option, Wireless Charging'),
            (19, 3, 1, 'Tata', 'Nexon Fearless', 2025, 'GJ-01-RK-1018', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2025) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (20, 3, 1, 'Maruti', 'Brezza ZXi+', 2024, 'GJ-01-RK-1019', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (21, 3, 1, 'Hyundai', 'Creta SX(O)', 2025, 'GJ-01-RK-1020', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (22, 4, 1, 'Mahindra', 'Thar 4x4 LX', 2024, 'GJ-01-RK-1021', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (23, 4, 1, 'Maruti', 'Jimny Alpha 4x4', 2025, 'GJ-01-RK-1022', 'Petrol', 'Automatic', 4, 4800, 580, 8000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=1000&q=80', '16.9 kmpl', 'Maruti Jimny Alpha 4x4 (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'ALLGRIP PRO 4x4, Brake LSD, Touchscreen Navigation, Compact Trail Specialist'),
            (24, 5, 1, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-01-RK-1023', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'reserved', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (25, 5, 1, 'Tata', 'Punch EV Empowered', 2025, 'GJ-01-RK-1024', 'Electric', 'Automatic', 5, 3500, 420, 6000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '421 km Range', 'Tata Punch EV Empowered (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'act.ev Architecture, 10.25-inch Touchscreen, Paddle Regenerative Braking'),
            (26, 5, 1, 'Tata', 'Tiago EV Tech Lux', 2024, 'GJ-01-RK-1025', 'Electric', 'Automatic', 5, 2800, 340, 5000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '315 km Range', 'Tata Tiago EV Tech Lux (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Ziptron EV Tech, Cruise Control, Leatherette Seats, Fast Charge Supported'),
            (27, 5, 1, 'Mahindra', 'XUV400 EV EL Pro', 2025, 'GJ-01-RK-1026', 'Electric', 'Automatic', 5, 4000, 480, 7500, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=1000&q=80', '456 km Range', 'Mahindra XUV400 EV EL Pro (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'FunFast EV Performance, Dual Zone Climate Control, 6 Airbags'),
            (28, 5, 1, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-01-RK-1027', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (29, 6, 1, 'Maruti', 'Grand Vitara Strong Hybrid', 2025, 'GJ-01-RK-1028', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (30, 6, 1, 'Toyota', 'Hyryder Strong Hybrid', 2024, 'GJ-01-RK-1029', 'Hybrid', 'Automatic', 5, 5200, 620, 9000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Toyota Hyryder Strong Hybrid (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'e-CVT Transmission, Ventilated Seats, 360 View Monitor, Toyota i-Connect'),
            (31, 6, 1, 'Maruti', 'Grand Vitara Strong Hybrid', 2025, 'GJ-01-RK-1030', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (32, 7, 1, 'Ford', 'Mustang GT 5.0 V8', 2024, 'GJ-01-RK-1031', 'Petrol', 'Automatic', 4, 15000, 1800, 25000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?auto=format&fit=crop&w=1000&q=80', '8.5 kmpl', 'Ford Mustang GT 5.0 V8 (2024) offering superior comfort, safety, and driving performance across Gujarat.', '450 HP V8 Engine, Line Lock Track Mode, B&O Audio, Quad Exhaust'),
            (33, 7, 1, 'Toyota', 'GR Supra 3.0', 2025, 'GJ-01-RK-1032', 'Petrol', 'Automatic', 2, 18000, 2200, 30000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1000&q=80', '11.2 kmpl', 'Toyota GR Supra 3.0 (2025) offering superior comfort, safety, and driving performance across Gujarat.', '382 HP Inline-6 Turbo, Adaptive Variable Suspension, Active Differential'),
            (34, 8, 1, 'Maruti', 'Ertiga ZXi+', 2024, 'GJ-01-RK-1033', 'Petrol', 'Manual', 7, 3800, 450, 6000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '20.5 kmpl', 'Maruti Ertiga ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Spacious 7-Seater, Smart Hybrid Tech, Roof AC Vents, Push Button Start'),
            (35, 8, 1, 'Kia', 'Carens Luxury Plus', 2025, 'GJ-01-RK-1034', 'Diesel', 'Automatic', 7, 4500, 540, 7500, 'rented', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '18.2 kmpl', 'Kia Carens Luxury Plus (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Bose 8-Speaker Audio, One-Touch Electric Tumble Seats, SkyLight Sunroof'),
            (36, 1, 2, 'Maruti', 'Swift ZXi', 2024, 'GJ-03-RK-1035', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (37, 1, 2, 'Hyundai', 'i20 Asta', 2025, 'GJ-03-RK-1036', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (38, 1, 2, 'Tata', 'Altroz XZ', 2024, 'GJ-03-RK-1037', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2024) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (39, 1, 2, 'Maruti', 'Baleno Alpha', 2025, 'GJ-03-RK-1038', 'Petrol', 'Automatic', 5, 2300, 280, 4500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=1000&q=80', '22.9 kmpl', 'Maruti Baleno Alpha (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Heads-Up Display, 360 Camera, 9-inch SmartPlay Touchscreen'),
            (40, 1, 2, 'Tata', 'Tiago XZ+', 2024, 'GJ-03-RK-1039', 'Petrol', 'Automatic', 5, 2000, 240, 3500, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '20.0 kmpl', 'Tata Tiago XZ+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Harman Sound System, Rear Camera, Automatic Climate Control'),
            (41, 1, 2, 'Maruti', 'Swift ZXi', 2025, 'GJ-03-RK-1040', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (42, 2, 2, 'Maruti', 'Dzire ZXi+', 2024, 'GJ-03-RK-1041', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'rented', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (43, 2, 2, 'Honda', 'Amaze VX', 2025, 'GJ-03-RK-1042', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (44, 2, 2, 'Honda', 'City ZX', 2024, 'GJ-03-RK-1043', 'Petrol', 'Automatic', 5, 4000, 480, 7000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1000&q=80', '17.8 kmpl', 'Honda City ZX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, ADAS Level 2, LaneWatch Camera, Leather Seats'),
            (45, 2, 2, 'Hyundai', 'Verna SX Turbo', 2025, 'GJ-03-RK-1044', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1619682817481-e994b7d45599?auto=format&fit=crop&w=1000&q=80', '18.5 kmpl', 'Hyundai Verna SX Turbo (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Ventilated Front Seats, Bose 8-Speaker Audio, Dual Screen Layout'),
            (46, 2, 2, 'Skoda', 'Slavia Style', 2024, 'GJ-03-RK-1045', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1000&q=80', '19.4 kmpl', 'Skoda Slavia Style (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Virtual Cockpit, Electric Sunroof, Wireless Apple CarPlay'),
            (47, 3, 2, 'Tata', 'Nexon Fearless', 2025, 'GJ-03-RK-1046', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2025) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (48, 3, 2, 'Maruti', 'Brezza ZXi+', 2024, 'GJ-03-RK-1047', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (49, 3, 2, 'Hyundai', 'Creta SX(O)', 2025, 'GJ-03-RK-1048', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (50, 3, 2, 'Kia', 'Seltos GTX+', 2024, 'GJ-03-RK-1049', 'Petrol', 'Automatic', 5, 6000, 720, 10000, 'maintenance', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&w=1000&q=80', '17.0 kmpl', 'Kia Seltos GTX+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Dual Panoramic Display, Smart Air Purifier, Head-Up Display'),
            (51, 3, 2, 'Maruti', 'Grand Vitara Alpha', 2025, 'GJ-03-RK-1050', 'Petrol', 'Automatic', 5, 5200, 620, 9000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '21.1 kmpl', 'Maruti Grand Vitara Alpha (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, ALLGRIP AWD option, Wireless Charging'),
            (52, 3, 2, 'Tata', 'Nexon Fearless', 2024, 'GJ-03-RK-1051', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2024) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (53, 4, 2, 'Mahindra', 'Thar 4x4 LX', 2025, 'GJ-03-RK-1052', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (54, 4, 2, 'Maruti', 'Jimny Alpha 4x4', 2024, 'GJ-03-RK-1053', 'Petrol', 'Automatic', 4, 4800, 580, 8000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=1000&q=80', '16.9 kmpl', 'Maruti Jimny Alpha 4x4 (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'ALLGRIP PRO 4x4, Brake LSD, Touchscreen Navigation, Compact Trail Specialist'),
            (55, 4, 2, 'Mahindra', 'Scorpio-N 4x4 Z8L', 2025, 'GJ-03-RK-1054', 'Diesel', 'Automatic', 7, 7500, 900, 12000, 'reserved', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '14.0 kmpl', 'Mahindra Scorpio-N 4x4 Z8L (2025) offering superior comfort, safety, and driving performance across Gujarat.', '4EXPLOR Terrain Modes, Sony 12-Speaker 3D Sound, Captain Seats'),
            (56, 5, 2, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-03-RK-1055', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (57, 5, 2, 'Tata', 'Punch EV Empowered', 2025, 'GJ-03-RK-1056', 'Electric', 'Automatic', 5, 3500, 420, 6000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '421 km Range', 'Tata Punch EV Empowered (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'act.ev Architecture, 10.25-inch Touchscreen, Paddle Regenerative Braking'),
            (58, 5, 2, 'Tata', 'Tiago EV Tech Lux', 2024, 'GJ-03-RK-1057', 'Electric', 'Automatic', 5, 2800, 340, 5000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '315 km Range', 'Tata Tiago EV Tech Lux (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Ziptron EV Tech, Cruise Control, Leatherette Seats, Fast Charge Supported'),
            (59, 6, 2, 'Maruti', 'Grand Vitara Strong Hybrid', 2025, 'GJ-03-RK-1058', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (60, 7, 2, 'Ford', 'Mustang GT 5.0 V8', 2024, 'GJ-03-RK-1059', 'Petrol', 'Automatic', 4, 15000, 1800, 25000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?auto=format&fit=crop&w=1000&q=80', '8.5 kmpl', 'Ford Mustang GT 5.0 V8 (2024) offering superior comfort, safety, and driving performance across Gujarat.', '450 HP V8 Engine, Line Lock Track Mode, B&O Audio, Quad Exhaust'),
            (61, 8, 2, 'Maruti', 'Ertiga ZXi+', 2025, 'GJ-03-RK-1060', 'Petrol', 'Manual', 7, 3800, 450, 6000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '20.5 kmpl', 'Maruti Ertiga ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Spacious 7-Seater, Smart Hybrid Tech, Roof AC Vents, Push Button Start'),
            (62, 8, 2, 'Kia', 'Carens Luxury Plus', 2024, 'GJ-03-RK-1061', 'Diesel', 'Automatic', 7, 4500, 540, 7500, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '18.2 kmpl', 'Kia Carens Luxury Plus (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Bose 8-Speaker Audio, One-Touch Electric Tumble Seats, SkyLight Sunroof'),
            (63, 1, 3, 'Maruti', 'Swift ZXi', 2025, 'GJ-06-RK-1062', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (64, 1, 3, 'Hyundai', 'i20 Asta', 2024, 'GJ-06-RK-1063', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (65, 1, 3, 'Tata', 'Altroz XZ', 2025, 'GJ-06-RK-1064', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2025) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (66, 1, 3, 'Maruti', 'Baleno Alpha', 2024, 'GJ-06-RK-1065', 'Petrol', 'Automatic', 5, 2300, 280, 4500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=1000&q=80', '22.9 kmpl', 'Maruti Baleno Alpha (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Heads-Up Display, 360 Camera, 9-inch SmartPlay Touchscreen'),
            (67, 2, 3, 'Maruti', 'Dzire ZXi+', 2025, 'GJ-06-RK-1066', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (68, 2, 3, 'Honda', 'Amaze VX', 2024, 'GJ-06-RK-1067', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'rented', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (69, 2, 3, 'Honda', 'City ZX', 2025, 'GJ-06-RK-1068', 'Petrol', 'Automatic', 5, 4000, 480, 7000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1000&q=80', '17.8 kmpl', 'Honda City ZX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, ADAS Level 2, LaneWatch Camera, Leather Seats'),
            (70, 2, 3, 'Hyundai', 'Verna SX Turbo', 2024, 'GJ-06-RK-1069', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1619682817481-e994b7d45599?auto=format&fit=crop&w=1000&q=80', '18.5 kmpl', 'Hyundai Verna SX Turbo (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Ventilated Front Seats, Bose 8-Speaker Audio, Dual Screen Layout'),
            (71, 2, 3, 'Skoda', 'Slavia Style', 2025, 'GJ-06-RK-1070', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1000&q=80', '19.4 kmpl', 'Skoda Slavia Style (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Virtual Cockpit, Electric Sunroof, Wireless Apple CarPlay'),
            (72, 3, 3, 'Tata', 'Nexon Fearless', 2024, 'GJ-06-RK-1071', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2024) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (73, 3, 3, 'Maruti', 'Brezza ZXi+', 2025, 'GJ-06-RK-1072', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (74, 3, 3, 'Hyundai', 'Creta SX(O)', 2024, 'GJ-06-RK-1073', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (75, 3, 3, 'Kia', 'Seltos GTX+', 2025, 'GJ-06-RK-1074', 'Petrol', 'Automatic', 5, 6000, 720, 10000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&w=1000&q=80', '17.0 kmpl', 'Kia Seltos GTX+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Dual Panoramic Display, Smart Air Purifier, Head-Up Display'),
            (76, 3, 3, 'Maruti', 'Grand Vitara Alpha', 2024, 'GJ-06-RK-1075', 'Petrol', 'Automatic', 5, 5200, 620, 9000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '21.1 kmpl', 'Maruti Grand Vitara Alpha (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, ALLGRIP AWD option, Wireless Charging'),
            (77, 4, 3, 'Mahindra', 'Thar 4x4 LX', 2025, 'GJ-06-RK-1076', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (78, 5, 3, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-06-RK-1077', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (79, 5, 3, 'Tata', 'Punch EV Empowered', 2025, 'GJ-06-RK-1078', 'Electric', 'Automatic', 5, 3500, 420, 6000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '421 km Range', 'Tata Punch EV Empowered (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'act.ev Architecture, 10.25-inch Touchscreen, Paddle Regenerative Braking'),
            (80, 5, 3, 'Tata', 'Tiago EV Tech Lux', 2024, 'GJ-06-RK-1079', 'Electric', 'Automatic', 5, 2800, 340, 5000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '315 km Range', 'Tata Tiago EV Tech Lux (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Ziptron EV Tech, Cruise Control, Leatherette Seats, Fast Charge Supported'),
            (81, 6, 3, 'Maruti', 'Grand Vitara Strong Hybrid', 2025, 'GJ-06-RK-1080', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (82, 7, 3, 'Ford', 'Mustang GT 5.0 V8', 2024, 'GJ-06-RK-1081', 'Petrol', 'Automatic', 4, 15000, 1800, 25000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?auto=format&fit=crop&w=1000&q=80', '8.5 kmpl', 'Ford Mustang GT 5.0 V8 (2024) offering superior comfort, safety, and driving performance across Gujarat.', '450 HP V8 Engine, Line Lock Track Mode, B&O Audio, Quad Exhaust'),
            (83, 8, 3, 'Maruti', 'Ertiga ZXi+', 2025, 'GJ-06-RK-1082', 'Petrol', 'Manual', 7, 3800, 450, 6000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '20.5 kmpl', 'Maruti Ertiga ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Spacious 7-Seater, Smart Hybrid Tech, Roof AC Vents, Push Button Start'),
            (84, 8, 3, 'Kia', 'Carens Luxury Plus', 2024, 'GJ-06-RK-1083', 'Diesel', 'Automatic', 7, 4500, 540, 7500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '18.2 kmpl', 'Kia Carens Luxury Plus (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Bose 8-Speaker Audio, One-Touch Electric Tumble Seats, SkyLight Sunroof'),
            (85, 1, 4, 'Maruti', 'Swift ZXi', 2025, 'GJ-11-RK-1084', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (86, 1, 4, 'Hyundai', 'i20 Asta', 2024, 'GJ-11-RK-1085', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (87, 1, 4, 'Tata', 'Altroz XZ', 2025, 'GJ-11-RK-1086', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2025) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (88, 2, 4, 'Maruti', 'Dzire ZXi+', 2024, 'GJ-11-RK-1087', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'rented', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (89, 2, 4, 'Honda', 'Amaze VX', 2025, 'GJ-11-RK-1088', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (90, 3, 4, 'Tata', 'Nexon Fearless', 2024, 'GJ-11-RK-1089', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2024) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (91, 3, 4, 'Maruti', 'Brezza ZXi+', 2025, 'GJ-11-RK-1090', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'reserved', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (92, 3, 4, 'Hyundai', 'Creta SX(O)', 2024, 'GJ-11-RK-1091', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (93, 4, 4, 'Mahindra', 'Thar 4x4 LX', 2025, 'GJ-11-RK-1092', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (94, 4, 4, 'Maruti', 'Jimny Alpha 4x4', 2024, 'GJ-11-RK-1093', 'Petrol', 'Automatic', 4, 4800, 580, 8000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=1000&q=80', '16.9 kmpl', 'Maruti Jimny Alpha 4x4 (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'ALLGRIP PRO 4x4, Brake LSD, Touchscreen Navigation, Compact Trail Specialist'),
            (95, 5, 4, 'Tata', 'Nexon EV Empowered', 2025, 'GJ-11-RK-1094', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (96, 6, 4, 'Maruti', 'Grand Vitara Strong Hybrid', 2024, 'GJ-11-RK-1095', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (97, 8, 4, 'Maruti', 'Ertiga ZXi+', 2025, 'GJ-11-RK-1096', 'Petrol', 'Manual', 7, 3800, 450, 6000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '20.5 kmpl', 'Maruti Ertiga ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Spacious 7-Seater, Smart Hybrid Tech, Roof AC Vents, Push Button Start'),
            (98, 8, 4, 'Kia', 'Carens Luxury Plus', 2024, 'GJ-11-RK-1097', 'Diesel', 'Automatic', 7, 4500, 540, 7500, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '18.2 kmpl', 'Kia Carens Luxury Plus (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Bose 8-Speaker Audio, One-Touch Electric Tumble Seats, SkyLight Sunroof'),
            (99, 8, 4, 'Toyota', 'Innova Crysta VX', 2025, 'GJ-11-RK-1098', 'Diesel', 'Manual', 8, 5500, 680, 10000, 'maintenance', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '15.6 kmpl', 'Toyota Innova Crysta VX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Legendary Comfort 8-Seater, Ambient Lighting, Eco & Power Driving Modes'),
            (100, 1, 5, 'Maruti', 'Swift ZXi', 2024, 'GJ-14-RK-1099', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (101, 1, 5, 'Hyundai', 'i20 Asta', 2025, 'GJ-14-RK-1100', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (102, 1, 5, 'Tata', 'Altroz XZ', 2024, 'GJ-14-RK-1101', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2024) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (103, 2, 5, 'Maruti', 'Dzire ZXi+', 2025, 'GJ-14-RK-1102', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (104, 2, 5, 'Honda', 'Amaze VX', 2024, 'GJ-14-RK-1103', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (105, 2, 5, 'Honda', 'City ZX', 2025, 'GJ-14-RK-1104', 'Petrol', 'Automatic', 5, 4000, 480, 7000, 'rented', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1000&q=80', '17.8 kmpl', 'Honda City ZX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, ADAS Level 2, LaneWatch Camera, Leather Seats'),
            (106, 3, 5, 'Tata', 'Nexon Fearless', 2024, 'GJ-14-RK-1105', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2024) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (107, 3, 5, 'Maruti', 'Brezza ZXi+', 2025, 'GJ-14-RK-1106', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (108, 3, 5, 'Hyundai', 'Creta SX(O)', 2024, 'GJ-14-RK-1107', 'Diesel', 'Automatic', 5, 5800, 700, 10000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1000&q=80', '19.1 kmpl', 'Hyundai Creta SX(O) (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Panoramic Sunroof, Bose Premium Sound, ADAS Level 2, Ventilated Seats'),
            (109, 4, 5, 'Mahindra', 'Thar 4x4 LX', 2025, 'GJ-14-RK-1108', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (110, 5, 5, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-14-RK-1109', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (111, 6, 5, 'Maruti', 'Grand Vitara Strong Hybrid', 2025, 'GJ-14-RK-1110', 'Hybrid', 'Automatic', 5, 4800, 580, 8500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '27.9 kmpl', 'Maruti Grand Vitara Strong Hybrid (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Self-Charging Strong Hybrid, EV Mode Drive, Panoramic Sunroof, HUD'),
            (112, 8, 5, 'Maruti', 'Ertiga ZXi+', 2024, 'GJ-14-RK-1111', 'Petrol', 'Manual', 7, 3800, 450, 6000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '20.5 kmpl', 'Maruti Ertiga ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Spacious 7-Seater, Smart Hybrid Tech, Roof AC Vents, Push Button Start'),
            (113, 8, 5, 'Kia', 'Carens Luxury Plus', 2025, 'GJ-14-RK-1112', 'Diesel', 'Automatic', 7, 4500, 540, 7500, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80', '18.2 kmpl', 'Kia Carens Luxury Plus (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Bose 8-Speaker Audio, One-Touch Electric Tumble Seats, SkyLight Sunroof'),
            (114, 1, 6, 'Maruti', 'Swift ZXi', 2024, 'GJ-13-RK-1113', 'Petrol', 'Manual', 5, 2000, 250, 4000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80', '22.5 kmpl', 'Maruti Swift ZXi (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Keyless Entry, Touchscreen, Dual Airbags, ABS'),
            (115, 1, 6, 'Hyundai', 'i20 Asta', 2025, 'GJ-13-RK-1114', 'Petrol', 'Automatic', 5, 2200, 270, 4000, 'available', '2027-08-15', '2026-11-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Hyundai i20 Asta (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Bose Audio, Wireless Charger, Digital Cluster'),
            (116, 1, 6, 'Tata', 'Altroz XZ', 2024, 'GJ-13-RK-1115', 'Diesel', 'Manual', 5, 2100, 260, 4000, 'available', '2027-09-15', '2026-12-20', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=1000&q=80', '23.6 kmpl', 'Tata Altroz XZ (2024) offering superior comfort, safety, and driving performance across Gujarat.', '5-Star Global NCAP, Ambient Lighting, Cruise Control'),
            (117, 2, 6, 'Maruti', 'Dzire ZXi+', 2025, 'GJ-13-RK-1116', 'Petrol', 'Automatic', 5, 3000, 360, 5000, 'available', '2027-01-15', '2026-10-20', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80', '24.1 kmpl', 'Maruti Dzire ZXi+ (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Rear AC Vents, Push Start, SmartPlay Infotainment'),
            (118, 2, 6, 'Honda', 'Amaze VX', 2024, 'GJ-13-RK-1117', 'Petrol', 'Automatic', 5, 3200, 380, 5000, 'available', '2027-02-15', '2026-11-20', 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1000&q=80', '18.6 kmpl', 'Honda Amaze VX (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'CVT Transmission, Paddle Shifters, LED Projector Headlamps'),
            (119, 3, 6, 'Tata', 'Nexon Fearless', 2025, 'GJ-13-RK-1118', 'Petrol', 'Automatic', 5, 4500, 540, 8000, 'available', '2027-03-15', '2026-12-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '17.4 kmpl', 'Tata Nexon Fearless (2025) offering superior comfort, safety, and driving performance across Gujarat.', '10.25-inch Touchscreen, JBL Audio, 360 Camera, Air Purifier'),
            (120, 3, 6, 'Maruti', 'Brezza ZXi+', 2024, 'GJ-13-RK-1119', 'Petrol', 'Automatic', 5, 4200, 500, 7500, 'available', '2027-04-15', '2026-10-20', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1000&q=80', '19.8 kmpl', 'Maruti Brezza ZXi+ (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Sunroof, Heads-Up Display, 360 View, Arkamys Surround Sound'),
            (121, 4, 6, 'Mahindra', 'Thar 4x4 LX', 2025, 'GJ-13-RK-1120', 'Diesel', 'Manual', 4, 5500, 650, 10000, 'available', '2027-05-15', '2026-11-20', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1000&q=80', '13.0 kmpl', 'Mahindra Thar 4x4 LX (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'Shift-on-the-fly 4x4, Mechanical Locking Diff, Hard Top Convertible'),
            (122, 5, 6, 'Tata', 'Nexon EV Empowered', 2024, 'GJ-13-RK-1121', 'Electric', 'Automatic', 5, 4200, 500, 8000, 'available', '2027-06-15', '2026-12-20', 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1000&q=80', '465 km Range', 'Tata Nexon EV Empowered (2024) offering superior comfort, safety, and driving performance across Gujarat.', 'Fast Charging, V2L / V2V Power Output, Arcade.ev App Suite'),
            (123, 5, 6, 'Tata', 'Punch EV Empowered', 2025, 'GJ-13-RK-1122', 'Electric', 'Automatic', 5, 3500, 420, 6000, 'available', '2027-07-15', '2026-10-20', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=1000&q=80', '421 km Range', 'Tata Punch EV Empowered (2025) offering superior comfort, safety, and driving performance across Gujarat.', 'act.ev Architecture, 10.25-inch Touchscreen, Paddle Regenerative Braking');

            INSERT INTO bookings (id, booking_code, user_id, vehicle_id, pickup_location_id, dropoff_location_id, pickup_datetime, return_datetime, total_days, base_amount, extra_charges, discount_amount, tax_amount, deposit_amount, total_amount, status) VALUES
            (1, 'ASHV-2026-8801', 3, 2, 1, 1, '2026-09-18 10:00:00', '2026-09-21 10:00:00', 3, 5400.00, 500.00, 500.00, 972.00, 4000.00, 6372.00, 'active'),
            (2, 'ASHV-2026-8802', 4, 1, 1, 1, '2026-09-10 09:00:00', '2026-09-12 09:00:00', 2, 2800.00, 0.00, 200.00, 468.00, 3000.00, 3068.00, 'completed');

            INSERT INTO payments (id, booking_id, transaction_id, payment_method, amount, payment_status) VALUES
            (1, 1, 'TXN-ASHV-99001', 'UPI / NetBanking', 6372.00, 'completed'),
            (2, 2, 'TXN-ASHV-99002', 'Credit Card', 3068.00, 'completed');
        ");
    }
}
