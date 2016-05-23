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
$labels = $msgs->getLabels();
if(!$labels['status']) {
    echo $labels['message'];
    exit;
}
foreach ($labels['data'] as $key => $value) {
    echo '<a href="label_details.php?labelId='.$value->getId().'">'.$value->getName().'</a><br/>';
}