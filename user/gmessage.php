<?php

if (!$user->valid()) {
    err_not_found();
}

$gid = $_REQUEST['gid'];

if (!isset($gid) || is_int($gid) || $gid < -1 || $gid > 63) {
    err_not_found();
}

$gmsg = db_query_first("select * from f_global_messages where gid = ?", array($gid));

if (isset($_REQUEST['state']) && strlen($_REQUEST['state'])>0) {
    if (!$gmsg || !$user->admin()) {
	err_not_found();
    }

    if (!isset($_REQUEST['token']) || !$user->is_valid_token($_REQUEST['token'])) {
	err_not_found('Invalid token');
    }

    if ($_REQUEST['state'] == "Active") {
	db_exec("update f_global_messages set state = 'Active' where gid = ?", array($gid));
    } elseif ($_REQUEST['state'] == "Inactive") {
	db_exec("update f_global_messages set state = 'Inactive' where gid = ?", array($gid));
    } else {
	err_not_found();
    }
}

if (isset($_REQUEST['hide'])) {
    if (!isset($_REQUEST['token']) || !$user->is_valid_token($_REQUEST['token'])) {
      err_not_found('Invalid token');
    }

    if ($gid==-1) {
	if ($_REQUEST['hide']==1) {	/* hide all */
	      db_exec("update u_users set gmsgfilter = " . (~0) ." where aid = ?", array($user->aid));
	} else {			/* show all */
	      db_exec("update u_users set gmsgfilter = 0 where aid = ?", array($user->aid));
	}
    } elseif ($gmsg) {
	if ($_REQUEST['hide']==1) {	/* hide gid */
	    if (!($user->gmsgfilter & (1 << $gid)))
	      db_exec("update u_users set gmsgfilter = " . ($user->gmsgfilter | (1 << $gid)) . " where aid = ?", array($user->aid));
	} elseif ($_REQUEST['hide']==0) { /* show gid */
	    if ($user->gmsgfilter & (1 << $gid))
	      db_exec("update u_users set gmsgfilter = " . ($user->gmsgfilter & ~(1 << $gid)) . " where aid = ?", array($user->aid));
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
