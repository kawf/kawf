<?php

unset($user);

$raw = (isset($_REQUEST['url']) && is_string($_REQUEST['url'])) ? $_REQUEST['url'] : '';
$local = safe_local_url($raw);
$url = ($raw !== '' && $local === $raw) ? $raw : '/admin/';

$message = (isset($_REQUEST['message']) && is_string($_REQUEST['message'])) ? $_REQUEST['message'] : '';

$email = (isset($_POST['email']) && is_string($_POST['email'])) ? $_POST['email'] : '';
$password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';

if (isset($_POST['submit'])) {
  $admin_user = new AdminUser;
  $admin_user->find_by_email($email);
  if (!$admin_user->valid() || !$admin_user->checkpassword($password))
    $message = "Invalid email or password";
  else {
    $admin_user->setcookie();
    header("Location: $url");
    exit;
  }
}

page_header("Forum Admin Authentication");
if ($message !== '')
  echo "<span class=\"red\">" . h($message) . "</span><br>\n";
?>
  <form method="post" action="login.phtml">
  <input type="hidden" name="url" value="<?php echo h($url); ?>">
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
