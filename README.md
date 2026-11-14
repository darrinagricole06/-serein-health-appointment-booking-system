# Serein Health — Appointment Booking System

**Website Design & Development Project — Task 1: Website Design and Bootstrap 5**

A responsive, professionally styled appointment booking website for a fictional health and
wellness clinic based at **Victoria Seychelles Hospital, Mahé**. Task 1 delivers the complete
front-end experience; booking processing and MySQL integration follow in Task 2.

---

## Run it locally (WAMP)

1. Keep the project in `C:\wamp64\www\Appointment Booking System`.
2. Start **WAMP** and make sure Apache is running. MySQL is **not** required for Task 1.
3. Open: <http://localhost/Appointment%20Booking%20System/>

**Contact details used on the site:** Victoria Seychelles Hospital, Mont Fleuri, Victoria, Mahé ·
+248 438 8000 · hello@serein.sc

There is no build step. Bootstrap, Bootstrap Icons, fonts and photography are all stored
locally, so the site also works without an internet connection.

---

## Design direction

- **Brand:** Serein Health — “Care that fits your life.” Based at Victoria Seychelles Hospital.
- **Palette:** Indian Ocean blue `#0a5273`, lagoon aqua `#128fae`, sea-foam `#e2f2f4`,
  warm cream `#fbf7ee`, Seychelles-sunset coral `#cf4a26`, sunshine gold `#f0b429`
  and palm green `#1d7a4f`.
- **Island cues:** a slim five-band ribbon at the very top echoes the Seychelles flag
  (ocean, sun, sunset, sand, palm), while the lagoon blues and sand tones keep it calm
  and clinical rather than touristy.
- **Typography:** *Fraunces* (editorial serif headings) + *Manrope* (clean interface sans-serif).
- **Style:** generous white space, oversized serif type, soft shadows, rounded cards, a custom
  leaf logomark, subtle grain/pattern textures and a calm, premium clinic feel.
- **Photography:** locally bundled clinic, consultation and portrait photography.

---

## Task 1 requirements covered

| Requirement | Where it appears |
|---|---|
| Professional, attractive Bootstrap 5 design | Whole site — custom design system in `assets/css/style.css` |
| Responsive navigation bar | Sticky `.site-nav` with Bootstrap collapse, mobile menu, scroll-spy active links, hamburger-to-X icon |
| Containers and grid layouts | Bootstrap `.container`, `.row`, `.col-*`, responsive column classes throughout |
| **Cards** | 6 service cards, 12 doctor cards, 3 patient review cards |
| **Buttons** | Brand, coral, outline, soft, ghost-light, icon buttons and text-arrow links |
| **Forms** | “Find your slot” availability form (`#find-slot`), full booking form (`#bookingForm`), footer newsletter form |
| **Alerts** | Dismissible info alert in the booking section + success alert after confirmation |
| **Modals** | Doctor profile modal (dynamic content) + per-doctor availability modal + appointment review/confirmation modal |
| **Progress bars** | "booking progress" bar + three animated clinic performance bars |
| **Image carousel** | Full-bleed hero slider with 3 slides, horizontal transition, autoplay, slide counter, indicators and prev/next controls (autoplay pauses for reduced-motion visitors) |
| Typography, images and styling | Fraunces/Manrope fonts, local photography, custom SVG logo, iconography, gradients |
| Responsive on desktop and mobile | Tested at 1440px, 1024px, 768px and 390px; collapsible nav, stacked grids, mobile ordering |

### Extra polish (beyond the minimum)

- Form completion progress updates as required fields are completed.
- “Find your slot” lives in its own section below the hero and is linked from the top bar.
  It checks every doctor for the chosen service, date and time, then shows who is free.
- If a doctor is fully booked, the checker offers their next free slot; if the whole team is
  booked, it suggests the earliest next appointment and offers to book it in one click.
- Choosing a doctor or suggested slot pre-fills the main booking form with the service,
  clinician, date and time, then scrolls to it.
- Booking form uses Bootstrap validation states and a confirmation modal summarising the request.
- After confirmation, a success alert is displayed and the form resets.
- Doctor cards open a profile modal with bio, languages, focus areas and next availability.
- “Book this service” / doctor booking buttons pre-select the service/doctor and scroll to the form.
- Animated statistics counters and progress bars trigger when scrolled into view.
- Doctor modal “Book with this doctor” sets both the doctor and matching service.
- A “What to expect at your visit” section walks patients through check-in, consultation,
  examination and their treatment plan, with preparation notes.
- Newsletter form with client-side validation and inline success state.
- Accessible details: skip link, labelled fields, alt text, ARIA attributes, keyboard focus styles,
  reduced-motion support, semantic headings.

---

## Project structure

```
Appointment Booking System/
├── index.php                  # Main page (all sections + modals)
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── process_booking.php        # Booking form → MySQL
├── process_newsletter.php     # Newsletter form → MySQL
├── process_availability.php   # Availability form handler
├── process_appointment_action.php # Doctor/admin appointment updates
├── process_admin_doctor.php   # Admin doctor account management
├── admin_bookings.php         # Admin appointment list and updates
├── admin_doctors.php          # Admin doctor account management
├── doctor_dashboard.php       # Doctor appointment dashboard
├── my_bookings.php            # User appointment history
├── includes/
│   ├── config.php
│   ├── db.php                 # MySQLi connection
│   ├── functions.php
│   └── auth.php               # Sessions and role checks
├── database/
│   ├── serein_health.sql      # Full schema + seed accounts
│   ├── serein_health_full_export.sql # Complete database export with current data
│   ├── task5_advanced.sql     # Upgrade script for older databases
│   └── task5_doctor_photos.sql # Adds doctor photos to an existing database
├── data/                      # Legacy text logs (no longer used)
├── README.md
└── assets/
    ├── css/
    │   └── style.css          # Design system, components, responsive rules
    ├── js/
    │   └── main.js            # Navigation, booking flow, modals, animations
    ├── fonts/                 # Fraunces & Manrope (local WOFF2)
    ├── img/
    │   ├── carousel/          # 3 hero slides
    │   ├── doctors/           # Doctor portraits + placeholder
    │   │   └── uploads/       # Photos uploaded by the admin
    │   ├── avatar-*.jpg       # Patient avatars
    │   ├── about-consultation.jpg
    │   ├── cta-hands.jpg
    │   ├── logo.svg
    │   └── favicon.svg
    └── vendor/
        ├── bootstrap/         # Bootstrap 5.3.3 CSS + JS bundle
        └── bootstrap-icons/   # Bootstrap Icons 1.11.3 CSS + fonts
```

---

## Backend, database and authentication (Tasks 3–5)

- The booking and newsletter forms post to `process_booking.php` and `process_newsletter.php`.
- Both handlers validate input and write to MySQL using MySQLi prepared statements.
- Database schema: `database/serein_health.sql` creates `doctors`, `appointments`,
  `newsletter_subscribers` and `users` in the `serein_health` database.
- A complete phpMyAdmin-compatible export of the current database is available at
  `database/serein_health_full_export.sql` (structure and data).
- Existing databases can be upgraded with `database/task5_advanced.sql`.
- Database connection: `includes/db.php` (MySQL at `127.0.0.1:3306`).
- Login and logout: `login.php`, `logout.php`, with helpers in `includes/auth.php`.
- Three roles are supported: **admin**, **doctor** and **user**.
- Admin pages:
  - `admin_bookings.php` — view and update every appointment.
  - `admin_doctors.php` — add doctors, reset doctor passwords and delete doctor accounts.
- Doctor page: `doctor_dashboard.php` — view assigned appointments, confirm them, save feedback,
  cancel them, or offer a new appointment date/time with a message.
- User page: `my_bookings.php` — shows the logged-in user's appointments, status and doctor message.
- The admin can create doctor accounts, reset their passwords, and upload/update doctor photos
  from `admin_doctors.php`.
- Doctor cards on the public site are generated from the `doctors` table, so doctors added by an
  admin appear automatically. A placeholder photo is used until the admin uploads a real one.
- The public booking form reads doctor options from the same table, so new doctors are bookable
  immediately.
- When a doctor confirms, reschedules or cancels an appointment, an email is sent to the patient.
  Fill in `includes/mail_config.php` with your SMTP credentials (e.g. Gmail app password) to
  enable real delivery. Fake/test email addresses (example.com, test.com, etc.) are skipped.
  A copy of every notification is logged in `data/emails.log`.
- The availability checker remains a deterministic front-end diary simulation; the booking form
  itself persists to MySQL.

---

## Credits

- **Bootstrap 5.3.3** and **Bootstrap Icons 1.11.3** (bundled locally).
- **Fraunces** and **Manrope** typefaces via Google Fonts (bundled locally).
- Photography from **Unsplash** (free-to-use licence).
- Icons and logo artwork hand-built for this project.
