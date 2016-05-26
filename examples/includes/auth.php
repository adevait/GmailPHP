<?php

session_start();

use GmailWrapper\Authenticate;

if (!isset($_SESSION['tokens'])) {
    header('Location:login.php');
    exit;
}
$authenticate = Authenticate::getInstance(CLIENT_ID, CLIENT_SECRET, APPLICATION_NAME, DEVELOPER_KEY);
if (!$authenticate->isTokenValid($_SESSION['tokens'])) {
    if(!isset($_SESSION['tokens']->refresh_token)) {
        header('Location:login.php');
        exit;
    }
    // If the app has offline access, refresh the access token automatically
    $response = $authenticate->refreshToken($_SESSION['tokens']->refresh_token);
    if(!$response['status']) {
        header('Location:login.php');
        exit;
    }
    $_SESSION['tokens'] = $authenticate->getTokens();
}