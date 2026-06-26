
<?php
session_start();

/* Remove All Session Data */

$_SESSION = [];

/* Destroy Session */

session_destroy();

/* Delete Session Cookie */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Redirect */

 header("Location: ../alumni/login.php");
exit;
?>

