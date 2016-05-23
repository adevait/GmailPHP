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
if (!isset($_GET['labelId'])) {
    header('Location:labels.php');
    exit;
}
$msgs = new Messages($authenticate);
$label_details = $msgs->getLabelDetails($_GET['labelId']);
if(!$label_details['status']) {
    echo $label_details['message'];exit;
}
$label_details = $label_details['data'];
$label_name = $label_details->getName();
echo '<h1>'.$label_name.'</h1>';
$message_list = $msgs->getMessages(['q' => 'label:'.$label_name]);
if(!$message_list['status']) {
    echo $message_list['status'];exit;
}
foreach ($message_list['data'] as $key => $value) {
    $msgId = $value->getId();
    echo '<a href="message_details.php?messageId='.$msgId.'">'.$msgId.'</a><br/>';
}
