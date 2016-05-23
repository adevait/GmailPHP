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
if (!isset($_GET['messageId'])) {
    header('Location:labels.php');
    exit;
}
$msgs = new Messages($authenticate);
$msgs->addRemoveLabels($_GET['messageId'],['Label_14']);