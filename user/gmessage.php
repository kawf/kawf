<?php

if (!$user->valid()) {
    err_not_found();
}

$gid = $_REQUEST['gid'];

if (!isset($gid) || is_int($gid) || $gid < -1 || $gid > 63) {
    err_not_found();
}

$gmsg = sql_querya("select * from f_global_messages where gid = '" . addslashes($gid) . "'");

if (strlen($_REQUEST['state'])>0) {
    if (!$gmsg || !$user->admin()) {
	err_not_found();
    }

    if ($_REQUEST['token'] != $user->token()) {
	err_not_found('Invalid token');
    }

    if ($_REQUEST['state'] == "Active") {
	sql_query("update f_global_messages set state = 'Active' where gid = '" . addslashes($gid) . "'");
    } elseif ($_REQUEST['state'] == "Inactive") {
	sql_query("update f_global_messages set state = 'Inactive' where gid = '" . addslashes($gid) . "'");
    } else {
	err_not_found();
    }
}

if (isset($_REQUEST['hide'])) {
    if ($_REQUEST['token'] != $user->token()) {
      err_not_found('Invalid token');
    }

    if ($gid==-1) {
	if ($_REQUEST['hide']==1) {	/* hide all */
	      sql_query("update u_users set gmsgfilter = " . (~0) ." where aid = '" . $user->aid . "'");
	} else {			/* show all */
	      sql_query("update u_users set gmsgfilter = 0 where aid = '" . $user->aid . "'");
	}
    } elseif ($gmsg) {
	if ($_REQUEST['hide']==1) {	/* hide gid */
	    if (!($user->gmsgfilter & (1 << $gid)))
	      sql_query("update u_users set gmsgfilter = " . ($user->gmsgfilter | (1 << $gid)) . " where aid = '" . $user->aid . "'");
	} elseif ($_REQUEST['hide']==0) { /* show gid */
	    if ($user->gmsgfilter & (1 << $gid))
	      sql_query("update u_users set gmsgfilter = " . ($user->gmsgfilter & ~(1 << $gid)) . " where aid = '" . $user->aid . "'");
	}
    } else {
	err_not_found();
    }
}

if (isset($_REQUEST['page'])) {
    header("Location: " . $_REQUEST['page']);
} elseif (strlen($gmsg['url'])>0) {
    header("Location: " . $gmsg['url']);
} else {
    err_not_found();
}

?>
