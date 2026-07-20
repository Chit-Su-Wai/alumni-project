<?php

if (!defined('ALUMNI_OTP_EXPIRY_MINUTES')) {
    define('ALUMNI_OTP_EXPIRY_MINUTES', 10);
}

/**
 * Validate email format and that the domain can receive mail.
 */
function is_valid_existing_email(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = substr(strrchr($email, '@'), 1);
    if ($domain === false || $domain === '') {
        return false;
    }

    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    return true;
}

function render_auth_alert(string $message, string $type = 'error'): string
{
    if ($message === '') {
        return '';
    }

    $classes = $type === 'success'
        ? 'rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700'
        : ($type === 'info'
            ? 'rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-teal-700'
            : 'rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700');

    return '<div class="' . $classes . '" role="alert" aria-live="polite">' . htmlspecialchars($message) . '</div>';
}

function otp_expires_at(int $minutes = ALUMNI_OTP_EXPIRY_MINUTES): string
{
    return date('Y-m-d H:i:s', strtotime('+' . max(1, $minutes) . ' minutes'));
}

function otp_is_expired(?string $expiresAt): bool
{
    if (!$expiresAt) {
        return true;
    }

    return strtotime($expiresAt) !== false && strtotime($expiresAt) < time();
}

function fetch_otp_record(mysqli $conn, string $email, string $otp, string $type): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, expires_at, verified
         FROM otps
         WHERE email = ? AND otp = ? AND type = ?
         ORDER BY created_at DESC
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('sss', $email, $otp, $type);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? ($result->fetch_assoc() ?: null) : null;
}

function mark_otp_verified(mysqli $conn, int $otpId): bool
{
    $stmt = $conn->prepare('UPDATE otps SET verified = 1 WHERE id = ?');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $otpId);
    return $stmt->execute();
}

function delete_otp_records(mysqli $conn, string $email, string $type): bool
{
    $stmt = $conn->prepare('DELETE FROM otps WHERE email = ? AND type = ?');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $email, $type);
    return $stmt->execute();
}
