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
if(isset($_POST['email']) && isset($_POST['subject']) && isset($_POST['body'])) {
    $message = new Messages($authenticate);
    $thread = isset($_GET['thread']) ? $_GET['thread'] : false;
    $attachments = array();
    if(isset($_FILES['attachment']) && $attachmentCount = count(array_filter($_FILES['attachment']['name']))) {
        $error_message = '';
        for ($i=0; $i < $attachmentCount; $i++) { 
            switch( $_FILES['attachment']['error'][$i] ) {
                case UPLOAD_ERR_OK:
                    array_push($attachments, [$_FILES['attachment']['name'][$i], $_FILES['attachment']['tmp_name'][$i]]);
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message .= '<p>'.($i+1).' File too large.</p>';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message .= '<p>'.($i+1).' File upload was not completed.</p>';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message .= '<p>'.($i+1).' Zero-length file uploaded.</p>';
                    break;
                default:
                    $error_message .= '<p>'.($i+1).' Internal error #'.$_FILES['attachment']['error'][$i].'</p>';
                    break;
            }
            if($error_message) {
                echo $error_message;
                exit;
            }
        }
    }
    $send = $message->send($_POST['email'],$_POST['subject'],$_POST['body'],$attachments,$thread);
    if(!$send['status']) {
        echo $send['message'];
        exit;
    }
    echo 'Message sent. See details below.<br/>';
    echo '<pre>';
    var_dump($send['data']);exit;
}
?>
<style>
    form input, form textarea {
        display: block;
        padding:5px;
        margin:5px;
    }
</style>
<form method="POST" action="" enctype="multipart/form-data">
    <input type="email" name="email" placeholder="Email address">
    <input type="text" name="subject" placeholder="Subject">
    <textarea name="body" id="" cols="30" rows="10" placeholder="Body"></textarea>
    <input type="file" name="attachment[]" multiple>
    <input type="submit" name="submit">
</form>