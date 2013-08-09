<?php 
include("config.inc");
include("util.inc");

if($login_to_read) {
    /* Workaround Debian bug 571762 */
    /* http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=571762 */
    //date_default_timezone_set(@date_default_timezone_get());

    include("forumuser.inc");

    sql_open($database);
    $user = new ForumUser;
    $user->find_by_cookie();
    sql_close($database);

    apache_note('aid',$user->aid);
}
?>

<HTML>
<!--
  vim: ts=2
  vim: sw=2
  -->
<HEAD>
<meta CONTENT="no-cache" http-equiv="Pragma">
<meta CONTENT="no-cache" http-equiv="Cache-Control">
<meta CONTENT="-1" http-equiv="Expires">

<!--
<?php 
while (list($k,$v) = each($_GET)) {echo "$k = $v\n";}
?>
-->

<TITLE><?php echo $forumname?> Search</TITLE>


<style>
body, table {
	font-family: verdana,arial;
	font-size:11px;
}

a {color:blue;}

.txtInp {
	font-size:11px;
	padding: 1px 2px;
	height: 18px;
}

#searchTxt {width:230px;
			}

.searchResults {
	border-left: 1px solid #E0E0E0;
}

.searchResults tr th {
	background-color: #E0E0E0;
	
}
.searchResults tr th a {
	color: black;
}
.searchResults tr th a:hover {
	color: #666666;
}

.searchResults tr td {
	padding: 1px 3px;
	border-right: 1px solid #E0E0E0;
	border-bottom: 1px solid #E0E0E0;
}

.searchResults tr td a:visited {
	color:purple;
}

.sum {
	font-size:10px;
	background-color:#f0f0f0;
	border: 1px solid #a0a0a0;
	padding: 2px 3px;
	margin-top: 3px;
	margin-left: 20px;
	
	/* width: expression(this.parentElement.offsetWidth-50); */
	overflow-x:auto;
}

.pageBar {
	padding-left: 10px;
}
</style>

<script>
function doSearch() {
	if (document.getElementById("searchTxt").value == "") {
		alert("Due to abuse, this feature has been disabled. You must always supply Search Text now.");
		return false;
	}
	if (document.getElementById("searchTxt").value == "" && document.getElementById("posterAID").value == "" && document.getElementById("posterName").value == "" ) {
		alert("At least one of the following is required:\n  Search Text\n  Poster's AID\n  Poster's Name");
		return false;
	}
	if (document.getElementById("searchTxt").value[0] == "+") {
		alert("Invalid search");
		return false;
	}
	str = document.getElementById("searchTxt").value;
	str = str.replace(/^\s+|\s+$/g,'').replace(/\s+/g,' ');
	if (str.length < 3) {
		alert("Invalid search. Use longer search string.");
		return false;
	}
	form1.submit();
}

function setDates(dtInterval) {
	dt = new Date();
	document.getElementById('endDate').value = fmtDt(dt);
	dt = new Date(dt.getFullYear(),dt.getMonth(),dt.getDate()-dtInterval);
	document.getElementById('startDate').value = fmtDt(dt);
}

function fmtDt(dt) {
	return (dt.getFullYear() + '-' + pad0(dt.getMonth()+1,2) + '-' + pad0(dt.getDate(),2));
}

function pad0(n,desiredLen) {
	n = String(n);
	while (n.length < desiredLen)
		n = '0' + n;
	return n;
}
</script>

</HEAD>
<BODY onkeydown="if (event.keyCode==13) {doSearch();return false;}">

<?php 


@mysql_connect($sql_host,$sql_username,$sql_password);
@mysql_select_db($database) or die( "Unable to select database");
mysql_query("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");

for($i=0;$i < count($_GET['forumID']);$i++) {
	$forumID[$i]=$_GET['forumID'][$i];
}

$searchTxt=isset($_GET['searchTxt'])?trim($_GET['searchTxt']):NULL;
$searchSubj=$_GET['searchSubj']?1:0;
$searchMsg=$_GET['searchMsg']?1:0;
$searchUrl=$_GET['searchUrl']?1:0;
$showMessages=$_GET['showMessages']?1:0;

$posterName=addslashes($_GET['posterName']);
$posterAID=is_numeric($_GET['posterAID'])?$_GET['posterAID']:'';

$flagNT=isset($_GET['flagNT'])?$_GET['flagNT']:'';
$flagPIC=isset($_GET['flagPIC'])?$_GET['flagPIC']:'';
$flagVID=isset($_GET['flagVID'])?$_GET['flagVID']:'';
$flagURL=isset($_GET['flagURL'])?$_GET['flagURL']:'';

$startDate=addslashes($_GET['startDate']);
$endDate=addslashes($_GET['endDate']);

$sortBy=addslashes($_GET['sortBy']);
$sortDir=addslashes($_GET['sortDir']);

$startRow=is_numeric($_GET['startRow'])?$_GET['startRow']:0;

?>

<form name="form1" id="form1" action="" method="get">
<table width=100%>
	<tr>
		<td valign="top" width="240" style="border-right:1px solid #888888;">
			<table>
				<tr>
					<td>
						<h4><?php echo $forumname?> Search</h4>
						<small>v 1.2</small>
					</td>
				</tr>
				<tr>
					
					<td>
						Search:<br>
						<input type="text" name="searchTxt" id="searchTxt" class="txtInp" value="<?php echo str_replace('"','&quot;',$searchTxt)?>">
					</td>
				</tr>
				
				<tr>
					<td>
						<fieldset>
						<legend>Search In</legend>
						<?php 
						if (!isset($searchTxt)) {
							$searchSubj=1;
							$searchMsg=1;
						}
						?>
						<table>
							<tr>
								<td>
									<input type="checkbox" name="searchSubj" value="1" <?php if ($searchSubj=="1") {?>checked<?php }?>> Subject
								</td>
								<td>
									<input type="checkbox" name="searchMsg" value="1" <?php if ($searchMsg=="1") {?>checked<?php }?>> Message
								</td>
								<td>
									<input type="checkbox" name="searchUrl" value="1" <?php if ($searchUrl=="1") {?>checked<?php }?>> URL
								</td>
							</tr>
						</table>
						</fieldset>
					</td>
				</tr>
				
				<tr>			
					<td>
						<fieldset>
						<legend>Forums</legend>
										
						<?php 
						$sql = "select * from f_forums where options like '%Searchable%' order by name";
						$rs = mysql_query($sql);

						while ($row = mysql_fetch_assoc($rs)) {
							$chkd = "";
							for($i=0;$i < count($forumID);$i++) {
								if ($forumID[$i] == $row['fid'])
									$chkd = " checked ";
							}

							if(isset($_GET['forum']) && $row['shortname'] == $_GET['forum']) {
							        /* 'forum' passed in via url */
								$chkd = " checked ";
							}
							
							?>
							<input type="checkbox" name="forumID[]" id="forumID<?php echo $row['fid']?>" value="<?php echo $row['fid']?>" <?php echo $chkd?>>
							<a href="http://<?php echo $hostname?>/<?php echo $row['shortname']?>/"><?php echo $row['name']?></a><br>
							<?php 
						}
						?>
						</fieldset>
					</td>
				</tr>
				
				<tr>			
					<td>
						<fieldset>
						<legend>Flags</legend>
							<img src="http://<?php echo $hostname?>/pics/nt.gif">
							<?php drawFlagSel('flagNT',$flagNT)?>
							<br>
							<img src="http://<?php echo $hostname?>/pics/pic.gif">
							<?php drawFlagSel('flagPIC',$flagPIC)?>
							<br>
							<img src="http://<?php echo $hostname?>/pics/video.gif">
							<?php drawFlagSel('flagVID',$flagVID)?>
							<br>
							<img src="http://<?php echo $hostname?>/pics/url.gif">
							<?php drawFlagSel('flagURL',$flagURL)?>
							
						</fieldset>
					</td>
				</tr>
				
				<tr>			
					<td>
						<fieldset>
						<legend>Date Range</legend>
							<table width=210 cellspacing=0 cellpadding=0>
								<tr>
									<td>Start:</td>
									<td>
										<input type="text" style="width:100px;" class="txtInp" name="startDate" id="startDate" value="<?php echo $startDate?>">
										
									</td>
									<td rowspan=3>
										<a href="#" onclick="setDates(7);return false;">past week</a><br>
										<a href="#" onclick="setDates(30);return false;">past 30 d</a><br>
										<a href="#" onclick="setDates(90);return false;">past 90 d</a><br>
										<a href="#" onclick="setDates(365);return false;">past year</a>
									</td>
								</tr>
								<tr>
									<td>End:</td>
									<td>
										<input type="text" style="width:100px;" class="txtInp" name="endDate" id="endDate" value="<?php echo $endDate?>">										
									</td>
								</tr>
								<tr>
									<td></td>
									<td style="font-size:10px; color:#333333;">yyyy-mm-dd</td>
								</tr>
							</table>
						</fieldset>
					</td>
				</tr>
				
				<tr>			
					<td>
						<fieldset>
						<legend>Other Options</legend>
							<table width=210>
								<tr>
									<td>Poster's AID:</td>
									<td>
										<input type="text" style="width:50px;" class="txtInp" name="posterAID" id="posterAID" value="<?php echo $posterAID?>">
									</td>
								</tr>
								<tr>
									<td>Poster's Name:</td>
									<td>
										<input type="text" style="width:100px;" class="txtInp" name="posterName" id="posterName" value="<?php echo $posterName?>">
									</td>
								</tr>
								<tr>
									<td>Show Messages:</td>
									<td>
										<input type="checkbox" name="showMessages" value="1" <?php if ($showMessages!="") {?>checked<?php }?>>
									</td>
								</tr>
							</table>
						</fieldset>
					</td>
				</tr>
				
				
				<tr>
					<td align="center">
						<input type="button" value="Reset" onclick="document.location='<?php echo $PHP_SELF?>';">
						&nbsp;&nbsp;
						<input type="button" value="Search &gt;" onclick="doSearch();">
					</td>
				</tr>
			</table>
		</td>
		<td valign="top">
			<?php 
			
			
//------------------SEARCH RESULTS---------------------			
			if ($searchTxt<>'' || $posterAID<>'' || $posterName<>'') {
				$rs=mysql_query(search_results(1)) or $rs=mysql_query(search_results(0));

				echo "<b>Results for '$searchTxt'</b><br><br>";


				if ($rs && mysql_num_rows($rs)>0) {
					echo mysql_num_rows($rs) . " matches found.<br>";
					
					?>
					<table class="searchResults" cellspacing=0 cellpadding=0 width=100%>
						<tr>
							<th>#</th>
							<?php if (count($forumID)>1) {?>
								<th>Forum</th>
							<?php }?>
							<th><?php drawSortLink('Subject','subject',$sortBy,$sortDir);?></th>
							<th><?php drawSortLink('Date','date',$sortBy,$sortDir);?></th>
							<th><?php drawSortLink('Posted by','name',$sortBy,$sortDir);?></th>
						</tr>
				
					<?php 
					$rowNbr=0;
					if ($startRow=="") $startRow = 0;
					$rowsPerPage = 25;
					
					while (($rowNbr < ($startRow)) && ($row = mysql_fetch_assoc($rs))) {
						$rowNbr++;
					}

					while (($row = mysql_fetch_assoc($rs)) && ($rowNbr < ($startRow + $rowsPerPage))) {
						$rowNbr++;
						?>
						<tr>
							<td><?php echo $rowNbr?></td>
							<?php if (count($forumID)>1) {?>
								<td>
									<?php echo $row['fName']?>
								</td>
							<?php }?>
							<td>
								<a href="http://<?php echo $hostname?>/<?php echo $row['shortname']?>/msgs/<?php echo $row['mid']?>.phtml" target="_blank"><?php echo $row['subject']?></a>
								&nbsp;
								<?php if (strpos($row['flags'],'NoText')) {?>
									<img src="http://<?php echo $hostname?>/pics/nt.gif">
								<?php }?>
								<?php if (strpos($row['flags'],'Picture')) {?>
									<img src="http://<?php echo $hostname?>/pics/pic.gif">
								<?php }?>
								<?php if (strpos($row['flags'],'Video')) {?>
									<img src="http://<?php echo $hostname?>/pics/video.gif">
								<?php }?>
								<?php if (strpos($row['flags'],'Link')) {?>
									<img src="http://<?php echo $hostname?>/pics/url.gif">
								<?php }
								if ($showMessages!="" && (!strpos($row['flags'],'NoText') || strpos($row['flags'],'Picture') || strpos($row['flags'],'Video') || strpos($row['flags'],'Link'))) {
									$msg = str_replace(chr(13),"<br>",$row['message']);
									//if ($row['url']) 
									//	$msg .= '<a href="' . $row['url'] . '" target="_blank">' . $row['urltext'] . '</a>';
									?>
									<div class="sum"><?php echo $msg?></div>
									<?php 
								}
								?>
							</td>
							<td><?php echo $row['date']?></td>
							<td>
								<a href="http://<?php echo $hostname?>/account/<?php echo $row['aid']?>.phtml"><?php echo substr($row['name'],0,25)?></a>
							</td>
						</tr>
						<?php 
					}
				
					?>
					</table>
					<br><br>
					
					<div class="pageBar">
						<?php 
						$url = $PHP_SELF . '?' . $_SERVER['QUERY_STRING'];					
						$url = removeUrlParam($url,"startRow");
											
					
						for ($i = 1; $i < mysql_num_rows($rs); $i+=$rowsPerPage) {
							if ($i >= 10000) {
								echo 'more than 10,000 rows returned.';
								break;
							}
							
							$maxRow = $i + $rowsPerPage - 1;
							if ($maxRow > mysql_num_rows($rs))
								$maxRow = mysql_num_rows($rs);
							if ($startRow == $i-1) {
								?>
								<b><?php echo $i?>-<?php echo $maxRow?></b>
								<?php 
							} else {
								?>
								<a href="<?php echo $url?>&startRow=<?php echo $i-1?>"><?php echo $i?>-<?php echo $maxRow?></a>
								<?php 
							}
							if ($maxRow < mysql_num_rows($rs)) echo " | ";
							
						}
						?>
					</div>
					<?php 
					
				} else {
					echo "No results.";
				}
			}
			?>
		</td>
	</tr>
</table>
</form>

<?php 

function search_results($useIndexedSearch)
{
	global $forumID;
	global $searchTxt, $searchSubj, $searchMsg, $searchUrl, $showMessages;
	global $posterName, $posterAID;
	global $flagNT, $flagPIC, $flagVID; $flagURL;
	global $startDate, $endDate;
	global $sortBy, $sortDir;


	//$sql1  = " select distinct m.mid,m.pid,m.tid,m.aid,m.state,m.flags,m.name,";
	$sql1  = " select m.mid,m.pid,m.tid,m.aid,m.state,m.flags,m.name,";
	$sql1 .= " m.date,m.subject,m.message,m.views, f.name fName, f.shortname ";
	
	//$sql2  = " where (m.subject like '%" . $searchTxt . "%' ";
	//$sql2  .= " or m.message like '%" . $searchTxt . "%' )";
	
	$sql2 = " where ";
	
	$searchArr = explode(" ",trim($searchTxt));
	
	if($useIndexedSearch) {
	    //check if any params are <=3 chars, if so, can't use indexed search
	    for ($i=0; $i<count($searchArr); $i++)
		    if (strlen($searchArr[$i]) <=3) {
			    $useIndexedSearch = false;
			    break;
		    }
	}
	
	for ($i=0; $i<count($searchArr); $i++) {
		if ($i>0)
			$sql2 .= " and ";
		$sql2 .= " (";

		if ($useIndexedSearch) {
			if ($searchSubj == "1" && $searchMsg == "1") {
				$sql2  .= " match (m.subject,m.message) AGAINST ('" . addslashes($searchArr[$i]) . "')";
			} else if ($searchSubj == "1") {
				$sql2  .= " match (m.subject) AGAINST ('" .  addslashes($searchArr[$i]) . "')";
			} else if ($searchMsg == "1") {
				$sql2  .= " match (m.message) AGAINST ('" .  addslashes($searchArr[$i]) . "')";
			}				
		} else {
			if ($searchSubj == "1" && $searchMsg == "1") {
				$sql2  .= " m.subject like '%" . addslashes($searchArr[$i]) . "%' ";
				$sql2  .= " or m.message like '%" . addslashes($searchArr[$i]) . "%' ";
			} else if ($searchSubj == "1") {
				$sql2  .= " m.subject like '%" . addslashes($searchArr[$i]) . "%' ";
			} else if ($searchMsg == "1") {
				$sql2  .= " m.message like '%" . addslashes($searchArr[$i]) . "%' ";
			}
		}

		if ($searchUrl == "1") {
			if ($searchSubj == "1" || $searchMsg == "1")
				$sql2  .= " or ";
			$sql2  .= " url like '%" . addslashes($searchTxt) . "%' or urltext like '%" . addslashes($searchTxt) . "%' ";
		}

		$sql2 .= ")";
	}
	
	$sql2 .= " and not (m.state = 'Deleted') ";
	
	if ($flagNT == "1")
		$sql2 .= " and m.flags like '%NoText%' ";
	if ($flagNT == "0")
		$sql2 .= " and not( m.flags like '%NoText%') ";
	
	if ($flagPIC == "1")
		$sql2 .= " and m.flags like '%Picture%' ";
	if ($flagPIC == "0")
		$sql2 .= " and not( m.flags like '%Picture%') ";
		
	if ($flagVID == "1")
		$sql2 .= " and m.flags like '%Video%' ";
	if ($flagVID == "0")
		$sql2 .= " and not( m.flags like '%Video%') ";
		
	if ($flagURL == "1")
		$sql2 .= " and m.flags like '%Link%' ";
	if ($flagURL == "0")
		$sql2 .= " and not( m.flags like '%Link%') ";
		
	if ($startDate != "")
		$sql2 .= " and m.date >= '" . $startDate . "' ";
	if ($endDate != "")
		$sql2 .= " and m.date <= '" . $endDate . " 23:59:59' ";
	
	if ($posterAID != "")
		$sql2 .= " and m.aid=" . $posterAID;
	
	if ($posterName != "")
		$sql2 .= " and m.name like '%" . $posterName . "%' ";
	
	$sql = "";
	for($i=0;$i < count($forumID);$i++) {
		if (!is_numeric($forumID[$i]))
			continue;

		if ($sql!="")
			$sql .= " UNION ";
		$sql .= "(" . $sql1 . " from f_forums f, f_messages" . $forumID[$i] . " m ";
		$sql .= $sql2 . " and f.fid=" . $forumID[$i];
		$sql .= ")";
	}
	
	if (preg_match("/^[a-zA-Z_]+$/", $sortBy))
		$sql .= " order by " . $sortBy . " " . (preg_match("/^(asc|desc)/i", $sortDir) ? $sortDir : "");
	else
		$sql .= " order by date desc";
	
	
	echo "\n<!-- $sql . -->\n";
	return $sql;
}

function drawFlagSel($id,$curval) {
	?>
	<select name="<?php echo $id?>" id="<?php echo $id?>">
		<option value=""  <?php if ($curval=="")  {?>selected<?php }?>>--</option>
		<option value="1" <?php if ($curval=="1") {?>selected<?php }?>>Yes</option>
		<option value="0" <?php if ($curval=="0") {?>selected<?php }?>>No</option>
	</select>
	<?php 
}

function drawSortLink($txt,$dbField,$sortBy,$sortDir) {
	$url = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];					
	$url = removeUrlParam($url,"sortBy");
	$url = removeUrlParam($url,"sortDir");
	?>
	<a href="<?php echo $url?>&sortBy=<?php echo $dbField?>&sortDir=<?php echo ($sortBy==$dbField && $sortDir=='asc') ? 'desc' : 'asc'?>"><?php echo $txt?></a>
	<?php 
	if ($sortBy==$dbField && ($sortDir=='asc' || $sortDir=='desc')) {
		?>
		<img src="images/Arrow_<?php echo $sortDir?>.gif">
		<?php 
	}
}


function removeUrlParam($strUrl,$param) {
	
	$p1 = strpos($strUrl,"&".$param."=");
	if ($p1) {
		$p2 = strpos($strUrl,"&",$p1+1);
		if (!$p2) $p2 = strlen($strUrl);
		$strUrl = substr($strUrl,0,$p1) . substr($strUrl,$p2);
	}
	return $strUrl;
}

?>

</BODY>
</HTML>
