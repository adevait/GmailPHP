<?php
session_start();

require_once '../vendor/autoload.php';
require_once('config.php');

use GmailWrapper\Authenticate;

$authenticate = Authenticate::getInstance(CLIENT_ID,CLIENT_SECRET,APPLICATION_NAME,DEVELOPER_KEY);


if(!$authenticate->isAuthenticated()) {
    $loginUrl = $authenticate->getLogInURL('http://mwrap.com/examples/login.php', ['openid','https://www.googleapis.com/auth/gmail.readonly']);
    echo "<a href='{$loginUrl}'>Login</a>";
}
if($authenticate->logIn()) {
    $_SESSION['tokens'] = $authenticate->getTokens();
    echo '<pre>';
    var_dump($authenticate->getUserDetails());
}