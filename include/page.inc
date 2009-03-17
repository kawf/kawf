<?php

// some layout functions
function page_header($title)
{
?>
<html>
<head>
<title><?echo $title;?></title>
<style type="text/css">
<!--
body {  font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
table { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
td { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
.heading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 14pt; font-weight: bold; color: white; margin-bottom: 0px; }
.footing { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; font-weight: bold; color: white; text-decoration: none; margin-bottom: 0px; }
-->
</style>
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
          <td><span class="footing"><a class="footing" href="logout.phtml?cookie=<? echo md5($user->cookie) ?>">Logout</a></span></td>
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
