<?php

// some layout functions
function page_header($title)
{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?echo $title;?></title>
<link rel=StyleSheet href="<? echo css_href("admin.css")?>" type="text/css">
</head>

<body>
<table class="outer">
  <tr>
    <td>
      <table class="inner">
        <tr>
          <th class="heading"><?echo $title;?></th>
        </tr>
        <tr> 
          <td>
<?php
}
 
function page_footer()
{
  global $user;
?>
         </td>
        </tr>
<?php
  if (isset($user)) {
?>
        <tr>
          <th class="footing"><a href="logout.phtml?token=<? echo $user->token() ?>">Logout</a></th>
        </tr>
<?php
  }
?>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
<?php
} 

// Show a messgae
function page_show_message($message)
{
?>
             <table class="message">
                <td><?echo $message;?></td>
              </tr>
            </table>
<?php
}
 
function page_sql_error($sql)
{
  sql_error($sql, 1);

  page_footer();

  exit;
}

function page_die($title = "Error", $message = "Unknown error")
{
?>
  <table class="error">
    <tr>
      <td><b><?echo $title;?></b><br><?echo $message;?></td>
    </tr>
  </table>
<?php
  page_footer();
  exit;
}

?>
