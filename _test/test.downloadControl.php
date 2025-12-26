<?php
require_once('../classes/class.downloadControl.php');
$downloadinfo = new downloadControl();
$downloadinfo->setDownloadInfo('test', 'info4spaeth@gmx.de');
$hash = $downloadinfo->setDownloadLog();
sleep(16);
$downloadinfo2 = new downloadControl();
$downloadinfo2->setDownloadInfo('test', 'info4spaeth@gmx.de');
echo $downloadinfo->getDownloadInfo()['filename'].' == '.$downloadinfo2->getDownloadInfo()['filename'].PHP_EOL;
echo $downloadinfo->getDownloadInfo()['user'].' == '.$downloadinfo2->getDownloadInfo()['user'].PHP_EOL;
echo $downloadinfo->getDownloadInfo()['timestamp'].' == '.$downloadinfo2->getDownloadInfo()['timestamp'].PHP_EOL;
echo $downloadinfo->getDownloadInfo()['browserinfo'].' == '.$downloadinfo2->getDownloadInfo()['browserinfo'].PHP_EOL;
if($downloadinfo->hasPermission($hash.'.dwnld', $downloadinfo2->getDownloadInfo())) echo 'TRUE'.PHP_EOL;
else echo 'FALSE'.PHP_EOL;
?>