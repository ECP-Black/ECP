<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['shoutbox']['view'] OR @$_SESSION['rights']['superadmin']) {
		$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich="shoutbox"');
		$tpls =new smarty();
		if($anzahl) {
			$db->query('SELECT comID, country, username, userID, author, datum, beitrag FROM '.DB_PRE.'ecp_comments LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE bereich="shoutbox" ORDER BY datum DESC LIMIT '.LIMIT_SHOUTBOX_MINI);
			while($row1 = $db->fetch_assoc()) {
				$row1['nr'] = $anzahl--;
				$row1['countryname'] = @$countries[$row1['country']];
				$row1['datum'] = date(SHORT_DATE, $row1['datum']);
				$row1['beitrag'] = wordwrap($row1['beitrag'], 25, '<br />', 1);
				$shouts[] = $row1;
			}	
			$tpls->assign('shoutbox', $shouts);
		}
		$tpls->display(DESIGN.'/tpl/shoutbox/mini.html');
	} else {
		echo NO_ACCESS_RIGHTS;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>