<?php
#random images example
#this is your file
$file = "images.txt";
#open the file
$fp = file($file);
#generate a random number
srand((double)microtime()*1000000);
#get one of the entries in the file
$random_image = rtrim($fp[array_rand($fp)]);
#display the entry
#echo "<img src='$random_image'></img>";


header("Content-Type: image/jpeg");
header("Content-Disposition: inline");
@readfile($random_image);
?>
