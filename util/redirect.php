<?php
try {
    require_once __DIR__ . '/../vendor/autoload.php'; 
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/functions.php';

    $client = new Google\Client; 
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);

    // Le state renvoyé par Google doit être celui posé en cookie (google_auth_url)
    $expectedState = $_COOKIE['oauth_state'] ?? '';
    $state = $_GET['state'] ?? '';
    if (!isset($_GET['code']) || !is_string($expectedState) || !is_string($state)
        || $expectedState === '' || !hash_equals($expectedState, $state)) {
        echo "login failed";
        exit;
    }
    setcookie('oauth_state', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $oauth = new Google\Service\Oauth2($client);
    $userinfo = $oauth->userinfo->get(); 

    if(! user_exists($userinfo->email)){
        create_user($userinfo->email, $userinfo->givenName, $userinfo->picture);
    }
    $sessionToken = base64_encode(random_bytes(32));
    saveSessionToDb($sessionToken, $userinfo->email);
    //setcookie('session_token',"baby miko", time() +$_30days , "/"); 
    setcookie('session_token', $sessionToken, [
        'expires'  => time() + 86400 * SESSION_LIFETIME_DAYS,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    header('Location: /pages/dashboard.php');
} catch (\Throwable $th) {
    if (env_type()=="dev") {
        echo $th;
    }
}

?>
