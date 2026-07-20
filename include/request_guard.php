<?php

if (!function_exists('request_guard_fingerprint')) {
    function request_guard_fingerprint(string $scope, array $payload): string
    {
        return hash(
            'sha256',
            $scope . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
    }
}

if (!function_exists('request_guard_is_duplicate')) {
    function request_guard_is_duplicate(string $scope, array $payload, int $ttlSeconds = 10): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['request_guard']) || !is_array($_SESSION['request_guard'])) {
            $_SESSION['request_guard'] = [];
        }

        $now = time();
        foreach ($_SESSION['request_guard'] as $guardScope => $items) {
            if (!is_array($items)) {
                unset($_SESSION['request_guard'][$guardScope]);
                continue;
            }

            foreach ($items as $fingerprint => $createdAt) {
                if (!is_int($createdAt) || ($now - $createdAt) > $ttlSeconds) {
                    unset($_SESSION['request_guard'][$guardScope][$fingerprint]);
                }
            }

            if (empty($_SESSION['request_guard'][$guardScope])) {
                unset($_SESSION['request_guard'][$guardScope]);
            }
        }

        $fingerprint = request_guard_fingerprint($scope, $payload);

        if (isset($_SESSION['request_guard'][$scope][$fingerprint])) {
            return true;
        }

        $_SESSION['request_guard'][$scope][$fingerprint] = $now;
        return false;
    }
}
