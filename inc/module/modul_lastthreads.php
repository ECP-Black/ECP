<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['forum']['view'] OR @$_SESSION['rights']['superadmin']) {
		$sql 	= 'SELECT a.`threadID` , datum, `bID` , `threadname` , `vonID` , `vonname` , `views` , a.`posts` , `lastuserID` , 
					`lastusername` , `lastreplay` , `fsurveyID` , `anhaenge` , b.name as boardname, c.name as boardparentname, 
					d.username as fromusername, e.username as lastpostname, d.country as voncountry, e.country as lastcountry 
					FROM `'.DB_PRE.'ecp_forum_threads` AS a
					LEFT JOIN '.DB_PRE.'ecp_forum_boards AS b ON ( a.bID = b.boardID ) 
					LEFT JOIN '.DB_PRE.'ecp_forum_boards AS c ON ( b.boardparentID = c.boardID ) 
					LEFT JOIN '.DB_PRE.'ecp_user as d ON (a.vonID = d.ID)
					LEFT JOIN '.DB_PRE.'ecp_user as e ON (a.lastuserID = e.ID)
					WHERE (
					b.rightsread = "" OR '.str_replace('access', 'b.rightsread', $_SESSION['access_search']).'
					)
					AND (
					b.boardparentID = 0 
					OR c.rightsread = "" OR '.str_replace('access', 'c.rightsread', $_SESSION['access_search']).'
					)
					ORDER BY lastreplay DESC LIMIT '.LIMIT_LAST_THREADS;					

		$db->query($sql);
		if($db->num_rows()) {
			$tpls = new smarty;
			$threads = array();
			while($row1 = $db->fetch_assoc()) {
				$row1['lastreplay'] = date(LONG_DATE, $row1['lastreplay']);
				$row1['datum'] = date(LONG_DATE, $row1['datum']);
				$row1['voncountryname'] = @$countries[$row1['voncountry']];
				$row1['lastcountryname'] = @$countries[$row1['lastcountry']];
				$threads[] =$row1;
			}
			$tpls->assign('threads', $threads);
			$tpls->display(DESIGN.'/tpl/modul/lastthreads.html');			
		} else {
			echo NO_ENTRIES;
		}
	} else {
		echo NO_ACCESS_RIGHTS;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>