<?php
/* Check's a crypt()'d password against a plaintext password */
function password_check($cpassword, $password) {
  /* Salt string */
  static $saltstr="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./";

  if (md5($password) == $cpassword)
    return 1;

  /* Two characters are used for the salt */
  for ($i = 0; $i < 64; $i++) {
    for ($j = 0; $j < 64; $j++) {
      /* Make the salt string */
      $salt = substr($saltstr, $i, 1) . substr($saltstr, $j, 1);

      /* Check it */
      if (crypt($password, $salt) == $cpassword)
        return(1);
    }
  }

  /* Didn't find it */
  return(0);
}
?>
