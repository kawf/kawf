<?php

require_once("sql.inc");

function get_notices_html($forum, $aid)
{
	if (sql_query1("show tables like 'n_notices".$forum['fid']."';")) {
		$notices_q = "select nid, blurb from n_notices" . $forum['fid'] . " where deleted != 1 order by date;";

		$notices_r = sql_query($notices_q);
		if ($notices_r) {
			$notices .= '<style type="text/css">';
			$notices .= 'a.ignore_notice { opacity:0.30; }'."\n";
			$notices .= 'a.ignore_notice:hover { opacity:1; }'."\n";
			$notices .= 'div.notice_block { height:80px; float:right; margin-left:10px; position:relative; overflow:hidden; }'."\n";
			$notices .= 'div.notice_closebox { padding:2px; position:absolute; top:0px; right:0px; }'."\n";
			$notices .= '</style>';
			
			$notices .= '<div>';
			while ($notices_a = mysql_fetch_array($notices_r)) {
				
				$ignoring = FALSE;
				if ($aid) {
					$ignoring_q = "select count(*) from n_ignoring where aid = $aid ".
						"and fid = ".$forum['fid']." and nid= ".$notices_a['nid'].";";
					$ignoring = sql_query1($ignoring_q);
				}
				if ($ignoring) continue;
				
				$notices .= '<div class="notice_block">';
				if ($aid) {
					$notices .= '<div class="notice_closebox">';
					$notices .= '<a class="ignore_notice" href="http://boty.wayot.org/notices/ignore.php?forum=';
					$notices .= $forum['shortname'];
					$notices .= '&nid='.$notices_a['nid'].'"><img border="0" src="/pics/close_notice.png" /></a>';
					$notices .= '</div>';
				}
				$notices .= $notices_a['blurb'];
				$notices .= '</div>';
			}
			$notices .= '</div>';
			
			sql_free_result($notices_r);
		} else {
			$notices = "Error fetching FORUM_NOTICES!";
		}

		// $tpl->set_var("FORUM_NOTICES", $notices);
	}
	
	return $notices;
}


?>