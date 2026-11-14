/* ==========================================================================
   Serein Health â€” Appointment Booking System
   Task 1 interactions: navigation, carousel helpers, booking form,
   modals, animated counters, progress bars and form validation.
   ========================================================================== */
(function () {
    'use strict';

    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const scrollBehaviour = prefersReducedMotion ? 'auto' : 'smooth';

    /* ----------------------------------------------------------------------
       Local date helpers (avoids UTC timezone surprises)
       ---------------------------------------------------------------------- */
    function toISODate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDate(isoDate) {
        if (!isoDate) return 'â€”';
        const parts = isoDate.split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return isoDate;
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    const today = new Date();
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 90);

    $$('input[type="date"]').forEach((input) => {
        input.min = toISODate(today);
        input.max = toISODate(maxDate);
    });

    /* ----------------------------------------------------------------------
       Navigation: shadow on scroll, active section, mobile menu behaviour
       ---------------------------------------------------------------------- */
    const siteNav = $('#siteNav');
    const backToTop = $('#backToTop');
    const navLinks = $$('.nav-link[data-section]');
    const navSections = navLinks
        .map((link) => $(link.dataset.section))
        .filter(Boolean);

    function updateNavState() {
        if (siteNav) {
            siteNav.classList.toggle('scrolled', window.scrollY > 12);
        }

        if (backToTop) {
            backToTop.classList.toggle('show', window.scrollY > 600);
        }

        if (navSections.length) {
            let current = navSections[0];
            const marker = window.scrollY + 150;

            navSections.forEach((section) => {
                if (section.offsetTop <= marker) current = section;
            });

            const atBottom =
                window.innerHeight + window.scrollY >=
                document.documentElement.scrollHeight - 8;

            if (atBottom) current = navSections[navSections.length - 1];

            navLinks.forEach((link) => {
                link.classList.toggle(
                    'active',
                    link.dataset.section === `#${current.id}`
                );
            });
        }
    }

    let scrollQueued = false;
    window.addEventListener(
        'scroll',
        () => {
            if (scrollQueued) return;
            scrollQueued = true;
            window.requestAnimationFrame(() => {
                updateNavState();
                scrollQueued = false;
            });
        },
        { passive: true }
    );
    updateNavState();

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: scrollBehaviour });
        });
    }

    const mainNav = $('#mainNav');
    const navTogglerIcon = $('.navbar-toggler i');

    if (mainNav && navTogglerIcon && window.bootstrap) {
        mainNav.addEventListener('show.bs.collapse', () => {
            navTogglerIcon.className = 'bi bi-x-lg';
        });
        mainNav.addEventListener('hide.bs.collapse', () => {
            navTogglerIcon.className = 'bi bi-list';
        });
    }

    $$('.nav-link, .nav-actions a').forEach((link) => {
        link.addEventListener('click', () => {
            if (
                mainNav &&
                mainNav.classList.contains('show') &&
                window.innerWidth < 992 &&
                window.bootstrap
            ) {
                window.bootstrap.Collapse.getOrCreateInstance(mainNav).hide();
            }
        });
    });

    /* ----------------------------------------------------------------------
       Reveal-on-scroll
       ---------------------------------------------------------------------- */
    const revealItems = $$('.reveal');

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('in'));
    } else {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -36px 0px' }
        );

        revealItems.forEach((item) => revealObserver.observe(item));
    }

    /* ----------------------------------------------------------------------
       Animated statistics
       ---------------------------------------------------------------------- */
    const statNumbers = $$('.stat-number');

    function runCounter(element) {
        const target = parseFloat(element.dataset.count || '0');
        const decimals = parseInt(element.dataset.decimals || '0', 10);

        if (prefersReducedMotion) {
            element.textContent = target.toFixed(decimals);
            return;
        }

        const duration = 1500;
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = (target * eased).toFixed(decimals);

            if (progress < 1) {
                window.requestAnimationFrame(tick);
            } else {
                element.textContent = target.toFixed(decimals);
            }
        }

        window.requestAnimationFrame(tick);
    }

    if (statNumbers.length) {
        if (!prefersReducedMotion) {
            statNumbers.forEach((stat) => {
                stat.textContent = (0).toFixed(
                    parseInt(stat.dataset.decimals || '0', 10)
                );
            });
        }

        const statsCard = $('.stats-card');

        if (
            statsCard &&
            'IntersectionObserver' in window &&
            !prefersReducedMotion
        ) {
            const statsObserver = new IntersectionObserver(
                (entries, observer) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        statNumbers.forEach(runCounter);
                        observer.disconnect();
                    }
                },
                { threshold: 0.35 }
            );
            statsObserver.observe(statsCard);
        } else {
            statNumbers.forEach(runCounter);
        }
    }

    /* ----------------------------------------------------------------------
       Animated progress bars
       ---------------------------------------------------------------------- */
    const animatedBars = $$('.progress-bar[data-progress]');

    animatedBars.forEach((bar) => {
        bar.style.width = prefersReducedMotion
            ? `${bar.dataset.progress}%`
            : '0%';
    });

    if (!prefersReducedMotion && animatedBars.length) {
        if ('IntersectionObserver' in window) {
            const barObserver = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        const bar = entry.target;
                        const value = parseInt(bar.dataset.progress, 10);
                        bar.style.width = `${value}%`;

                        const track = bar.closest('.progress');
                        if (track) track.setAttribute('aria-valuenow', value);

                        observer.unobserve(bar);
                    });
                },
                { threshold: 0.4 }
            );

            animatedBars.forEach((bar) => barObserver.observe(bar));
        } else {
            animatedBars.forEach((bar) => {
                bar.style.width = `${bar.dataset.progress}%`;
            });
        }
    }

    /* ----------------------------------------------------------------------
       Booking form: live completion progress
       ---------------------------------------------------------------------- */
    const bookingForm = $('#bookingForm');
    const bookingCard = $('#bookingCard');
    const formProgress = $('#formProgress');
    const formProgressLabel = $('#formProgressLabel');
    const formSuccess = $('#formSuccess');
    const successText = $('#successText');

    const requiredFields = bookingForm
        ? $$('[required]', bookingForm)
        : [];

    const bookingServiceField = $('#service');
    const bookingDoctorField = $('#doctor');
    let doctorOptionCache = null;

    function readDoctorOptions() {
        if (doctorOptionCache) return doctorOptionCache;

        doctorOptionCache = [];

        if (!bookingDoctorField) return doctorOptionCache;

        $$('option', bookingDoctorField).forEach((option) => {
            if (option.value === '') return;

            const parent = option.parentElement;
            doctorOptionCache.push({
                value: option.value,
                label: option.textContent.trim(),
                service: option.dataset.service || '',
                group: parent && parent.tagName === 'OPTGROUP' ? parent.label : ''
            });
        });

        return doctorOptionCache;
    }

    function appendDoctorOption(container, item) {
        const option = document.createElement('option');
        option.value = item.value;
        option.textContent = item.label;
        if (item.service) option.dataset.service = item.service;
        container.append(option);
    }

    function syncDoctorOptions() {
        if (!bookingDoctorField || !bookingServiceField) return;

        const selectedService = bookingServiceField.value;
        const previousValue = bookingDoctorField.value;
        const allOptions = readDoctorOptions();
        const visibleOptions = allOptions.filter(
            (item) => selectedService === '' || item.service === selectedService
        );

        bookingDoctorField.innerHTML = '';

        const firstAvailable = document.createElement('option');
        firstAvailable.value = '';
        firstAvailable.textContent = 'First available';
        bookingDoctorField.append(firstAvailable);

        const grouped = new Map();
        visibleOptions.forEach((item) => {
            if (!grouped.has(item.group)) grouped.set(item.group, []);
            grouped.get(item.group).push(item);
        });

        grouped.forEach((items, groupLabel) => {
            if (groupLabel) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = groupLabel;
                items.forEach((item) => appendDoctorOption(optgroup, item));
                bookingDoctorField.append(optgroup);
            } else {
                items.forEach((item) => appendDoctorOption(bookingDoctorField, item));
            }
        });

        const keepPrevious = visibleOptions.some((item) => item.value === previousValue);
        bookingDoctorField.value = keepPrevious ? previousValue : '';

        updateFormProgress();
    }

    function syncServiceFromDoctor() {
        if (!bookingDoctorField || !bookingServiceField) return;

        const selected = bookingDoctorField.options[bookingDoctorField.selectedIndex];
        if (!selected || selected.value === '') return;

        const doctorService = selected.dataset.service || '';
        if (doctorService === '' || bookingServiceField.value === doctorService) return;

        bookingServiceField.value = doctorService;
        calculateEstimatedTotal(doctorService);
        syncDoctorOptions();
    }

    function isFieldFilled(field) {
        if (field.type === 'checkbox') return field.checked;
        if (field.tagName === 'SELECT') return field.value !== '';
        return field.value.trim() !== '';
    }

    function updateFormProgress() {
        if (!formProgress || !formProgressLabel) return;

        const total = requiredFields.length;
        if (!total) return;

        const completed = requiredFields.filter(isFieldFilled).length;
        const percentage = Math.round((completed / total) * 100);

        formProgress.style.width = `${percentage}%`;

        const track = formProgress.closest('.progress');
        if (track) track.setAttribute('aria-valuenow', String(percentage));

        formProgressLabel.textContent = `${percentage}% complete`;
    }

    function highlightBookingCard() {
        if (!bookingCard || prefersReducedMotion) return;
        if (typeof bookingCard.animate !== 'function') return;

        bookingCard.animate(
            [
                { boxShadow: '0 0 0 0 rgba(18, 143, 174, 0)' },
                { boxShadow: '0 0 0 7px rgba(18, 143, 174, 0.22)' },
                { boxShadow: '0 0 0 0 rgba(18, 143, 174, 0)' }
            ],
            { duration: 1500, easing: 'ease-out' }
        );
    }

    function goToBooking() {
        const target = $('#book');
        if (!target) return;
        target.scrollIntoView({ behavior: scrollBehaviour, block: 'start' });
        highlightBookingCard();
    }

    /* ----------------------------------------------------------------------
       Live availability checker
       Front-end diary simulation for Task 1. The markup and interactions are
       ready to be pointed at the MySQL appointment table in Task 2.
       ---------------------------------------------------------------------- */
    const SERVICE_LABELS = {
        general: 'General Medicine',
        dental: 'Dental Care',
        physio: 'Physiotherapy',
        skin: 'Skin & Aesthetics',
        mental: 'Mental Wellness',
        nutrition: 'Nutrition & Dietetics'
    };

    const clinicDoctors = [
        { id: 'elena', name: 'Dr. Elena Rossi', service: 'general', role: 'General medicine', initials: 'ER', tone: 'ocean', rating: '4.9', busy: 46, blockDay: 12, saturday: true },
        { id: 'priya', name: 'Dr. Priya Nair', service: 'general', role: 'General medicine', initials: 'PN', tone: 'lagoon', rating: '4.8', busy: 36, blockDay: 8, saturday: false },
        { id: 'arjun', name: 'Dr. Arjun Mehta', service: 'dental', role: 'Dental surgery', initials: 'AM', tone: 'coral', rating: '4.8', busy: 44, blockDay: 10, saturday: true },
        { id: 'samuel', name: 'Dr. Samuel Labiche', service: 'dental', role: 'Dental surgery', initials: 'SL', tone: 'palm', rating: '4.7', busy: 34, blockDay: 7, saturday: false },
        { id: 'lucas', name: 'Dr. Lucas Ferreira', service: 'physio', role: 'Physiotherapy', initials: 'LF', tone: 'gold', rating: '4.9', busy: 45, blockDay: 11, saturday: true },
        { id: 'claire', name: 'Dr. Claire Antat', service: 'physio', role: 'Physiotherapy', initials: 'CA', tone: 'sunset', rating: '4.8', busy: 35, blockDay: 7, saturday: false },
        { id: 'maya', name: 'Dr. Maya Chen', service: 'skin', role: 'Dermatology', initials: 'MC', tone: 'lagoon', rating: '5.0', busy: 48, blockDay: 12, saturday: false },
        { id: 'nadia', name: 'Dr. Nadia Payet', service: 'skin', role: 'Dermatology', initials: 'NP', tone: 'ocean', rating: '4.9', busy: 37, blockDay: 8, saturday: true },
        { id: 'amara', name: 'Dr. Amara Hoareau', service: 'mental', role: 'Mental wellness', initials: 'AH', tone: 'palm', rating: '4.9', busy: 40, blockDay: 9, saturday: true },
        { id: 'theo', name: 'Dr. Theo Barbier', service: 'mental', role: 'Mental wellness', initials: 'TB', tone: 'sunset', rating: '4.8', busy: 33, blockDay: 6, saturday: false },
        { id: 'jean', name: 'Dr. Jean Morel', service: 'nutrition', role: 'Nutrition & dietetics', initials: 'JM', tone: 'gold', rating: '4.7', busy: 38, blockDay: 8, saturday: false },
        { id: 'elodie', name: 'Dr. Elodie Fanchette', service: 'nutrition', role: 'Nutrition & dietetics', initials: 'EF', tone: 'coral', rating: '4.8', busy: 32, blockDay: 6, saturday: true }
    ];

    const clinicSlots = (window.SEREIN_CONFIG && Array.isArray(window.SEREIN_CONFIG.slots) && window.SEREIN_CONFIG.slots.length)
        ? window.SEREIN_CONFIG.slots.slice()
        : [
            '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
            '11:00', '11:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00'
        ];

    /* Saturday appointments run 9:00 AM â€“ 2:00 PM; Sunday is closed. */
    const saturdaySlots = clinicSlots.filter((slot) => slot >= '09:00' && slot <= '13:30');

    const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const WEEKDAYS_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const quickForm = $('#quickForm');
    const quickService = $('#quickService');
    const quickDate = $('#quickDate');
    const quickTime = $('#quickTime');
    const availabilityModalElement = $('#availabilityModal');
    const availabilityTitle = $('#availabilityTitle');
    const availabilityWhen = $('#availabilityWhen');
    const availabilitySummary = $('#availabilitySummary');
    const availabilityList = $('#availabilityList');
    let currentAvailability = null;

    function parseISODate(iso) {
        const parts = String(iso).split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatTimeLabel(time) {
        const [hours, minutes] = time.split(':').map(Number);
        const suffix = hours >= 12 ? 'PM' : 'AM';
        return `${hours % 12 || 12}:${String(minutes).padStart(2, '0')} ${suffix}`;
    }

    function formatDay(iso, short = false) {
        const date = parseISODate(iso);
        const weekday = short ? WEEKDAYS[date.getDay()] : WEEKDAYS_LONG[date.getDay()];
        const month = short ? MONTHS[date.getMonth()] : MONTHS_LONG[date.getMonth()];
        return `${weekday} ${date.getDate()} ${month}`;
    }

    function relativeDay(iso) {
        const todayISO = toISODate(new Date());
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        if (iso === todayISO) return 'today';
        if (iso === toISODate(tomorrow)) return 'tomorrow';
        return formatDay(iso, true);
    }

    function dayPhrase(iso) {
        const todayISO = toISODate(new Date());
        if (iso === todayISO) return 'today';
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        if (iso === toISODate(tomorrow)) return 'tomorrow';
        const diff = Math.round((parseISODate(iso) - parseISODate(todayISO)) / 86400000);
        if (diff > 1 && diff < 7) return `this ${WEEKDAYS_LONG[parseISODate(iso).getDay()]}`;
        return formatDay(iso, true);
    }

    function slotWhen(iso, time) {
        return `${relativeDay(iso)} at ${formatTimeLabel(time)}`;
    }

    function hashString(value) {
        let hash = 2166136261;
        for (let index = 0; index < value.length; index++) {
            hash ^= value.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }
        return hash >>> 0;
    }

    function slotsForDate(iso) {
        const day = parseISODate(iso).getDay();
        if (day === 0) return [];
        if (day === 6) return saturdaySlots;
        return clinicSlots;
    }

    function doctorWorksOn(doctor, iso) {
        const day = parseISODate(iso).getDay();
        if (day === 0) return false;
        if (day === 6) return Boolean(doctor.saturday);
        return true;
    }

    function slotHasPassed(iso, time) {
        const now = new Date();
        const todayISO = toISODate(now);
        if (iso < todayISO) return true;
        if (iso > todayISO) return false;
        const [hours, minutes] = time.split(':').map(Number);
        return (hours * 60 + minutes) <= (now.getHours() * 60 + now.getMinutes());
    }

    /* Deterministic diary simulation: the same doctor/date/time always returns
       the same answer, so the demo stays consistent across page reloads. */
    function slotIsBooked(doctor, iso, time) {
        const todayISO = toISODate(new Date());
        let dayBlockRate = doctor.blockDay || 0;
        if (iso === todayISO) dayBlockRate += 6;
        if ((hashString(`${doctor.id}|${iso}|day`) % 100) < dayBlockRate) return true;

        let rate = doctor.busy || 40;
        if (iso === todayISO) rate += 18;
        if (parseISODate(iso).getDay() === 6) rate += 6;
        return (hashString(`${doctor.id}|${iso}|${time}`) % 100) < rate;
    }

    function findNextFree(doctor, fromISO, fromTime) {
        const start = parseISODate(fromISO);
        for (let offset = 0; offset <= 60; offset++) {
            const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + offset);
            const iso = toISODate(date);
            if (!doctorWorksOn(doctor, iso)) continue;
            for (const slot of slotsForDate(iso)) {
                if (offset === 0 && fromTime && slot <= fromTime) continue;
                if (slotHasPassed(iso, slot)) continue;
                if (!slotIsBooked(doctor, iso, slot)) return { date: iso, time: slot };
            }
        }
        return null;
    }

    function findNextForService(serviceDoctors, fromISO, fromTime) {
        let best = null;
        serviceDoctors.forEach((doctor) => {
            const next = findNextFree(doctor, fromISO, fromTime);
            if (!next) return;
            if (!best || `${next.date} ${next.time}` < `${best.date} ${best.time}`) {
                best = { date: next.date, time: next.time, doctor };
            }
        });
        return best;
    }

    function renderAvailability(service, iso, time) {
        const serviceDoctors = clinicDoctors.filter((doctor) => doctor.service === service);
        const clinicOpen = slotsForDate(iso).includes(time);
        const passed = slotHasPassed(iso, time);
        let freeCount = 0;

        availabilityTitle.textContent = `${SERVICE_LABELS[service] || 'Appointment'} Â· ${formatDay(iso)}`;
        availabilityWhen.textContent = `Requested: ${formatDay(iso, true)} at ${formatTimeLabel(time)}`;

        const rows = serviceDoctors.map((doctor) => {
            const works = doctorWorksOn(doctor, iso);
            const free = clinicOpen && works && !passed && !slotIsBooked(doctor, iso, time);
            if (free) freeCount++;

            let pill = `Free at ${formatTimeLabel(time)}`;
            let tone = 'free';
            if (!clinicOpen) {
                pill = 'Clinic closed';
                tone = 'muted';
            } else if (passed) {
                pill = 'Time has passed';
                tone = 'muted';
            } else if (!works) {
                pill = 'Not in clinic';
                tone = 'muted';
            } else if (!free) {
                pill = 'Fully booked';
                tone = 'full';
            }

            const next = free ? null : findNextFree(doctor, iso, time);
            const action = free
                ? `<button type="button" class="btn btn-brand btn-sm" data-avail-date="${iso}" data-avail-time="${time}" data-avail-doctor="${doctor.name}">Book ${formatTimeLabel(time)}</button>`
                : (next
                    ? `<button type="button" class="btn btn-outline-brand btn-sm" data-avail-date="${next.date}" data-avail-time="${next.time}" data-avail-doctor="${doctor.name}">Book next slot</button>`
                    : '<a class="btn btn-outline-brand btn-sm" href="tel:+2484388000">Call us</a>');

            return `
                <article class="availability-row">
                    <span class="availability-avatar tone-${doctor.tone}" aria-hidden="true">${doctor.initials}</span>
                    <div class="availability-person">
                        <h3>${doctor.name}</h3>
                        <p>${doctor.role}
                            <span class="availability-rating"><i class="bi bi-star-fill" aria-hidden="true"></i>${doctor.rating}</span>
                        </p>
                    </div>
                    <div class="availability-action">
                        <span class="availability-pill is-${tone}">${pill}</span>
                        ${next ? `<span class="availability-next">Next free <strong>${slotWhen(next.date, next.time)}</strong></span>` : ''}
                        ${action}
                    </div>
                </article>`;
        }).join('');

        const total = serviceDoctors.length;
        const workingDoctors = serviceDoctors.filter((doctor) => doctorWorksOn(doctor, iso));
        const allWorkingBooked = workingDoctors.length === total
            && workingDoctors.every((doctor) => slotIsBooked(doctor, iso, time));
        const nextOverall = findNextForService(serviceDoctors, iso, time);

        let summaryTone = 'free';
        let summaryIcon = 'bi-check2-circle';
        let summary = '';

        if (freeCount > 0) {
            const freeLabel = freeCount === total
                ? 'Both doctors are'
                : (freeCount === 1 ? `1 of ${total} doctors is` : `${freeCount} of ${total} doctors are`);
            summary = `<strong>${freeLabel} free at ${formatTimeLabel(time)} on ${formatDay(iso)}.</strong> Choose a doctor below to continue.`;
        } else if (!clinicOpen) {
            summaryTone = 'muted';
            summaryIcon = 'bi-clock';
            summary = `The clinic is closed at ${formatTimeLabel(time)} on ${formatDay(iso)}.`;
        } else if (passed) {
            summaryTone = 'muted';
            summaryIcon = 'bi-clock-history';
            summary = `${formatTimeLabel(time)} has already passed today.`;
        } else if (workingDoctors.length === 0) {
            summaryTone = 'muted';
            summaryIcon = 'bi-person-x';
            summary = `Our ${(SERVICE_LABELS[service] || '').toLowerCase()} doctors are not in clinic on ${formatDay(iso)}.`;
        } else if (allWorkingBooked) {
            summaryTone = 'full';
            summaryIcon = 'bi-calendar-x';
            summary = `<strong>Both doctors are fully booked at ${formatTimeLabel(time)} on ${formatDay(iso)}.</strong>`;
        } else {
            summaryTone = 'full';
            summaryIcon = 'bi-calendar-x';
            summary = `No doctors are free at ${formatTimeLabel(time)} on ${formatDay(iso)}.`;
        }

        if (freeCount === 0 && nextOverall) {
            summary += ` The next free appointment is <strong>${slotWhen(nextOverall.date, nextOverall.time)}</strong>. Would you like to book ${dayPhrase(nextOverall.date)}?`;
        }

        const summaryAction = freeCount === 0 && nextOverall
            ? `<button type="button" class="btn btn-brand btn-sm" data-avail-date="${nextOverall.date}" data-avail-time="${nextOverall.time}" data-avail-doctor="${nextOverall.doctor.name}">Book ${formatDay(nextOverall.date, true)}, ${formatTimeLabel(nextOverall.time)}</button>`
            : '';

        availabilitySummary.className = `availability-summary is-${summaryTone}`;
        availabilitySummary.innerHTML = `
            <span class="availability-summary-icon"><i class="bi ${summaryIcon}" aria-hidden="true"></i></span>
            <div class="availability-summary-copy">
                <p>${summary}</p>
                ${summaryAction}
            </div>`;
        availabilityList.innerHTML = rows;

        currentAvailability = { service };
    }

    function prefillBooking(service, doctor, iso, time) {
        const serviceField = $('#service');
        const doctorField = $('#doctor');
        const dateField = $('#date');
        const timeField = $('#time');

        if (serviceField) {
            serviceField.value = service;
            syncDoctorOptions();
        }

        if (doctorField && doctor) {
            const match = $$('option', doctorField).find((option) => option.value === doctor);
            if (match) doctorField.value = doctor;
        }

        if (dateField) dateField.value = iso;

        if (timeField) {
            if (!$$('option', timeField).some((option) => option.value === time)) {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = formatTimeLabel(time);
                timeField.append(option);
            }
            timeField.value = time;
        }

        updateFormProgress();
    }

    function completeAvailabilityBooking(doctor, iso, time) {
        if (!currentAvailability) return;
        prefillBooking(currentAvailability.service, doctor, iso, time);

        if (window.bootstrap && availabilityModalElement) {
            window.bootstrap.Modal.getOrCreateInstance(availabilityModalElement).hide();
        }

        window.setTimeout(() => {
            goToBooking();
            const firstName = $('#firstName');
            if (firstName) firstName.focus({ preventScroll: true });
        }, 320);
    }

    function openAvailability() {
        if (!quickService || !quickDate || !quickTime || !availabilityModalElement || !window.bootstrap) {
            return false;
        }
        renderAvailability(quickService.value, quickDate.value, quickTime.value);
        window.bootstrap.Modal.getOrCreateInstance(availabilityModalElement).show();
        return true;
    }

    if (quickForm) {
        quickForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!quickForm.checkValidity()) {
                quickForm.classList.add('was-validated');
                return;
            }

            if (!openAvailability()) {
                prefillBooking(quickService.value, '', quickDate.value, quickTime.value);
                goToBooking();
            }
        });
    }

    if (availabilityModalElement) {
        availabilityModalElement.addEventListener('click', (event) => {
            const button = event.target.closest('[data-avail-date]');
            if (!button) return;
            completeAvailabilityBooking(
                button.dataset.availDoctor,
                button.dataset.availDate,
                button.dataset.availTime
            );
        });
    }

    if (bookingForm) {
        bookingForm.addEventListener('input', updateFormProgress);
        bookingForm.addEventListener('change', updateFormProgress);
        updateFormProgress();
    }

    if (bookingServiceField && bookingDoctorField) {
        bookingServiceField.addEventListener('change', syncDoctorOptions);
        bookingDoctorField.addEventListener('change', syncServiceFromDoctor);
        syncDoctorOptions();
    }

    const copyBookingTokenBtn = $('#copyBookingToken');
    const bookingAccessToken = $('#bookingAccessToken');
    const bookingTokenModal = $('#bookingTokenModal');

    if (bookingTokenModal && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(bookingTokenModal).show();
    }

    if (copyBookingTokenBtn && bookingAccessToken) {
        copyBookingTokenBtn.addEventListener('click', async () => {
            const token = bookingAccessToken.value.trim();
            if (!token) return;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(token);
                } else {
                    bookingAccessToken.select();
                    document.execCommand('copy');
                }

                copyBookingTokenBtn.textContent = 'Copied!';
                window.setTimeout(() => {
                    copyBookingTokenBtn.textContent = 'Copy';
                }, 2000);
            } catch (error) {
                bookingAccessToken.select();
            }
        });
    }

    /* ----------------------------------------------------------------------
       Doctor profile modal
       ---------------------------------------------------------------------- */
    const doctors = {
        elena: {
            name: 'Dr. Elena Rossi',
            specialty: 'General medicine',
            img: 'assets/img/doctors/doctor-1.jpg',
            rating: '4.9',
            experience: '12 years experience',
            languages: 'English, Italian',
            next: 'Today, 4:30 PM',
            focus: 'Preventive care, women\'s health, chronic conditions',
            service: 'general',
            bio: 'Elena takes a whole-person approach to general medicine, with a special interest in preventive care and women\'s health. She is known for clear explanations and never rushing a consultation.'
        },
        arjun: {
            name: 'Dr. Arjun Mehta',
            specialty: 'Dental surgery',
            img: 'assets/img/doctors/doctor-2.jpg',
            rating: '4.8',
            experience: '9 years experience',
            languages: 'English, Hindi, Gujarati',
            next: 'Tomorrow, 9:00 AM',
            focus: 'Cosmetic dentistry, fillings, family care',
            service: 'dental',
            bio: 'Arjun combines gentle technique with a calm chair-side manner. From routine cleanings to cosmetic work, he makes sure patients understand every step before treatment begins.'
        },
        maya: {
            name: 'Dr. Maya Chen',
            specialty: 'Dermatology',
            img: 'assets/img/doctors/doctor-3.jpg',
            rating: '5.0',
            experience: '11 years experience',
            languages: 'English, Mandarin',
            next: 'Today, 2:15 PM',
            focus: 'Acne, eczema, skin health and ageing',
            service: 'skin',
            bio: 'Maya is a dermatologist who believes healthy skin starts with a plan you can actually keep. She works with adults and teens on acne, eczema and long-term skin health.'
        },
        lucas: {
            name: 'Dr. Lucas Ferreira',
            specialty: 'Physiotherapy',
            img: 'assets/img/doctors/doctor-4.jpg',
            rating: '4.9',
            experience: '8 years experience',
            languages: 'English, Portuguese',
            next: 'Friday, 10:30 AM',
            focus: 'Sports injury, back pain, rehabilitation',
            service: 'physio',
            bio: 'Lucas helps patients move well and recover with confidence. His sessions blend hands-on treatment with practical movement plans for sport, work and everyday life.'
        },
        priya: {
            name: 'Dr. Priya Nair',
            specialty: 'General medicine',
            img: 'assets/img/doctors/doctor-5.jpg',
            rating: '4.8',
            experience: '10 years experience',
            languages: 'English, Malayalam, Hindi',
            next: 'Today, 11:00 AM',
            focus: 'Preventive care, family medicine, lifestyle',
            service: 'general',
            bio: 'Priya focuses on preventive care and family medicine, helping patients build sustainable, healthy routines. She is known for her warm, unhurried consultations.'
        },
        samuel: {
            name: 'Dr. Samuel Labiche',
            specialty: 'Dental surgery',
            img: 'assets/img/doctors/doctor-6.jpg',
            rating: '4.7',
            experience: '7 years experience',
            languages: 'English, French, Creole',
            next: 'Tomorrow, 9:30 AM',
            focus: 'Orthodontics, cleanings, implants',
            service: 'dental',
            bio: 'Samuel offers gentle, thorough dental care from routine cleanings to orthodontic and implant work, with a focus on patient comfort and clear communication.'
        },
        claire: {
            name: 'Dr. Claire Antat',
            specialty: 'Physiotherapy',
            img: 'assets/img/doctors/doctor-7.jpg',
            rating: '4.8',
            experience: '6 years experience',
            languages: 'English, French, Creole',
            next: 'Friday, 2:00 PM',
            focus: 'Posture, mobility, pain relief',
            service: 'physio',
            bio: 'Claire helps patients recover mobility and relieve pain through tailored exercise and hands-on therapy, with a focus on long-term movement habits.'
        },
        nadia: {
            name: 'Dr. Nadia Payet',
            specialty: 'Dermatology',
            img: 'assets/img/doctors/doctor-8.jpg',
            rating: '4.9',
            experience: '9 years experience',
            languages: 'English, French, Creole',
            next: 'Today, 3:30 PM',
            focus: 'Aesthetics, pigmentation, anti-ageing',
            service: 'skin',
            bio: 'Nadia blends clinical dermatology with aesthetic treatments, helping patients with pigmentation, ageing and skin confidence in a calm, evidence-based way.'
        },
        amara: {
            name: 'Dr. Amara Hoareau',
            specialty: 'Mental wellness',
            img: 'assets/img/doctors/doctor-9.jpg',
            rating: '4.9',
            experience: '10 years experience',
            languages: 'English, French, Creole',
            next: 'Today, 4:00 PM',
            focus: 'Anxiety, stress, therapy',
            service: 'mental',
            bio: 'Amara provides a safe, supportive space for anxiety, stress and life transitions, using evidence-based therapy tailored to each individual.'
        },
        theo: {
            name: 'Dr. Theo Barbier',
            specialty: 'Mental wellness',
            img: 'assets/img/doctors/doctor-10.jpg',
            rating: '4.8',
            experience: '7 years experience',
            languages: 'English, French',
            next: 'Tomorrow, 10:00 AM',
            focus: 'Counselling, mindfulness, burnout',
            service: 'mental',
            bio: 'Theo helps patients manage burnout, stress and emotional wellbeing through counselling and mindfulness-based approaches.'
        },
        jean: {
            name: 'Dr. Jean Morel',
            specialty: 'Nutrition & dietetics',
            img: 'assets/img/doctors/doctor-11.jpg',
            rating: '4.7',
            experience: '8 years experience',
            languages: 'English, French, Creole',
            next: 'Today, 1:30 PM',
            focus: 'Weight, diabetes, sports nutrition',
            service: 'nutrition',
            bio: 'Jean supports patients with weight management, diabetes care and sports nutrition through practical, sustainable dietary plans.'
        },
        elodie: {
            name: 'Dr. Elodie Fanchette',
            specialty: 'Nutrition & dietetics',
            img: 'assets/img/doctors/doctor-12.jpg',
            rating: '4.8',
            experience: '6 years experience',
            languages: 'English, French, Creole',
            next: 'Friday, 11:00 AM',
            focus: 'Gut health, paediatric, wellness',
            service: 'nutrition',
            bio: 'Elodie helps families and individuals improve gut health and overall wellness with gentle, family-friendly nutrition guidance.'
        }
    };

    const doctorModalElement = $('#doctorModal');
    const doctorModalImage = $('#doctorModalImg');
    const doctorModalAvatar = $('#doctorModalAvatar');
    const doctorModalSpecialty = $('#doctorModalSpecialty');
    const doctorModalName = $('#doctorModalName');
    const doctorModalRating = $('#doctorModalRating');
    const doctorModalExp = $('#doctorModalExp');
    const doctorModalBio = $('#doctorModalBio');
    const doctorModalLanguages = $('#doctorModalLanguages');
    const doctorModalNext = $('#doctorModalNext');
    const doctorModalFocus = $('#doctorModalFocus');
    const doctorModalBook = $('#doctorModalBook');

    let activeDoctor = null;

    function showDoctorModal(doctor) {
        if (!doctor || !doctorModalElement || !window.bootstrap) return;

        activeDoctor = doctor;
        if (doctor.img) {
            doctorModalImage.src = doctor.img;
            doctorModalImage.alt = doctor.name;
            doctorModalImage.classList.remove('d-none');
            if (doctorModalAvatar) {
                doctorModalAvatar.className = 'doctor-modal-avatar';
                doctorModalAvatar.classList.add('d-none');
                doctorModalAvatar.textContent = '';
            }
        } else {
            doctorModalImage.classList.add('d-none');
            if (doctorModalAvatar) {
                doctorModalAvatar.className = 'doctor-modal-avatar tone-' + (doctor.tone || 'ocean');
                doctorModalAvatar.classList.remove('d-none');
                doctorModalAvatar.textContent = doctor.initials;
            }
        }
        doctorModalSpecialty.textContent = doctor.specialty;
        doctorModalName.textContent = doctor.name;
        doctorModalRating.textContent = doctor.rating;
        doctorModalExp.textContent = doctor.experience;
        doctorModalBio.textContent = doctor.bio;
        doctorModalLanguages.textContent = doctor.languages;
        doctorModalNext.textContent = doctor.next;
        doctorModalFocus.textContent = doctor.focus;

        window.bootstrap.Modal.getOrCreateInstance(doctorModalElement).show();
    }

    function openDoctorModal(key) {
        const doctor = doctors[key];
        showDoctorModal(doctor);
    }

    function openDynamicDoctorModal(button) {
        const name = button.dataset.name || 'New specialist';
        const initials = name
            .split(' ')
            .filter(Boolean)
            .map((word) => word.charAt(0))
            .slice(-2)
            .join('')
            .toUpperCase();

        showDoctorModal({
            name,
            specialty: button.dataset.specialty || 'General medicine',
            img: button.dataset.image || '',
            rating: 'New',
            experience: button.dataset.specialty || 'New specialist',
            languages: button.dataset.languages || 'Not specified',
            next: 'Accepting patients',
            focus: button.dataset.specialty || 'General medicine',
            service: button.dataset.service || '',
            bio: button.dataset.bio || 'This doctor has not added a bio yet.',
            initials,
            tone: 'ocean'
        });
    }

    $$('.btn-view[data-doctor]').forEach((button) => {
        button.addEventListener('click', () => openDoctorModal(button.dataset.doctor));
    });

    $$('.btn-view[data-dynamic-doctor]').forEach((button) => {
        button.addEventListener('click', () => openDynamicDoctorModal(button));
    });

    if (doctorModalBook) {
        doctorModalBook.addEventListener('click', () => {
            if (!activeDoctor) return;

            const serviceField = $('#service');
            if (serviceField && activeDoctor.service) {
                serviceField.value = activeDoctor.service;
                syncDoctorOptions();
            }

            const doctorField = $('#doctor');
            if (doctorField) {
                const matchingOption = $$('option', doctorField).find(
                    (option) => option.value === activeDoctor.name
                );
                if (matchingOption) doctorField.value = activeDoctor.name;
            }

            updateFormProgress();

            if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(doctorModalElement).hide();
            }

            window.setTimeout(goToBooking, 250);
        });
    }

    /* Book buttons on doctor cards */
    $$('[data-book-doctor]').forEach((button) => {
        button.addEventListener('click', () => {
            const doctorName = button.dataset.bookDoctor;
            const serviceField = $('#service');
            const doctorField = $('#doctor');
            const doctor = Object.values(doctors).find(
                (item) => item.name === doctorName
            );
            const service = (doctor && doctor.service) || button.dataset.bookService || '';

            if (serviceField && service) {
                serviceField.value = service;
                syncDoctorOptions();
            }

            if (doctorField) {
                const match = $$('option', doctorField).find(
                    (option) => option.value === doctorName
                );
                if (match) doctorField.value = doctorName;
            }

            updateFormProgress();
            goToBooking();
        });
    });

    /* Book buttons on service cards */
    $$('[data-book-service]').forEach((button) => {
        button.addEventListener('click', () => {
            const serviceField = $('#service');
            if (serviceField) {
                serviceField.value = button.dataset.bookService;
                syncDoctorOptions();
            }

            updateFormProgress();
            goToBooking();
        });
    });

    /* ----------------------------------------------------------------------
       Booking validation and confirmation modal
       ---------------------------------------------------------------------- */
    const confirmModalElement = $('#confirmModal');
    const confirmBookingBtn = $('#confirmBookingBtn');
    let pendingBooking = null;

    if (bookingForm) {
        bookingForm.addEventListener('submit', (event) => {
            // Allow the confirmed submission to reach the PHP handler.
            if (bookingForm.dataset.confirmed === '1') {
                bookingForm.dataset.confirmed = '';
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (!bookingForm.checkValidity()) {
                bookingForm.classList.add('was-validated');
                const firstInvalid = bookingForm.querySelector(':invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            const serviceField = $('#service');
            const doctorField = $('#doctor');
            const dateField = $('#date');
            const timeField = $('#time');
            const firstNameField = $('#firstName');
            const lastNameField = $('#lastName');
            const emailField = $('#email');
            const phoneField = $('#phone');

            const serviceLabel =
                serviceField.options[serviceField.selectedIndex].text;
            const timeLabel = timeField.options[timeField.selectedIndex].text;
            const fullName =
                `${firstNameField.value.trim()} ${lastNameField.value.trim()}`;
            const contact =
                `${emailField.value.trim()} Â· ${phoneField.value.trim()}`;

            $('#summaryService').textContent = serviceLabel;
            $('#summaryDoctor').textContent =
                doctorField.value || 'First available';
            $('#summaryDate').textContent = formatDate(dateField.value);
            $('#summaryTime').textContent = timeLabel;
            $('#summaryName').textContent = fullName;
            $('#summaryContact').textContent = contact;

            pendingBooking = {
                service: serviceLabel,
                date: formatDate(dateField.value),
                time: timeLabel,
                email: emailField.value.trim()
            };

            if (formSuccess) formSuccess.classList.add('d-none');

            if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(
                    confirmModalElement
                ).show();
            }
        });
    }

    if (confirmBookingBtn) {
        confirmBookingBtn.addEventListener('click', () => {
            if (!pendingBooking) return;

            if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(
                    confirmModalElement
                ).hide();
            }

            if (bookingForm) {
                bookingForm.dataset.confirmed = '1';
                bookingForm.submit();
            }

            pendingBooking = null;
        });
    }

    /* ----------------------------------------------------------------------
       Newsletter
       ---------------------------------------------------------------------- */
    const newsletterForm = $('#newsletterForm');
    const newsletterEmail = $('#newsletterEmail');
    const newsletterSuccess = $('#newsletterSuccess');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (event) => {
            if (!newsletterForm.checkValidity()) {
                event.preventDefault();
                newsletterForm.reportValidity();
                return;
            }
            // Allow the valid submission to reach process_newsletter.php.
        });
    }

    /* ----------------------------------------------------------------------
       Hero slideshow: auto-start (default interval) and keep sliding; respects
       reduced-motion preferences.
       ---------------------------------------------------------------------- */
    const heroCarousel = $('#heroCarousel');
    if (heroCarousel && window.bootstrap) {
        const carousel = window.bootstrap.Carousel.getOrCreateInstance(
            heroCarousel
        );

        if (prefersReducedMotion) {
            carousel.pause();
        } else {
            carousel.cycle();
        }

        const heroSlideNow = $('#heroSlideNow');
        if (heroSlideNow) {
            heroCarousel.addEventListener('slid.bs.carousel', (event) => {
                heroSlideNow.textContent = String(event.to + 1).padStart(2, '0');
            });
        }
    }

    /* ----------------------------------------------------------------------
       Task 2 interactivity
       ---------------------------------------------------------------------- */

    /* Function 1: display a welcome message. */
    function showWelcomeMessage(name) {
        const message = `Welcome to Serein Health${name ? ', ' + name : ''}!`;
        console.log(message);

        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-primary border-0 position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';
        document.body.appendChild(toast);

        if (window.bootstrap && window.bootstrap.Toast) {
            const bsToast = new window.bootstrap.Toast(toast, { delay: 3500 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
    }

    /* Function 2: calculate the estimated total for a selected service. */
    var servicePrices = {
        general: 750,
        dental: 1200,
        physio: 950,
        skin: 1400,
        mental: 1250,
        nutrition: 900
    };

    function calculateEstimatedTotal(serviceKey) {
        const totalElement = $('#estimatedTotal');
        if (!totalElement) return 0;

        const price = servicePrices[serviceKey];
        if (!price) {
            totalElement.textContent = '';
            return 0;
        }

        const total = 'Estimated consultation: SR ' + price.toLocaleString();
        totalElement.textContent = total;
        return price;
    }

    /* Event 1 (onclick): show a welcome message when the page loads. */
    showWelcomeMessage();

    /* Event 2 (onchange): update the estimated total when the service changes. */
    const serviceSelect = $('#service');
    if (serviceSelect) {
        serviceSelect.addEventListener('change', function () {
            calculateEstimatedTotal(serviceSelect.value);
        });
    }

    /* Form validation: booking form checks at least three fields (name, email, phone)
       before submission (handled by Bootstrap's was-validated + checkValidity()). */
    const bookingFormEl = $('#bookingForm');
    if (bookingFormEl) {
        bookingFormEl.addEventListener('submit', function (event) {
            const firstNameField = $('#firstName');
            const lastNameField = $('#lastName');
            const emailField = $('#email');
            const phoneField = $('#phone');

            let valid = true;
            if (!firstNameField || !firstNameField.value.trim()) valid = false;
            if (!lastNameField || !lastNameField.value.trim()) valid = false;
            if (!emailField || !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(emailField.value)) valid = false;
            if (!phoneField || !phoneField.value.trim()) valid = false;

            if (!valid || !bookingFormEl.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                bookingFormEl.classList.add('was-validated');
            }
        });
    }
})();

