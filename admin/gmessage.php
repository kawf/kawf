<?php
require_once('lib/YATT/YATT.class.php');

$user->req('ForumAdmin');

function build_args($args)
{
    $out = array();
    foreach($args as $k=>$v) {
	if (empty($v)) $out[]=$k;
	else $out[]="$k=$v";
    }
    return join('&amp;',$out);
}

function output_row($tpl, $msg)
{
    global $user;
    $tpl->set('token', $user->token());

    $g = $msg['gid'];

    $tpl->set('r', $g & 1);

    /* gid */
    $g_args['edit']='';
    if ($msg['state']=='Empty') {
	$g_args['add']='';
	$g_args['token']= $user->token();
    }
    $gid['args']=build_args($g_args);
    $tpl->set('gid', $gid);

    /* name */
    $name['args'] = build_args(array('take'=>''));
    $name['title'] = 'Take ownership';
    $tpl->set('name',$name);

    /* url */
    $date['args'] = build_args(array('touch'=>''));
    $date['title'] = 'Update time stamp';
    $tpl->set('date',$date);

    /* state */
    if ($msg['state'] == 'Inactive') {
	$state['title'] = 'Make active';
	$state['url'] = '/gmessage.phtml';
	$s_args['state'] = 'Active';
	/* return here when done */
	$s_args['page'] = '/admin/gmessage.phtml';
    } else if ($msg['state'] == 'Active') {
	$state['title'] = 'Make inactive';
	$state['url'] = '/gmessage.phtml';
	$s_args['state'] = 'Inactive';
	/* return here when done */
	$s_args['page'] = '/admin/gmessage.phtml';
    } else if ($msg['state'] == 'Empty') {
	$state['title'] = 'Enable';
	$state['url'] = '/admin/gmessage.phtml';
	$s_args['add'] = '';
    }
    $state['args'] = build_args($s_args);
    $tpl->set('state',$state);

    $tpl->set('msg',$msg);

    /* hidden by */
    $hidden = sql_query1("select count(*) from u_users where ".
	"gmsgfilter & (1<<$g)");
    $tpl->set('hidden',$hidden);
    if($hidden>0) $tpl->parse('table.row.unhide');

    $tpl->parse('table.row');
    $tpl->reset();
}

function generate_table($tpl)
{
    global $debug, $template_dir;

    /* sanity check global_message table */
    $max = sql_query1("select max(gid) from f_global_messages");
    if ($max>31)
	mysql_query("delete from f_global_messages where gid>31");

    $result = mysql_query("select * from f_global_messages order by gid")
	or sql_error();

    for ($i=0; $i<32; $i++) {
	for ($msg = mysql_fetch_assoc($result);
	    (!$msg || $i!=$msg['gid']) && $i<32; $i++) {
	    $m['gid']=$i;
	    $m['state']='Empty';
	    output_row($tpl, $m);	// output blank row
	}
	if ($msg) output_row($tpl, $msg);	// output real row
    }

    $tpl->parse('table');
}

function debug($msg) {
    global $debug;
    $debug .= $msg;
}

function dump($a) {
    $out = array();
    foreach ($a as $k=>$v)
	$out[] = "[$k='$v']";
    debug(join(", ", $out)."\n");
}

function generate_edit_form($tpl, $gid)
{
    global $user;

    $msg = sql_queryh("select * from f_global_messages where gid=$gid")
	or sql_error();

    $tpl->set('token', $user->token());
    $tpl->set('msg', $msg);
    $tpl->parse('form');
    $tpl->reset();
}

function process_request($tpl, $arg)
{
    global $user;

    if (!count($arg)) return;

    dump($arg);
    $args = '';

    if (isset($arg['gid']) && is_numeric($arg['gid'])) {
	$gid = $arg['gid'];
	if ($gid < 0 || $gid > 31)
	    err_not_found("GID out of range");
    }

    if (isset($gid)) {
	$sqls = array();

	$name = $user->name;

	if (isset($arg['submit']) && $arg['submit'] == "Update Slot $gid") {
	    global $subject_tags;
	    $subject = stripcrap($arg['subject'], $subject_tags);
	    $url = stripcrapurl($arg['url']);
	    $sqls[]="update f_global_messages set ".
		    "subject = '$subject', url = '$url', ".
		    "name = '$name', date = NOW() ".
		    "where gid = '$gid'";
	    /* resend edit so we get the form back */
	    $args = "?gid=$gid&edit";
	}

	if (isset($arg['add'])) {
	    $sqls[]="insert into f_global_messages " .
		     "(gid, name, date) values " .
		     "($gid, '$name', NOW())";
	}

	if (isset($arg['take'])) {
	    $sqls[]="update f_global_messages set ".
		    "name = '$name', date = NOW() ".
		    "where gid = '$gid'";
	}

	if (isset($arg['touch'])) {
	    $sqls[]="update f_global_messages set ".
		    "date = NOW() ".
		    "where gid = '$gid'";
	}

	if (isset($arg['unhide'])) {
	    $sqls[]="update u_users set ".
		    "gmsgfilter = gmsgfilter & ~(1<<$gid)";
	}

	if (count($sqls)) {
	    /* don't allow any sql updates unless we have a valid token */
	    if (!$user->is_valid_token($arg['token']))
		err_not_found("invalid token");

	    foreach ($sqls as $sql) {
		debug($sql."\n");
		mysql_query($sql) or sql_error($sql);
	    }
	}

	if (isset($arg['edit'])) {
	    /* on edit and add, we will send Location: but with "edit" again,
	       stripping add and the token */
	    if (isset($arg['add'])) $args = "?gid=$gid&edit";
	    else generate_edit_form($tpl, $gid);
	}

	if (count($sqls))
	   header("Location: /admin/gmessage.phtml$args");
    }
}

$tpl = new YATT($template_dir.'/admin', 'gmessage.yatt');

process_request($tpl,$_REQUEST);
generate_table($tpl);

/*
if (!empty($debug)) {
    $tpl->set('debug',$debug);
    $tpl->parse('debug');
}
*/

page_header("Global Messages");
echo $tpl->output();
page_footer();

?>
