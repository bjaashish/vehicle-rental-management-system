# Vehicle Rental Management System (DriveEasy Rentals)

A complete, ready-to-run web application for renting cars, SUVs, bikes, scooters, vans and trucks.
Built with **HTML5, CSS3, JavaScript, PHP 8 and MySQL** — no frameworks, no Composer, runs on XAMPP/WAMP.

---

## 1. How to run (XAMPP — 5 minutes)

1. Install **XAMPP** (https://www.apachefriends.org) and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the `vehicle-rental` folder into `C:\xampp\htdocs\` (Mac: `/Applications/XAMPP/htdocs/`).
3. Open **http://localhost/phpmyadmin** → click **Import** → choose `database.sql` → **Go**.
   (This creates the `vehicle_rental` database with tables and sample data.)
4. Open **http://localhost/vehicle-rental/** in your browser.

If your MySQL has a password, edit `config/db.php` (`DB_USER`, `DB_PASS`).

### Demo logins
| Role     | Email              | Password  |
|----------|--------------------|-----------|
| Admin    | admin@rental.com   | admin123  |
| Customer | user@rental.com    | user123   |

---

## 2. Features & how each required concept is demonstrated

| Concept | Where it is demonstrated |
|---|---|
| **HTML5** | Semantic tags (`header`, `nav`, `main`, `article`, `aside`, `footer`), HTML5 inputs (`date`, `email`, `tel`, `number`, `search`), ARIA labels |
| **CSS3** | `assets/css/style.css` — CSS variables, Flexbox, CSS Grid, gradients, transitions, hover effects, shadows |
| **Responsive Web Design** | Media queries at 1100/992/860/768/520px, mobile hamburger menu, fluid grids, scrollable tables |
| **Forms & Form Validation** | Client-side: `assets/js/main.js` (reusable `data-rules` validator — required, email, phone, min/max, password match, date rules, registration-number format). Server-side: every PHP form re-validates all input |
| **JavaScript** | Validation, live booking price calculator, mobile menu, confirm dialogs, instant table search, image preview, auto-submit filters, auto-hide alerts |
| **PHP** | All server logic: sessions, auth, CRUD, uploads, validation |
| **User Authentication** | `register.php`, `login.php`, `logout.php` — `password_hash`/`password_verify`, session regeneration, role-based access (user/admin), protected pages |
| **Navigation** | Sticky responsive navbar with active-page highlight, role-aware menus, breadcrumbs, pagination |
| **Data Management** | Bookings with overlap/availability checks, server-side price calculation, status workflow (pending → approved → completed / rejected / cancelled), dashboard statistics, contact messages |
| **CRUD Operations** | Vehicles: Create/Read/Update/Delete (`admin/vehicle_form.php`, `admin/vehicles.php`). Bookings: create (customer), update status / cancel / delete. Users: register, update profile/password, change role, delete. Messages: create, mark read, delete |
| **Database Integration** | MySQL via PDO with prepared statements, foreign keys with cascading deletes, aggregate queries (`COUNT`, `SUM`, `GROUP BY`, joins) |
| **Search & Filtering** | Vehicles page: keyword search + type, fuel, gearbox, max price, sorting, pagination. Admin: search/filter vehicles, bookings (status + date range), users (role) + instant JS table filter |
| **Security** | Prepared statements (SQL injection), `htmlspecialchars` output escaping (XSS), CSRF tokens on every form, hashed passwords, safe image uploads (MIME check, size limit, random names), `.htaccess` blocking scripts in `uploads/` |

---

## 3. Folder structure

```
vehicle-rental/
├── index.php            Home page (hero search, stats, featured vehicles)
├── vehicles.php         Browse + search + filter + sort + pagination
├── vehicle.php          Vehicle details + booking form
├── register.php         Sign up
├── login.php            Log in
├── logout.php           Log out
├── my_bookings.php      Customer bookings (filter, cancel)
├── profile.php          Edit profile, change password
├── contact.php          Contact form (saved to DB)
├── database.sql         Database schema + sample data
├── config/db.php        DB connection & settings
├── includes/            header, footer, helpers, vehicle card
├── admin/
│   ├── index.php        Dashboard (stats, recent bookings, chart, messages)
│   ├── vehicles.php     List / search / delete / toggle status
│   ├── vehicle_form.php Add & edit vehicle (with photo upload)
│   ├── bookings.php     Approve / reject / complete / delete, filters
│   └── users.php        List / search / change role / delete
├── assets/css/style.css
├── assets/js/main.js
└── uploads/             Vehicle photos
```

## 4. Database tables
- **users** — id, name, email (unique), phone, password (hashed), role, created_at
- **vehicles** — id, name, brand, type, fuel, transmission, seats, reg_number (unique), price_per_day, image, description, status
- **bookings** — id, user_id → users, vehicle_id → vehicles, start_date, end_date, total_days, total_amount, pickup_location, status
- **messages** — id, name, email, subject, message, is_read

## 5. Typical flow to demo
1. Browse vehicles, try search/filters.
2. Register a new account (try invalid inputs to see validation).
3. Book a vehicle — watch the total update live; try overlapping dates with booking #1 on the Swift to see the conflict check.
4. Log in as admin → approve the booking, add a vehicle with a photo, edit it, toggle maintenance, delete.
5. Back as customer → My Bookings shows the new status.

Requirements: PHP 7.4+ (tested on PHP 8.3), MySQL 5.7+/MariaDB 10.3+.
