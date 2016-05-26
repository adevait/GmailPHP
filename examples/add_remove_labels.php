<?php
require_once '../vendor/autoload.php';
require_once('includes/config.php');
require_once('includes/auth.php');

use GmailWrapper\Messages;

if (!isset($_GET['messageId'])) {
    header('Location:labels.php');
    exit;
}
$msgs = new Messages($authenticate);
$msgs->addRemoveLabels($_GET['messageId'],[],['Label_14']);