<?php
require_once '../vendor/autoload.php';
require_once('includes/config.php');
require_once('includes/auth.php');

use GmailWrapper\Messages;

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

