<?php
use Changwei\PhpLrc;


define('ROOT', dirname(__FILE__));

require '../src/PhpLrc.php';

$objLrc = new PhpLrc(ROOT."\\安河桥.lrc");

$compress = $objLrc->compress();
$decompress = $objLrc->decompress();
$arrNormal = $objLrc->getArrayByLrc(PhpLrc::NORMAL);
$arrMsecond = $objLrc->getArrayByLrc(PhpLrc::MSECOND);

// var_dump($compress);
// var_dump($decompress);

// var_dump($arrNormal);
// var_dump($arrMsecond);

var_dump(PhpLrc::storeToFile($compress, ROOT."\\安河桥（压缩版）.lrc"));
var_dump(PhpLrc::storeToFile($decompress, ROOT."\\安河桥（非压缩版）.lrc"));
