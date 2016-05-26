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
$messageDetails = $msgs->getMessageDetails($_GET['messageId']);
if(!$messageDetails['status']) {
    echo $messageDetails['message'];
    exit;
}
if (!empty($messageDetails['data'])) {
    header('Content-type:text/html; charset=utf-8');
    foreach ($messageDetails['data']['headers'] as $key => $value) {
        echo '<p><strong>'.$key.':</strong> '.$value.'</p>';
    }
    echo '<a href="send.php?thread='.$messageDetails['data']['threadId'].'">Reply</a><br/>';
    echo '<a href="add_remove_labels.php?messageId='.$_GET['messageId'].'">Add label</a><br/>';
    echo '<h4>Text/Plain</h4>';
    foreach ($messageDetails['data']['body']['text/plain'] as $key => $value) {
        echo $value.'<br/>';
    }
    echo '<h4>Text/HTML</h4>';
    foreach ($messageDetails['data']['body']['text/html'] as $key => $value) {
        echo $value.'<br/>';
    }
    if(!empty($messageDetails['data']['files'])) {
        foreach ($messageDetails['data']['files'] as $key => $value) {
            echo '<a target="_blank" href="attachment.php?messageId='.$_GET['messageId'].'&part_id='.$value.'">Attachment '.($key+1).'</a><br/>';
        }
    }
} else {
    echo 'No details retrieved';
}
