<?php

require "ZipCompare.class.php";

$obj = new ZipCompare();
$result = $obj->compare('test1.zip','test2.zip');

print_r($result);

?>