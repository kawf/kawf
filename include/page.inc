<?php

// some layout functions
function page_header($title)
{
?>
<html>
<head>
<title><?echo $title;?></title>
<link rel=StyleSheet href="/css/main.css" type="text/css">
</head>

<body bgcolor="#FFFFFF">
<table border="0" cellspacing="0" cellpadding="1" align="center" bgcolor="#000099">
  <tr>
    <td>
      <table border="0" align="center" bgcolor="#FFFFFF" cellspacing="0" cellpadding="5">
        <tr bgcolor="#000099"> 
          <td><span class="heading"><?echo $title;?></span></td>
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
        <tr bgcolor="#000099"> 
          <td><span class="footing"><a class="footing" href="logout.phtml?token=<? echo $user->token() ?>">Logout</a></span></td>
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
             <table border="0" align="" width="100%">
              <tr bgcolor="#CCCCFF"> 
                <td><b><?echo $message;?></b></td>
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
  <table border="0" align="" width="100%">
    <tr bgcolor="#CCCCFF"> 
      <td><b><?echo $title;?></b><br><?echo $message;?></td>
    </tr>
  </table>
<?php
  page_footer();
  exit;
}

?>
