<?php
$statusClasses = [
    'pending' => 'bg-warning text-dark',
    'confirmed' => 'bg-success',
    'rescheduled' => 'bg-info text-dark',
    'cancelled' => 'bg-danger',
    'completed' => 'bg-secondary',
];

$patientCancelled = is_patient_cancelled($appointment);
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h3 class="h6 mb-1">
                    <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge <?= $statusClasses[$appointment['status']] ?? 'bg-secondary' ?> ms-2">
                        <?= htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </h3>
                <div class="text-muted small">
                    <?= htmlspecialchars($appointment['service'], ENT_QUOTES, 'UTF-8') ?>
                    · <?= htmlspecialchars($appointment['appointment_date'], ENT_QUOTES, 'UTF-8') ?>
                    at <?= htmlspecialchars($appointment['appointment_time'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="text-end small">
                <a href="mailto:<?= htmlspecialchars($appointment['email'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($appointment['email'], ENT_QUOTES, 'UTF-8') ?>
                </a><br>
                <a href="tel:<?= htmlspecialchars($appointment['phone'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($appointment['phone'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>

        <?php if (!empty($appointment['notes'])): ?>
            <p class="small mb-2"><strong>Patient notes:</strong> <?= htmlspecialchars($appointment['notes'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($appointment['doctor_message'])): ?>
            <div class="alert alert-light border small mb-2">
                <strong>Your message:</strong> <?= htmlspecialchars($appointment['doctor_message'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($appointment['status'] === 'rescheduled' && $appointment['proposed_date']): ?>
                    <br><strong>Proposed:</strong>
                    <?= htmlspecialchars($appointment['proposed_date'], ENT_QUOTES, 'UTF-8') ?>
                    at <?= htmlspecialchars($appointment['proposed_time'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($appointment['status'] === 'cancelled' && !empty($appointment['cancelled_by'])): ?>
            <div class="alert alert-danger py-2 small mb-2">
                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                <strong>Cancelled by <?= htmlspecialchars(strtolower(cancellation_actor_label($appointment['cancelled_by_role'])), ENT_QUOTES, 'UTF-8') ?>:</strong>
                <?= htmlspecialchars($appointment['cancelled_by'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($appointment['cancelled_at'])): ?>
                    <br><?= htmlspecialchars($appointment['cancelled_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($appointment['cancellation_reason'])): ?>
                    <br><strong>Reason:</strong> <?= htmlspecialchars($appointment['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($patientCancelled): ?>
            <div class="border-top pt-3">
                <p class="small text-muted mb-2">
                    This appointment was cancelled by the patient. You can only send a thank-you message.
                </p>
                <form method="POST" action="process_appointment_action.php" class="row g-2">
                    <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
                    <input type="hidden" name="action" value="feedback">
                    <div class="col-12">
                        <input type="text" name="message" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($appointment['doctor_message'] ?: 'Thank you for informing us.', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Thank you for informing us.">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Send message</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <form method="POST" action="process_appointment_action.php" class="border-top pt-3">
                <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Action</label>
                        <select name="action" class="form-select form-select-sm" required>
                            <option value="confirm">Confirm</option>
                            <option value="reschedule">Offer new time</option>
                            <option value="cancel">Cancel</option>
                            <option value="complete">Completed</option>
                            <option value="feedback">Save feedback</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">New date</label>
                        <input type="date" name="proposed_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">New time</label>
                        <input type="time" name="proposed_time" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Message to patient</label>
                        <input type="text" name="message" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($appointment['doctor_message'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Optional message or feedback">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-brand btn-sm">Update appointment</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
