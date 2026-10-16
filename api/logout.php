<?php

    require_once __DIR__.  '/../env_data.php';
    require_once __DIR__.  '/../util/functions.php';

    if (isset($_COOKIE['session_token']) && is_string($_COOKIE['session_token'])) {
        logout($_COOKIE['session_token']);
    }
    header('Location: /');
?>
