<?php

unset($user);

$url = $_REQUEST['url'];
$message = $_REQUEST['message'];

$email = $_POST['email'];
$password = $_POST['password'];

if (isset($_POST['submit'])) {
  $admin_user = new AdminUser;
  $admin_user->find_by_email($email);
  if (!$admin_user->valid())
    $message = "Invalid email address '$email'\n";
  else if (!$admin_user->checkpassword($password))
    $message = "Invalid password for $email\n";
  else {
    $admin_user->setcookie();
    header("Location: http://$url");
    exit;
  }
}

page_header("Forum Admin Authentication");
if (isset($message))
  echo "<span class=\"red\">$message</span><br>\n";
?>
  <form method="post" action="login.phtml?url=<?php echo $url;?>">
  <table>
   <tr>
    <td>Email:</td>
    <td><input type="text" name="email"></td>
   </tr>
   <tr>
    <td>Password:</td>
    <td><input type="password" name="password"></td>
   </tr>
   <tr>
    <td></td>
    <td><input type="submit" name="submit" value="Login"></td>
   </tr>
  </table>
<?php
page_footer();
exit;
?>
