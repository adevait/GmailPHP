<?php
session_start();
require_once '../vendor/autoload.php';
require_once('config.php');

use GmailWrapper\Authenticate;
use GmailWrapper\Messages;

if (!isset($_SESSION['tokens'])) {
    header('Location:login.php');
    exit;
}
$authenticate = Authenticate::getInstance(CLIENT_ID, CLIENT_SECRET, APPLICATION_NAME, DEVELOPER_KEY);
if (!$authenticate->isTokenValid($_SESSION['tokens'])) {
    header('Location:login.php');
    exit;
}
$message = new Messages($authenticate);
$send = $message->send('jondoe@example.com','Test','This is a test message to test google send mail functionality');
echo '<pre>';
var_dump($send);
