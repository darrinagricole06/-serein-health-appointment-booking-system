<?php
/**
 * Serein Health — Appointment Booking System
 * Task 1: Website Design & Bootstrap 5
 *
 * Front-end only at this stage. Booking handling and database connectivity
 * are added in the next task.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$year = date('Y');

$publicDoctors = [];
$doctorResult = $conn->query('SELECT id, name, specialty, image, languages, bio FROM doctors ORDER BY specialty ASC, name ASC');
if ($doctorResult) {
    while ($doctorRow = $doctorResult->fetch_assoc()) {
        $publicDoctors[] = $doctorRow;
    }
    $doctorResult->free();
}

$doctorsBySpecialty = [];
foreach ($publicDoctors as $doctorRow) {
    $doctorsBySpecialty[$doctorRow['specialty']][] = $doctorRow['name'];
}
ksort($doctorsBySpecialty);
foreach ($doctorsBySpecialty as &$specialtyDoctors) {
    sort($specialtyDoctors);
}
unset($specialtyDoctors);

$timeSlots = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
    '11:00', '11:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00',
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serein Health — Book Your Appointment | Victoria, Seychelles</title>
    <meta name="description" content="Book same-day appointments with leading specialists at Serein Health, Victoria Seychelles Hospital. General medicine, dental, physiotherapy, skin, mental wellness and nutrition — in clinic or online.">
    <meta name="theme-color" content="#062b3f">

    <meta property="og:title" content="Serein Health — Book Your Appointment">
    <meta property="og:description" content="Care that fits your life. Book same-day appointments with specialists who listen.">
    <meta property="og:type" content="website">

    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

    <link rel="preload" href="assets/fonts/fraunces-500.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/fonts/manrope-400.woff2" as="font" type="font/woff2" crossorigin>

    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
</head>
<body>

<?php
$flashStatus = isset($_GET['status']) ? $_GET['status'] : '';
$flashMessage = isset($_GET['message']) ? $_GET['message'] : '';
$lastBooking = $_SESSION['last_booking'] ?? null;
if ($flashStatus !== '' && $flashMessage !== '') {
    $alertClass = ($flashStatus === 'success') ? 'alert-success' : 'alert-danger';
    echo '<div class="container mt-3"><div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8')
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>';
}
?>

<a class="skip-link" href="#main">Skip to main content</a>

<!-- ======================= TOP BAR ======================= -->
<div class="topbar">
    <div class="container">
        <div class="topbar-left">
            <span>
                <i class="bi bi-telephone"></i>
                <a href="tel:+2484388000">+248 438 8000</a>
            </span>
            <span class="d-none d-md-inline-flex">
                <i class="bi bi-geo-alt"></i>
                Victoria Hospital, Mahé
            </span>
            <span class="d-none d-lg-inline-flex">
                <i class="bi bi-clock"></i>
                Open 24/7
            </span>
        </div>
        <div class="topbar-right">
            <span class="d-none d-md-inline-flex">
                <i class="bi bi-shield-check"></i>
                Accepting new patients
            </span>
            <a class="topbar-find" href="#find-slot">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                Find your slot
            </a>
        </div>
    </div>
</div>

<!-- ======================= NAVIGATION ======================= -->
<nav class="navbar navbar-expand-lg site-nav sticky-top" id="siteNav" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="#home">
            <img src="assets/img/logo.svg" alt="" width="44" height="44" class="brand-mark">
            <span class="brand-text">
                <span class="brand-name">Serein</span>
                <span class="brand-sub">Victoria · Seychelles</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link active" href="#home" data-section="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#services" data-section="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#how" data-section="#how">How it works</a></li>
                <li class="nav-item"><a class="nav-link" href="#doctors" data-section="#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="#about" data-section="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#book" data-section="#book">Contact</a></li>
            </ul>
            <div class="nav-actions">
                <?php if (is_admin() || is_doctor()): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= dashboard_url() ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                <?php endif; ?>
                <a class="nav-phone" href="tel:+2484388000">
                    <i class="bi bi-telephone"></i> +248 438 8000
                </a>
                <a class="btn btn-coral" href="#book">
                    Book appointment <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<main id="main">

    <!-- ======================= HERO / CAROUSEL ======================= -->
    <section class="hero" id="home" aria-label="Welcome to Serein Health">
        <div id="heroCarousel" class="carousel slide hero-carousel"
             data-bs-ride="carousel" data-bs-pause="false">

            <div class="carousel-inner">
                <div class="carousel-item active" role="group" aria-roledescription="slide" aria-label="1 of 3">
                    <div class="hero-media">
                        <img src="assets/img/carousel/slide-1.jpg" alt="Bright, modern reception area at Serein Health"
                             width="1920" height="1200" fetchpriority="high" decoding="async">
                    </div>
                </div>
                <div class="carousel-item" role="group" aria-roledescription="slide" aria-label="2 of 3">
                    <div class="hero-media">
                        <img src="assets/img/carousel/slide-2.jpg" alt="A doctor talking with a patient during a consultation"
                             width="1920" height="1200" decoding="async">
                    </div>
                </div>
                <div class="carousel-item" role="group" aria-roledescription="slide" aria-label="3 of 3">
                    <div class="hero-media">
                        <img src="assets/img/carousel/slide-3.jpg" alt="A nurse caring for an older patient"
                             width="1920" height="1200" decoding="async">
                    </div>
                </div>
            </div>

            <div class="container hero-inner">
                <div class="row align-items-center g-5">
                    <div class="col-lg-8">
                        <div class="hero-copy">
                            <span class="hero-pill">
                                <span class="pulse-dot" aria-hidden="true"></span>
                                Now accepting new patients · Victoria, Mahé
                            </span>
                            <h1>Your health,<br><em>on your schedule.</em></h1>
                            <p class="hero-lead">
                                Book same-day appointments with specialists who listen — in clinic or online.
                                No phone queues, no waiting weeks.
                            </p>
                            <div class="hero-actions">
                                <a class="btn btn-coral btn-lg" href="#book">
                                    Book an appointment <i class="bi bi-arrow-right"></i>
                                </a>
                                <a class="btn btn-ghost-light btn-lg" href="#services">Explore our services</a>
                            </div>
                            <div class="hero-trust">
                                <div class="avatar-stack" aria-hidden="true">
                                    <img src="assets/img/avatar-1.jpg" alt="" width="160" height="160">
                                    <img src="assets/img/avatar-2.jpg" alt="" width="160" height="160">
                                    <img src="assets/img/avatar-3.jpg" alt="" width="160" height="160">
                                    <img src="assets/img/avatar-4.jpg" alt="" width="160" height="160">
                                    <span class="more">+2k</span>
                                </div>
                                <div class="hero-trust-text">
                                    <span class="stars" aria-hidden="true">★★★★★</span>
                                    <strong>4.9/5 from 1,200+ patients</strong>
                                    Trusted care, real stories
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container hero-bottom">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="carousel-indicators hero-indicators">
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0"
                                    class="active" aria-current="true" aria-label="Slide 1: Our clinic"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"
                                    aria-label="Slide 2: Consultations"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"
                                    aria-label="Slide 3: Caring for you"></button>
                        </div>
                        <span class="hero-count" aria-hidden="true"><span id="heroSlideNow">01</span> / 03</span>
                    </div>
                    <div class="hero-arrows">
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            <span class="visually-hidden">Previous slide</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            <span class="visually-hidden">Next slide</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= FIND YOUR SLOT ======================= -->
    <section class="find-slot-section" id="find-slot" aria-labelledby="findSlotTitle">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-5">
                    <div class="find-slot-intro reveal">
                        <span class="eyebrow">Availability</span>
                        <h2 class="find-slot-title" id="findSlotTitle">Find your <em>slot.</em></h2>
                        <p class="find-slot-lead">
                            Choose a service, date and time. We check every doctor's diary and show you who is
                            free — or the next opening if they are fully booked.
                        </p>
                        <ul class="find-slot-points">
                            <li>
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                <span>Each doctor's status for your exact time</span>
                            </li>
                            <li>
                                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                                <span>Next free slot offered when they're full</span>
                            </li>
                            <li>
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <span>No account needed · free cancellation</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="quick-card quick-card-standalone reveal" style="--delay: .1s">
                        <div class="quick-card-head">
                            <div>
                                <h3 class="quick-title">
                                    <i class="bi bi-calendar2-week" aria-hidden="true"></i> Check a time
                                </h3>
                                <p class="quick-sub">Every doctor's diary · Free cancellation</p>
                            </div>
                        </div>

                        <form id="quickForm" class="row g-3" novalidate method="POST" action="process_availability.php">
                            <div class="col-12">
                                <label class="form-label" for="quickService">Service</label>
                                <select class="form-select" id="quickService" name="quickService" required>
                                    <option value="" selected disabled>Choose a service</option>
                                    <option value="general">General Medicine</option>
                                    <option value="dental">Dental Care</option>
                                    <option value="physio">Physiotherapy</option>
                                    <option value="skin">Skin &amp; Aesthetics</option>
                                    <option value="mental">Mental Wellness</option>
                                    <option value="nutrition">Nutrition &amp; Dietetics</option>
                                </select>
                                <div class="invalid-feedback">Please choose a service.</div>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="quickDate">Preferred date</label>
                                <input type="date" class="form-control" id="quickDate" name="quickDate" required>
                                <div class="invalid-feedback">Pick a date.</div>
                            </div>
                            <div class="col-5">
                                <label class="form-label" for="quickTime">Time</label>
                                <select class="form-select" id="quickTime" name="quickTime" required>
                                    <option value="" selected disabled>Select</option>
                                    <?php foreach ($timeSlots as $slot): ?>
                                        <option value="<?= htmlspecialchars($slot) ?>">
                                            <?= htmlspecialchars(date('g:i A', strtotime($slot))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Pick a time.</div>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-brand" type="submit">
                                    See who's free <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </form>

                        <div class="quick-chip">
                            <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
                            <span>Same-day openings · <strong>12 clinicians</strong></span>
                        </div>
                        <p class="quick-note">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            Your details stay private and secure.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= STATS ======================= -->
    <div class="stats-strip">
        <div class="container">
            <div class="stats-card reveal">
                <div class="row g-0">
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <span class="stat-value">24/7</span>
                            <span class="stat-label">Online booking</span>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <span class="stat-value">
                                <span class="stat-number" data-count="25" data-decimals="0">25</span><span class="suffix">+</span>
                            </span>
                            <span class="stat-label">Specialist clinicians</span>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <span class="stat-value">
                                <span class="stat-number" data-count="4.9" data-decimals="1">4.9</span><span class="suffix">/5</span>
                            </span>
                            <span class="stat-label">Average patient rating</span>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <span class="stat-value">
                                <span class="stat-number" data-count="15" data-decimals="0">15</span><span class="suffix">+</span>
                            </span>
                            <span class="stat-label">Years of care</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================= SERVICES ======================= -->
    <section class="section section-cream section-pattern" id="services">
        <div class="container">
            <div class="services-header">
                <div class="reveal">
                    <span class="eyebrow">What we treat</span>
                    <h2 class="section-title">Care for every <em>part of you.</em></h2>
                    <p class="section-lead">
                        From everyday check-ups to specialised care, our team looks after your whole self —
                        with time to talk and a plan that makes sense.
                    </p>
                </div>
                <a class="btn btn-outline-brand reveal" href="#book" style="--delay: .1s">
                    Not sure what you need? Talk to us <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <div class="col">
                    <article class="service-card card reveal" style="--delay: 0s">
                        <span class="service-num">01</span>
                        <span class="service-icon"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
                        <h3>General Medicine</h3>
                        <p>Everyday health, chronic care and preventive check-ups with a doctor who knows your history.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 750</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="general">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <div class="col">
                    <article class="service-card card reveal" style="--delay: .06s">
                        <span class="service-num">02</span>
                        <span class="service-icon"><i class="bi bi-emoji-smile" aria-hidden="true"></i></span>
                        <h3>Dental Care</h3>
                        <p>Gentle cleanings, fillings and cosmetic dentistry in a calm, modern surgery.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 1,200</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="dental">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <div class="col">
                    <article class="service-card card reveal" style="--delay: .12s">
                        <span class="service-num">03</span>
                        <span class="service-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
                        <h3>Physiotherapy</h3>
                        <p>Hands-on treatment and movement plans that get you back to doing what you love.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 950</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="physio">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <div class="col">
                    <article class="service-card card reveal" style="--delay: 0s">
                        <span class="service-num">04</span>
                        <span class="service-icon"><i class="bi bi-stars" aria-hidden="true"></i></span>
                        <h3>Skin &amp; Aesthetics</h3>
                        <p>Dermatologist-led care for acne, eczema, ageing and everything in between.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 1,400</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="skin">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <div class="col">
                    <article class="service-card card reveal" style="--delay: .06s">
                        <span class="service-num">05</span>
                        <span class="service-icon"><i class="bi bi-chat-heart" aria-hidden="true"></i></span>
                        <h3>Mental Wellness</h3>
                        <p>Confidential counselling and therapy, in person or from the comfort of home.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 1,250</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="mental">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <div class="col">
                    <article class="service-card card reveal" style="--delay: .12s">
                        <span class="service-num">06</span>
                        <span class="service-icon"><i class="bi bi-basket2" aria-hidden="true"></i></span>
                        <h3>Nutrition &amp; Dietetics</h3>
                        <p>Practical, personalised nutrition plans for more energy and lasting wellbeing.</p>
                        <div class="service-meta">
                            <span class="service-price">from SR 900</span>
                        </div>
                        <button type="button" class="link-arrow" data-book-service="nutrition">
                            Book this service <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= HOW IT WORKS ======================= -->
    <section class="section section-white" id="how">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <div class="process-panel reveal">
                        <span class="eyebrow">How it works</span>
                        <h2 class="section-title">Three steps to <em>feeling better.</em></h2>
                        <p class="section-lead mb-4">
                            No hold music. No guesswork. Just a simple booking flow designed around your day —
                            and a care team ready when you arrive.
                        </p>
                        <a class="btn btn-brand" href="#book">
                            Book an appointment <i class="bi bi-arrow-right"></i>
                        </a>
                        <div class="process-note">
                            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                            <div>
                                <strong>Next available: today at 4:30 PM</strong>
                                <span>Most appointments booked in under 60 seconds.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="step active reveal" style="--delay: .05s">
                        <span class="step-num">01</span>
                        <h3>Choose your care</h3>
                        <p>Browse our services and pick the appointment type that suits you. Not sure? Start with general medicine and we'll guide you.</p>
                    </div>
                    <div class="step reveal" style="--delay: .1s">
                        <span class="step-num">02</span>
                        <h3>Find your time</h3>
                        <p>See real-time availability and choose a slot that fits your day — early mornings, lunch breaks or after work.</p>
                    </div>
                    <div class="step reveal" style="--delay: .15s">
                        <span class="step-num">03</span>
                        <h3>Confirm and relax</h3>
                        <p>Get instant confirmation, helpful reminders and easy rescheduling. Then simply arrive — we'll take it from there.</p>
                    </div>
                    <a class="link-arrow process-link reveal" href="#visit">
                        What to expect at your visit <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= DOCTORS ======================= -->
<?php
$doctorCardDetails = [
    'Dr. Elena Rossi' => [
        'key' => 'elena',
        'meta' => 'MD · 12 years experience',
        'tags' => ['Check-ups', "Women's health", 'Chronic care'],
        'rating' => '4.9',
        'availability' => 'Available today',
        'service' => 'general',
    ],
    'Dr. Arjun Mehta' => [
        'key' => 'arjun',
        'meta' => 'BDS · 9 years experience',
        'tags' => ['Cosmetic', 'Fillings', 'Family'],
        'rating' => '4.8',
        'availability' => 'Tomorrow',
        'service' => 'dental',
    ],
    'Dr. Maya Chen' => [
        'key' => 'maya',
        'meta' => 'MD · 11 years experience',
        'tags' => ['Acne', 'Eczema', 'Skin health'],
        'rating' => '5.0',
        'availability' => 'Available today',
        'service' => 'skin',
    ],
    'Dr. Lucas Ferreira' => [
        'key' => 'lucas',
        'meta' => 'MSc · 8 years experience',
        'tags' => ['Sports injury', 'Back pain', 'Rehab'],
        'rating' => '4.9',
        'availability' => 'Friday',
        'service' => 'physio',
    ],
    'Dr. Priya Nair' => [
        'key' => 'priya',
        'meta' => 'MD · 10 years experience',
        'tags' => ['Preventive', 'Family', 'Lifestyle'],
        'rating' => '4.8',
        'availability' => 'Available today',
        'service' => 'general',
    ],
    'Dr. Samuel Labiche' => [
        'key' => 'samuel',
        'meta' => 'BDS · 7 years experience',
        'tags' => ['Orthodontics', 'Cleanings', 'Implants'],
        'rating' => '4.7',
        'availability' => 'Tomorrow',
        'service' => 'dental',
    ],
    'Dr. Claire Antat' => [
        'key' => 'claire',
        'meta' => 'MSc · 6 years experience',
        'tags' => ['Posture', 'Mobility', 'Pain relief'],
        'rating' => '4.8',
        'availability' => 'Friday',
        'service' => 'physio',
    ],
    'Dr. Nadia Payet' => [
        'key' => 'nadia',
        'meta' => 'MD · 9 years experience',
        'tags' => ['Aesthetics', 'Pigmentation', 'Anti-ageing'],
        'rating' => '4.9',
        'availability' => 'Available today',
        'service' => 'skin',
    ],
    'Dr. Amara Hoareau' => [
        'key' => 'amara',
        'meta' => 'MA · 10 years experience',
        'tags' => ['Anxiety', 'Stress', 'Therapy'],
        'rating' => '4.9',
        'availability' => 'Available today',
        'service' => 'mental',
    ],
    'Dr. Theo Barbier' => [
        'key' => 'theo',
        'meta' => 'MA · 7 years experience',
        'tags' => ['Counselling', 'Mindfulness', 'Burnout'],
        'rating' => '4.8',
        'availability' => 'Tomorrow',
        'service' => 'mental',
    ],
    'Dr. Jean Morel' => [
        'key' => 'jean',
        'meta' => 'RD · 8 years experience',
        'tags' => ['Weight', 'Diabetes', 'Sports nutrition'],
        'rating' => '4.7',
        'availability' => 'Available today',
        'service' => 'nutrition',
    ],
    'Dr. Elodie Fanchette' => [
        'key' => 'elodie',
        'meta' => 'RD · 6 years experience',
        'tags' => ['Gut health', 'Paediatric', 'Wellness'],
        'rating' => '4.8',
        'availability' => 'Friday',
        'service' => 'nutrition',
    ],
];

$staticDoctorNames = array_keys($doctorCardDetails);
usort($publicDoctors, function (array $a, array $b) use ($staticDoctorNames): int {
    $positionA = array_search($a['name'], $staticDoctorNames, true);
    $positionB = array_search($b['name'], $staticDoctorNames, true);

    if ($positionA === false && $positionB === false) {
        return strcmp($a['name'], $b['name']);
    }

    if ($positionA === false) {
        return 1;
    }

    if ($positionB === false) {
        return -1;
    }

    return $positionA <=> $positionB;
});

$specialtyServices = [
    'General medicine' => 'general',
    'Dental surgery' => 'dental',
    'Physiotherapy' => 'physio',
    'Dermatology' => 'skin',
    'Mental wellness' => 'mental',
    'Nutrition & dietetics' => 'nutrition',
];
?>
<section class="section section-foam" id="doctors">
    <div class="container">
        <div class="services-header">
            <div class="reveal">
                <span class="eyebrow">Meet your care team</span>
                <h2 class="section-title">Specialists who <em>actually listen.</em></h2>
                <p class="section-lead">
                    Experienced clinicians across medicine, dentistry, physiotherapy and wellbeing —
                    all under one calm roof.
                </p>
            </div>
            <button type="button" class="link-arrow reveal" data-book-service="general" style="--delay: .1s">
                Start with a general visit <i class="bi bi-arrow-right"></i>
            </button>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
            <?php foreach ($publicDoctors as $index => $doctor): ?>
                <?php
                $details = $doctorCardDetails[$doctor['name']] ?? [];
                $profileKey = $details['key'] ?? '';
                $availability = $details['availability'] ?? 'Accepting patients';
                $rating = $details['rating'] ?? '';
                $meta = $details['meta'] ?? 'New specialist';
                $tags = $details['tags'] ?? ['New specialist', 'Accepting patients'];
                $serviceKey = $details['service'] ?? ($specialtyServices[$doctor['specialty']] ?? '');
                $image = (!empty($doctor['image']) && is_file(__DIR__ . '/' . $doctor['image']))
                    ? $doctor['image']
                    : 'assets/img/doctors/doctor-placeholder.svg';
                $hasUploadedPhoto = !empty($doctor['image']) && is_file(__DIR__ . '/' . $doctor['image']);
                ?>
                <div class="col">
                    <article class="doctor-card card reveal" style="--delay: <?= min($index * 0.06, 1.2) ?>s">
                        <div class="doctor-media">
                            <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($doctor['name'] . ', ' . $doctor['specialty'] . ' specialist', ENT_QUOTES, 'UTF-8') ?>"
                                 width="800" height="1000" loading="lazy" decoding="async">
                            <span class="badge-availability"><span class="dot" aria-hidden="true"></span><?= htmlspecialchars($availability, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($rating !== ''): ?>
                                <span class="doctor-rating"><i class="bi bi-star-fill" aria-hidden="true"></i><?= htmlspecialchars($rating, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="doctor-rating"><i class="bi bi-stars" aria-hidden="true"></i>New</span>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-body">
                            <span class="doctor-specialty"><?= htmlspecialchars($doctor['specialty'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3 class="doctor-name"><?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="doctor-meta"><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="doctor-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="doctor-actions">
                                <?php if ($profileKey !== ''): ?>
                                    <button type="button" class="btn-view" data-doctor="<?= htmlspecialchars($profileKey, ENT_QUOTES, 'UTF-8') ?>">View profile</button>
                                <?php elseif ($hasUploadedPhoto || trim($doctor['bio'] ?? '') !== '' || trim($doctor['languages'] ?? '') !== ''): ?>
                                    <button type="button" class="btn-view"
                                            data-dynamic-doctor="1"
                                            data-name="<?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-specialty="<?= htmlspecialchars($doctor['specialty'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-image="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                                            data-languages="<?= htmlspecialchars($doctor['languages'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-bio="<?= htmlspecialchars($doctor['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-service="<?= htmlspecialchars($serviceKey, ENT_QUOTES, 'UTF-8') ?>">
                                        View profile
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-view"
                                            data-book-doctor="<?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?php if ($serviceKey !== ''): ?>data-book-service="<?= htmlspecialchars($serviceKey, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                                        Book visit
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="doctor-book"
                                        data-book-doctor="<?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?php if ($serviceKey !== ''): ?>data-book-service="<?= htmlspecialchars($serviceKey, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                        aria-label="Book an appointment with <?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======================= ABOUT ======================= -->
    <section class="section section-white" id="about">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="about-media reveal">
                        <img src="assets/img/about-consultation.jpg"
                             alt="A Serein clinician talking a patient through their scan results"
                             width="1000" height="1250" loading="lazy" decoding="async" class="about-img">
                        <div class="about-badge">
                            <strong>15+</strong>
                            <span>Years of care</span>
                        </div>
                        <div class="about-quote">
                            <i class="bi bi-quote" aria-hidden="true"></i>
                            <p>"The calmest clinic I've ever visited — and I was seen right on time."</p>
                            <span>Amelia R. · Patient since 2019</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-copy reveal" style="--delay: .1s">
                        <span class="eyebrow">Why Serein</span>
                        <h2 class="section-title">A calmer kind of <em>healthcare.</em></h2>
                        <p class="section-lead">
                            We built Serein around the parts of healthcare that usually feel rushed:
                            time to talk, clear answers, and appointments that start when they should.
                        </p>

                        <ul class="about-list">
                            <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Same-day appointments</li>
                            <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Transparent pricing</li>
                            <li><i class="bi bi-check2-circle" aria-hidden="true"></i> One connected health record</li>
                            <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Secure online booking</li>
                        </ul>

                        <div class="progress-block">
                            <div class="progress-head"><span>Patient satisfaction</span><span>98%</span></div>
                            <div class="progress" role="progressbar" aria-label="Patient satisfaction" aria-valuenow="98" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" data-progress="98" style="width: 98%"></div>
                            </div>
                        </div>
                        <div class="progress-block">
                            <div class="progress-head"><span>Seen on time</span><span>94%</span></div>
                            <div class="progress" role="progressbar" aria-label="Patients seen on time" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" data-progress="94" style="width: 94%"></div>
                            </div>
                        </div>
                        <div class="progress-block">
                            <div class="progress-head"><span>Would recommend us</span><span>96%</span></div>
                            <div class="progress" role="progressbar" aria-label="Patients who would recommend Serein" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" data-progress="96" style="width: 96%"></div>
                            </div>
                        </div>

                        <a class="btn btn-brand" href="#book">
                            Book your first visit <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= WHAT TO EXPECT ======================= -->
    <section class="section visit-section" id="visit">
        <div class="container">
            <div class="row align-items-end g-4 mb-5">
                <div class="col-lg-7 reveal">
                    <span class="eyebrow eyebrow-light">What to expect</span>
                    <h2 class="section-title">Your visit, <em>without the guesswork.</em></h2>
                    <p class="section-lead">
                        From the front desk to your follow-up, here's how an appointment at Serein
                        unfolds — and what your specialist will do.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end reveal" style="--delay: .1s">
                    <span class="visit-time">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        Visit length depends on what you need to do or discuss
                    </span>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <div class="col">
                    <article class="visit-step reveal" style="--delay: 0s">
                        <div class="visit-step-top">
                            <span class="visit-step-num">01</span>
                            <span class="visit-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                        </div>
                        <h3>Check in</h3>
                        <p>
                            Arrive 10 minutes early with photo ID and your insurance card. Reception
                            confirms your details and any records you've sent ahead.
                        </p>
                    </article>
                </div>
                <div class="col">
                    <article class="visit-step reveal" style="--delay: .06s">
                        <div class="visit-step-top">
                            <span class="visit-step-num">02</span>
                            <span class="visit-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        </div>
                        <h3>Talk it through</h3>
                        <p>
                            Your specialist asks about your symptoms, medical history, lifestyle and
                            goals. Take as long as you need — with no rushing.
                        </p>
                    </article>
                </div>
                <div class="col">
                    <article class="visit-step reveal" style="--delay: .12s">
                        <div class="visit-step-top">
                            <span class="visit-step-num">03</span>
                            <span class="visit-icon"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
                        </div>
                        <h3>Examination</h3>
                        <p>
                            A focused physical check and, if needed, tests or scans. Your specialist
                            explains what they're doing and why before every step.
                        </p>
                    </article>
                </div>
                <div class="col">
                    <article class="visit-step reveal" style="--delay: .18s">
                        <div class="visit-step-top">
                            <span class="visit-step-num">04</span>
                            <span class="visit-icon"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
                        </div>
                        <h3>Your plan</h3>
                        <p>
                            You leave with a clear diagnosis or next step, a written treatment plan,
                            any prescriptions and a follow-up time if you need one.
                        </p>
                    </article>
                </div>
            </div>

            <p class="visit-note reveal">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>
                    Bring a list of the medicines you take and any previous scans or reports.
                    Not sure what to bring? Call <a href="tel:+2484388000">+248 438 8000</a>.
                </span>
            </p>
        </div>
    </section>

    <!-- ======================= REVIEWS ======================= -->
    <section class="section section-cream" id="reviews">
        <div class="container">
            <div class="row align-items-end g-4 mb-5">
                <div class="col-lg-7 reveal">
                    <span class="eyebrow">Patient stories</span>
                    <h2 class="section-title">Care people <em>talk about.</em></h2>
                    <p class="section-lead">
                        Real appointments, real people. Here's what a visit to Serein feels like.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end reveal" style="--delay: .1s">
                    <span class="review-summary d-inline-flex mt-0">
                        <span class="review-score">
                            <strong>4.9</strong>
                            <span>
                                <span class="stars d-block" aria-hidden="true">★★★★★</span>
                                1,248 reviews
                            </span>
                        </span>
                        <span class="verified"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Independently verified</span>
                    </span>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <article class="review-card card reveal" style="--delay: 0s">
                        <div class="review-stars" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="review-quote">
                            "I booked at 9pm and was seen the next morning. The whole thing took less time
                            than making a cup of tea."
                        </p>
                        <div class="review-person">
                            <img src="assets/img/avatar-1.jpg" alt="" width="160" height="160" loading="lazy">
                            <div>
                                <strong>Sarah Mitchell</strong>
                                <span>General check-up · March 2026</span>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-lg-4">
                    <article class="review-card card reveal" style="--delay: .07s">
                        <div class="review-stars" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="review-quote">
                            "Dr. Mehta explained every step and never made me feel rushed. My kids actually
                            look forward to the dentist now."
                        </p>
                        <div class="review-person">
                            <img src="assets/img/avatar-2.jpg" alt="" width="160" height="160" loading="lazy">
                            <div>
                                <strong>James Turner</strong>
                                <span>Dental care · February 2026</span>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-lg-4">
                    <article class="review-card card reveal" style="--delay: .14s">
                        <div class="review-stars" aria-label="5 out of 5 stars">★★★★★</div>
                        <p class="review-quote">
                            "The online booking, the reminders, the follow-up — it all feels designed by
                            someone who actually cares."
                        </p>
                        <div class="review-person">
                            <img src="assets/img/avatar-3.jpg" alt="" width="160" height="160" loading="lazy">
                            <div>
                                <strong>Priya Raman</strong>
                                <span>Mental wellness · January 2026</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= CTA ======================= -->
    <section class="cta-section">
        <div class="cta-bg" aria-hidden="true"></div>
        <div class="container">
            <div class="cta-inner reveal">
                <span class="eyebrow eyebrow-light">Ready when you are</span>
                <h2>Good health starts with <em>one small appointment.</em></h2>
                <p>
                    Book online in under a minute — or call our care team and we'll help you find
                    a time that works.
                </p>
                <div class="cta-actions">
                    <a class="btn btn-coral btn-lg" href="#book">
                        Book an appointment <i class="bi bi-arrow-right"></i>
                    </a>
                    <a class="btn btn-ghost-light btn-lg" href="tel:+2484388000">
                        <i class="bi bi-telephone"></i> +248 438 8000
                    </a>
                </div>
                <p class="cta-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Free cancellation up to 24 hours before your visit.
                </p>
            </div>
        </div>
    </section>

    <!-- ======================= BOOKING ======================= -->
    <section class="section booking-section" id="book">
        <div class="container">
            <div class="row g-5">

                <div class="col-lg-5">
                    <div class="booking-info reveal">
                        <span class="eyebrow">Book online</span>
                        <h2 class="section-title">Let's find a time that <em>works for you.</em></h2>
                        <p class="section-lead mb-4">
                            Appointments are usually confirmed within 15 minutes during clinic hours.
                            Prefer to talk? Our care team is a call away.
                        </p>

                        <div class="contact-item">
                            <span class="contact-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                            <div>
                                <h3>Call us</h3>
                                <a href="tel:+2484388000">+248 438 8000</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <div>
                                <h3>Email us</h3>
                                <a href="mailto:hello@serein.sc">hello@serein.sc</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                            <div>
                                <h3>Visit us</h3>
                                <p>Victoria Seychelles Hospital<br>Mont Fleuri, Victoria, Mahé</p>
                                <a class="link-arrow mt-1" href="https://www.google.com/maps/search/?api=1&amp;query=Victoria+Hospital+Seychelles" target="_blank" rel="noopener">
                                    Get directions <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        <div class="hours-card">
                            <h3>Opening hours</h3>
                            <div class="hours-row"><span>Hospital &amp; emergency</span><span>Open 24/7</span></div>
                            <div class="hours-row"><span>Appointments · Mon–Fri</span><span>8:00 AM – 6:00 PM</span></div>
                            <div class="hours-row"><span>Appointments · Saturday</span><span>9:00 AM – 2:00 PM</span></div>
                            <div class="hours-row"><span>Appointments · Sunday</span><span>Closed</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="alert alert-info-soft alert-dismissible fade show booking-alert reveal" role="alert">
                        <div class="d-flex gap-3 align-items-start">
                            <i class="bi bi-info-circle-fill fs-5" aria-hidden="true"></i>
                            <div>
                                <strong>New here?</strong> Your first consultation includes a complimentary
                                15-minute health review. <a href="#faq" class="alert-link">Read the FAQs</a>.
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>

                    <div class="alert alert-success-soft d-none reveal" id="formSuccess" role="alert">
                        <div class="d-flex gap-3 align-items-start">
                            <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
                            <div>
                                <strong>Appointment request confirmed!</strong>
                                <span id="successText">We've saved your request and will email your confirmation shortly.</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($lastBooking['token']) && $flashStatus === 'success'): ?>
                        <div class="modal fade" id="bookingTokenModal" tabindex="-1" aria-labelledby="bookingTokenModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title" id="bookingTokenModalLabel">
                                            <i class="bi bi-key-fill text-primary me-2" aria-hidden="true"></i>
                                            Your appointment access token
                                        </h5>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted small mb-3">
                                            Save this 10-character token. You can use it on the website to view or cancel
                                            your appointment without needing the email link.
                                        </p>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control font-monospace" id="bookingAccessToken"
                                                   value="<?= htmlspecialchars($lastBooking['token'], ENT_QUOTES, 'UTF-8') ?>"
                                                   readonly onclick="this.select()">
                                            <button type="button" class="btn btn-outline-secondary" id="copyBookingToken">
                                                Copy
                                            </button>
                                        </div>
                                        <a class="btn btn-brand w-100"
                                           href="appointment.php?token=<?= htmlspecialchars(urlencode($lastBooking['token']), ENT_QUOTES, 'UTF-8') ?>">
                                            View this appointment
                                        </a>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            I’ve saved my token
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (is_logged_in() && (current_user()['role'] ?? '') === 'user'): ?>
                        <div class="alert alert-info-soft reveal" id="tokenLookupPanel" role="status">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-key fs-5" aria-hidden="true"></i>
                                <div class="flex-grow-1">
                                    <strong>Already booked?</strong>
                                    <p class="small mb-2">
                                        Enter your 10-character access token to view or cancel an appointment.
                                    </p>
                                    <form method="GET" action="appointment.php" class="row g-2">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control font-monospace" name="token"
                                                   placeholder="Paste your 10-character token" maxlength="10"
                                                   pattern="[A-Za-z0-9]{10}" autocomplete="off" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-outline-secondary w-100">
                                                View appointment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="booking-form-card reveal" style="--delay: .08s" id="bookingCard">
                        <div class="form-head">
                            <div>
                                <h3>Book your visit</h3>
                                <p>Takes about 2 minutes · No payment required today</p>
                            </div>
                            <span class="form-head-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
                        </div>

                        <div class="form-progress">
                            <div class="progress-head">
                                <span>Booking progress</span>
                                <span id="formProgressLabel">0% complete</span>
                            </div>
                            <div class="progress" role="progressbar" aria-label="Booking form completion"
                                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div class="progress-bar" id="formProgress"></div>
                            </div>
                        </div>

                        <form id="bookingForm" class="row g-3" novalidate method="POST" action="process_booking.php">
                            <div class="col-md-6">
                                <label class="form-label" for="service">Service <span class="required-star">*</span></label>
                                <select class="form-select" id="service" name="service" required>
                                    <option value="" selected disabled>Choose a service</option>
                                    <option value="general">General Medicine</option>
                                    <option value="dental">Dental Care</option>
                                    <option value="physio">Physiotherapy</option>
                                    <option value="skin">Skin &amp; Aesthetics</option>
                                    <option value="mental">Mental Wellness</option>
                                    <option value="nutrition">Nutrition &amp; Dietetics</option>
                                </select>
                                <p class="text-muted-soft small mt-1 mb-0" id="estimatedTotal" aria-live="polite"></p>
                                <div class="invalid-feedback">Please choose a service.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="doctor">Doctor <span class="text-muted-soft fw-normal">(optional)</span></label>
                                <select class="form-select" id="doctor" name="doctor">
                                    <option value="">First available</option>
                                    <?php if (empty($doctorsBySpecialty)): ?>
                                        <option value="Dr. Elena Rossi">Dr. Elena Rossi</option>
                                    <?php else: ?>
                                        <?php foreach ($doctorsBySpecialty as $specialty => $doctorNames): ?>
                                            <optgroup label="<?= htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8') ?>">
                                                <?php foreach ($doctorNames as $doctorName): ?>
                                                    <option value="<?= htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-service="<?= htmlspecialchars(service_key_for_specialty($specialty), ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="date">Preferred date <span class="required-star">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required>
                                <div class="invalid-feedback">Please choose a date.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="time">Preferred time <span class="required-star">*</span></label>
                                <select class="form-select" id="time" name="time" required>
                                    <option value="" selected disabled>Select a time</option>
                                    <?php foreach ($timeSlots as $slot): ?>
                                        <option value="<?= htmlspecialchars($slot) ?>">
                                            <?= htmlspecialchars(date('g:i A', strtotime($slot))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please choose a time.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="firstName">First name <span class="required-star">*</span></label>
                                <input type="text" class="form-control" id="firstName" name="firstName"
                                       placeholder="Jordan" autocomplete="given-name" required>
                                <div class="invalid-feedback">Please enter your first name.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="lastName">Last name <span class="required-star">*</span></label>
                                <input type="text" class="form-control" id="lastName" name="lastName"
                                       placeholder="Lee" autocomplete="family-name" required>
                                <div class="invalid-feedback">Please enter your last name.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address <span class="required-star">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="you@example.com" autocomplete="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone number <span class="required-star">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       placeholder="+248 438 8000" autocomplete="tel"
                                       pattern="[0-9\s\(\)\+\-]{7,20}" required>
                                <div class="invalid-feedback">Please enter a valid phone number.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Anything we should know? <span class="text-muted-soft fw-normal">(optional)</span></label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                          placeholder="Symptoms, accessibility needs, or a preferred clinician…"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="consent" name="consent" required>
                                    <label class="form-check-label" for="consent">
                                        I agree to the <a href="#faq">cancellation policy</a> and consent to being
                                        contacted about this appointment.
                                    </label>
                                    <div class="invalid-feedback">Please confirm before continuing.</div>
                                </div>
                            </div>

                            <div class="col-12 pt-1">
                                <button class="btn btn-brand btn-lg w-100" type="submit">
                                    Request appointment <i class="bi bi-arrow-right"></i>
                                </button>
                                <p class="secure-note mb-0">
                                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                    Secure booking · Your information is never shared.
                                </p>
                                <noscript>
                                    <p class="text-muted small mt-2 mb-0">
                                        Online booking needs JavaScript. Please call
                                        <a href="tel:+2484388000">+248 438 8000</a> instead.
                                    </p>
                                </noscript>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ======================= FAQ ======================= -->
    <section class="section section-white faq-section" id="faq">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="reveal">
                        <span class="eyebrow">Good to know</span>
                        <h2 class="section-title">Questions, <em>answered.</em></h2>
                        <p class="section-lead mb-4">
                            Everything you need before your first visit. Still curious? Our care team is
                            happy to help.
                        </p>
                        <a class="btn btn-outline-brand" href="tel:+2484388000">
                            <i class="bi bi-telephone"></i> Call our team
                        </a>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="accordion reveal" id="faqAccordion" style="--delay: .1s">

                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeadingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqOne" aria-expanded="true" aria-controls="faqOne">
                                    Do I need a referral to book an appointment?
                                </button>
                            </h3>
                            <div id="faqOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    No referral is needed for most of our services. You can book directly online.
                                    If a specialist needs your previous records, our team will ask before your visit.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeadingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqTwo" aria-expanded="false" aria-controls="faqTwo">
                                    How do I reschedule or cancel?
                                </button>
                            </h3>
                            <div id="faqTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can reschedule or cancel free of charge up to 24 hours before your appointment
                                    using the link in your confirmation email, or by calling us. Late cancellations
                                    may incur a small fee.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeadingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqThree" aria-expanded="false" aria-controls="faqThree">
                                    Do you accept insurance?
                                </button>
                            </h3>
                            <div id="faqThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We work with most major insurance providers. Bring your card to your visit and
                                    our reception team will confirm your cover. Self-pay pricing is listed on each
                                    service, with no hidden fees.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeadingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqFour" aria-expanded="false" aria-controls="faqFour">
                                    Can I book an appointment for a family member?
                                </button>
                            </h3>
                            <div id="faqFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Add their details in the booking form and mention it in the notes.
                                    For patients under 16, a parent or guardian must accompany them to the visit.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeadingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqFive" aria-expanded="false" aria-controls="faqFive">
                                    What happens after I submit the form?
                                </button>
                            </h3>
                            <div id="faqFive" class="accordion-collapse collapse" aria-labelledby="faqHeadingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our team confirms your appointment by email within 15 minutes during clinic hours.
                                    You'll receive a reminder 24 hours and 2 hours before your visit, along with
                                    directions and parking information.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- ======================= FOOTER ======================= -->
<footer class="site-footer" id="contact">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="navbar-brand footer-brand" href="#home">
                    <img src="assets/img/logo.svg" alt="" width="44" height="44" class="brand-mark">
                    <span class="brand-text">
                        <span class="brand-name">Serein</span>
                        <span class="brand-sub">Victoria · Seychelles</span>
                    </span>
                </a>
                <p class="footer-about">
                    A calmer kind of healthcare at Victoria Seychelles Hospital. Thoughtful appointments,
                    connected records and specialists who take the time to listen.
                </p>
                <div class="socials">
                    <a class="social-btn" href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="bi bi-instagram" aria-hidden="true"></i>
                    </a>
                    <a class="social-btn" href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">
                        <i class="bi bi-facebook" aria-hidden="true"></i>
                    </a>
                    <a class="social-btn" href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <i class="bi bi-linkedin" aria-hidden="true"></i>
                    </a>
                </div>

                <form class="newsletter" id="newsletterForm" novalidate method="POST" action="process_newsletter.php">
                    <label class="footer-title d-block mb-2" for="newsletterEmail">Health notes, monthly</label>
                    <div class="input-group">
                        <input type="email" class="form-control" id="newsletterEmail" name="newsletterEmail" placeholder="Your email address"
                               aria-label="Email address for newsletter" required>
                        <button class="btn btn-coral" type="submit" aria-label="Subscribe to the newsletter">
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="newsletter-note">Practical wellbeing tips. No spam, unsubscribe anytime.</p>
                    <div class="newsletter-success d-none" id="newsletterSuccess" role="status">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> You're on the list — welcome to Serein.
                    </div>
                </form>
            </div>

            <div class="col-6 col-lg-2">
                <h4 class="footer-title">Explore</h4>
                <ul class="footer-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#how">How it works</a></li>
                    <li><a href="#doctors">Our doctors</a></li>
                    <li><a href="#about">About us</a></li>
                    <li><a href="#visit">What to expect</a></li>
                    <li><a href="#reviews">Patient stories</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-3">
                <h4 class="footer-title">Services</h4>
                <ul class="footer-links">
                    <li><a href="#services">General medicine</a></li>
                    <li><a href="#services">Dental care</a></li>
                    <li><a href="#services">Physiotherapy</a></li>
                    <li><a href="#services">Skin &amp; aesthetics</a></li>
                    <li><a href="#services">Mental wellness</a></li>
                    <li><a href="#services">Nutrition &amp; dietetics</a></li>
                </ul>
            </div>

            <div class="col-lg-3">
                <h4 class="footer-title">Visit us</h4>
                <ul class="footer-contact">
                    <li>
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        <span>Victoria Seychelles Hospital<br>Mont Fleuri, Victoria, Mahé</span>
                    </li>
                    <li>
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        <a href="tel:+2484388000">+248 438 8000</a>
                    </li>
                    <li>
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <a href="mailto:hello@serein.sc">hello@serein.sc</a>
                    </li>
                    <li>
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        <span>Hospital open 24/7<br>Appointments Mon–Fri 8am–6pm · Sat 9am–2pm</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= $year ?> Serein Health. All rights reserved.</span>
            <span>
                <?php if (is_logged_in()): ?>
                    <a href="<?= dashboard_url() ?>" class="footer-login-link">
                        <?= dashboard_label() ?>
                    </a>
                    <span aria-hidden="true">·</span>
                    <a href="logout.php" class="footer-login-link">Log out</a>
                <?php else: ?>
                    <a href="login.php" class="footer-login-link">Staff login</a>
                <?php endif; ?>
            </span>
            <span>Made with <i class="bi bi-heart-fill heart" aria-hidden="true"></i> in Seychelles.</span>
        </div>
    </div>
</footer>

<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-arrow-up" aria-hidden="true"></i>
</button>

<!-- ======================= DOCTOR MODAL ======================= -->
<div class="modal fade" id="doctorModal" tabindex="-1" aria-labelledby="doctorModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="doctorModalTitle">Doctor profile</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <img src="assets/img/doctors/doctor-1.jpg" alt="" class="doctor-modal-img" id="doctorModalImg">
                        <div class="doctor-modal-avatar" id="doctorModalAvatar" aria-hidden="true"></div>
                    </div>
                    <div class="col-md-7">
                        <span class="doctor-specialty" id="doctorModalSpecialty">General medicine</span>
                        <h3 class="modal-doctor-name" id="doctorModalName">Dr. Elena Rossi</h3>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="modal-rating">
                                <i class="bi bi-star-fill" aria-hidden="true"></i>
                                <span id="doctorModalRating">4.9</span>
                            </span>
                            <span class="text-muted-soft small" id="doctorModalExp">12 years experience</span>
                        </div>
                        <p class="text-muted-soft small mb-0" id="doctorModalBio">
                            Elena takes a whole-person approach to general medicine, with a special interest
                            in preventive care and women's health.
                        </p>

                        <ul class="doctor-detail-list">
                            <li>
                                <i class="bi bi-translate" aria-hidden="true"></i>
                                <div>
                                    <span>Languages</span>
                                    <strong id="doctorModalLanguages">English, Italian</strong>
                                </div>
                            </li>
                            <li>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                <div>
                                    <span>Next available</span>
                                    <strong id="doctorModalNext">Today, 4:30 PM</strong>
                                </div>
                            </li>
                            <li>
                                <i class="bi bi-patch-check" aria-hidden="true"></i>
                                <div>
                                    <span>Focus areas</span>
                                    <strong id="doctorModalFocus">Preventive care, women's health</strong>
                                </div>
                            </li>
                        </ul>

                        <button type="button" class="btn btn-brand w-100" id="doctorModalBook">
                            Book with this doctor <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================= AVAILABILITY MODAL ======================= -->
<div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content availability-modal">
            <div class="modal-header align-items-start">
                <div>
                    <span class="eyebrow">Diary</span>
                    <h2 class="modal-title mt-1" id="availabilityTitle">Check availability</h2>
                    <p class="text-muted-soft small mb-0" id="availabilityWhen">Select a service, date and time.</p>
                </div>
                <button type="button" class="btn-close mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="availability-summary" id="availabilitySummary" role="status" aria-live="polite"></div>
                <div class="availability-list" id="availabilityList"></div>
                <p class="availability-footnote">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    Updated · Seychelles time (SCT). Prefer to talk?
                    <a href="tel:+2484388000">+248 438 8000</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ======================= CONFIRMATION MODAL ======================= -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <div class="confirmation-icon">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                </div>
                <h2 class="modal-title mb-2" id="confirmModalTitle">Review your appointment</h2>
                <p class="text-muted-soft small mb-0">Please check the details below before confirming.</p>

                <ul class="summary-list text-start" id="summaryList">
                    <li><span>Service</span><strong id="summaryService">—</strong></li>
                    <li><span>Doctor</span><strong id="summaryDoctor">First available</strong></li>
                    <li><span>Date</span><strong id="summaryDate">—</strong></li>
                    <li><span>Time</span><strong id="summaryTime">—</strong></li>
                    <li><span>Patient</span><strong id="summaryName">—</strong></li>
                    <li><span>Contact</span><strong id="summaryContact">—</strong></li>
                </ul>

                <p class="text-muted-soft small">
                    You'll receive a confirmation email shortly. Free cancellation up to 24 hours before your visit.
                </p>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-brand" id="confirmBookingBtn">
                        Confirm appointment <i class="bi bi-check2"></i>
                    </button>
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Go back and edit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.SEREIN_CONFIG = {
        slots: <?= json_encode($timeSlots) ?>
    };
</script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
</body>
</html>
