<?php
Function randomstring($len) {
  srand(time());
  $i = 0; $str = "";
  while($i<$len) {
    $str.=chr((rand()%26)+97);
    $i++;
  }
  return $str;
}
?>
