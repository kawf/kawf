<?php

function forum_admin()
{
  global $user;

  return isset($user->cap['Delete']);
}

function forum_moderate()
{
  global $user;

  return isset($user->cap['Moderate']);
}

?>
