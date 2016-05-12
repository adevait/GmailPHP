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
if (!isset($_GET['message_id'])) {
    header('Location:messages.php');
    exit;
}
$msgs = new Messages($authenticate);
$message_details = $msgs->getMessageDetails($_GET['message_id']);
if (!empty($message_details)) {
    header('Content-type:text/html; charset=utf-8');
    foreach ($message_details['Headers'] as $key => $value) {
        echo '<p><strong>'.$key.':</strong> '.$value.'</p>';
    }
    echo $message_details['Message'];
} else {
    echo 'No details retrieved';
}
