#!/usr/bin/perl

while (<>) {
  $l .= $_;
}

$ENV{'MESSAGE'} = $l;

exec "/usr/local/bin/php", "/web/php.new/forum/bounce.php";

