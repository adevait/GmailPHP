<?php
require_once '../vendor/autoload.php';
require_once('includes/config.php');
require_once('includes/auth.php');

use GmailWrapper\Messages;

$msgs = new Messages($authenticate);
$labels = $msgs->getLabels();
if(!$labels['status']) {
    echo $labels['message'];
    exit;
}
foreach ($labels['data'] as $key => $value) {
    echo '<a href="label_details.php?labelId='.$value->getId().'">'.$value->getName().'</a><br/>';
}