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
$msgs = new Messages($authenticate);
$message_list = $msgs->getMessages();
foreach ($message_list as $key => $value) {
    $msgId = $value->getId();
    echo '<a href="message_details.php?message_id='.$msgId.'">'.$msgId.'</a><br/>';
}
