<?php
require_once '../vendor/autoload.php';
require_once('includes/config.php');
require_once('includes/auth.php');

use GmailWrapper\Messages;

$msgs = new Messages($authenticate);
$label = $msgs->createLabel('Test Label');