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
    $row = db_query_first("select count(*) from u_users where ".
	"gmsgfilter & (1<<$g)");
    $hidden = $row[0];
    $tpl->set('hidden',$hidden);
    if($hidden>0) $tpl->parse('table.row.unhide');

    $tpl->parse('table.row');
    $tpl->reset();
}

function generate_table($tpl)
{
    global $debug, $template_dir;

    /* sanity check global_message table */
    $row = db_query_first("select max(gid) from f_global_messages");
    $max = $row[0];
    if ($max>31)
	db_exec("delete from f_global_messages where gid>31");

    $sth = db_query("select * from f_global_messages order by gid");

    for ($i=0; $i<32; $i++) {
	for ($msg = $sth->fetch();
	    (!$msg || $i!=$msg['gid']) && $i<32; $i++) {
	    $m['gid']=$i;
	    $m['state']='Empty';
	    output_row($tpl, $m);	// output blank row
	}
	if ($msg) output_row($tpl, $msg);	// output real row
    }
    $sth->closeCursor();

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

    $msg = db_query_first("select * from f_global_messages where gid=?", array($gid));

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
	$sargs = array();

	$name = $user->name;

	if (isset($arg['submit']) && $arg['submit'] == "Update Slot $gid") {
	    global $subject_tags;
	    $subject = stripcrap($arg['subject'], $subject_tags);
	    $url = stripcrapurl($arg['url']);
	    $sqls[]="update f_global_messages set ".
		    "subject = ?, url = ?, ".
		    "name = ?, date = NOW() ".
		    "where gid = ?";
	    $sargs[]=array($subject, $url, $name, $gid);
	    /* resend edit so we get the form back */
	    $args = "?gid=$gid&edit";
	}

	if (isset($arg['add'])) {
	    $sqls[]="insert into f_global_messages " .
		     "(gid, name, date) values " .
		     "(?, ?, NOW())";
	    $sargs[]=array($gid, $name);
	}

	if (isset($arg['take'])) {
	    $sqls[]="update f_global_messages set ".
		    "name = ?, date = NOW() ".
		    "where gid = ?";
	    $sargs[]=array($name, $gid);
	}

	if (isset($arg['touch'])) {
	    $sqls[]="update f_global_messages set ".
		    "date = NOW() ".
		    "where gid = ?";
	    $sargs[]=array($gid);
	}

	if (isset($arg['unhide'])) {
	    $sqls[]="update u_users set ".
		    "gmsgfilter = gmsgfilter & ~(1<<$gid) where gmsgfilter & (1<<$gid)";
	    $sargs[]=array();
	}

	if (count($sqls)) {
	    /* don't allow any sql updates unless we have a valid token */
	    if (!$user->is_valid_token($arg['token']))
		err_not_found("invalid token");

	    for ($i = 0; $i < count($sqls); $i++) {
		$sql = $sqls[$i];
		$sarg = $sargs[$i];
		debug($sql."\narray(".implode(",",$sarg).")\n");
		db_exec($sql, $sarg);
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
