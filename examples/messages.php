<?php
require_once '../vendor/autoload.php';
require_once('includes/config.php');
require_once('includes/auth.php');

use GmailWrapper\Messages;

$pageToken = isset($_GET['pageToken']) ? $_GET['pageToken'] : null;
$msgs = new Messages($authenticate);
$messageList = $msgs->getMessages([], $pageToken);
if(!$messageList['status']) {
    echo $messageList['message'];
    exit;
}
foreach ($messageList['data'] as $key => $value) {
    $msgId = $value->getId();
    echo '<a href="message_details.php?messageId='.$msgId.'">'.$msgId.'</a><br/>';
}
$nextToken = $messageList['nextToken'];
echo '<p><a href="messages.php?pageToken='.$nextToken.'">Next</a></p>';
