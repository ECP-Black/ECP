<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin']) {
		$db->query('SELECT dID,	name, a.userID, info, 
					a.homepage, version, size, traffic, downloads, 
					a.datum, COUNT(comID) as comments, username, kname, country
					FROM '.DB_PRE.'ecp_downloads as a
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate as b ON (kID = cID)
					LEFT JOIN '.DB_PRE.'ecp_user ON (a.userID = ID) 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "downloads" AND subID = dID) 
					WHERE (a.access = "" OR '.str_replace('access', 'a.access', $_SESSION['access_search']).') AND (b.access = "" OR '.str_replace('access', 'b.access', $_SESSION['access_search']).')
					GROUP BY dID
					ORDER BY datum DESC LIMIT '.LIMIT_LAST_THREADS);					
		if($db->num_rows()) {
			$tpls = new smarty;
			$dls = array();
			while($row1 = $db->fetch_assoc()) {
				$row1['size'] = goodsize($row1['size']);
				$row1['traffic'] = goodsize($row1['traffic']);
				$row1['datum'] = date(LONG_DATE, $row1['datum']);
				$dls[] =$row1;
			}
			$tpls->assign('dls', $dls);
			$tpls->display(DESIGN.'/tpl/modul/lastdownloads.html');			
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