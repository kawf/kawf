#!/usr/bin/perl

while (<>) {
  $l .= $_;
}

$ENV{'MESSAGE'} = $l;

exec "/usr/local/bin/php", "/web/php.new/kawf/bounce.php";

