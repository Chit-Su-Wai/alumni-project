<?php
/**
 * lang/lang.php
 * Serves as a JavaScript file that injects window.LANG translations.
 * Include as: <script src="../lang/lang.php"></script>
 *
 * Language priority:
 *  1. ?lang= URL param  (lets URL override)
 *  2. $_SESSION['lang'] (remembered across pages)
 *  3. Default: 'en'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine requested language
$lang = 'en';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'mm'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'mm'])) {
    $lang = $_SESSION['lang'];
}

// Load translation array
$langFile = __DIR__ . '/' . $lang . '.php';
$strings  = file_exists($langFile) ? require $langFile : [];

// Output as JavaScript global — serve as JS
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');

echo "window.LANG_CODE = " . json_encode($lang) . ";\n";
echo "window.LANG = " . json_encode($strings, JSON_UNESCAPED_UNICODE) . ";\n";
