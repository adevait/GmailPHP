<?php
session_start();
require_once '../vendor/autoload.php';
require_once('config.php');

use GmailWrapper\Authenticate;
use GmailWrapper\Messages;
use GmailWrapper\Helper;

if (!isset($_SESSION['tokens'])) {
    header('Location:login.php');
    exit;
}
$authenticate = Authenticate::getInstance(CLIENT_ID, CLIENT_SECRET, APPLICATION_NAME, DEVELOPER_KEY);
if (!$authenticate->isTokenValid($_SESSION['tokens'])) {
    header('Location:login.php');
    exit;
}
if (!isset($_GET['messageId'])) {
    header('Location:messages.php');
    exit;
}
$msgs = new Messages($authenticate);
$attachment = $msgs->getAttachment($_GET['messageId'],$_GET['part_id']);
if(!$attachment['status']) {
    echo $attachment['message'];
    exit;
}
foreach ($attachment['data']['headers'] as $key => $value) {
    header($key.':'.$value);
}
echo $attachment['data']['data'];

