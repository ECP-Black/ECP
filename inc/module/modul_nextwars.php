<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['clanwars']['view_next'] OR @$_SESSION['rights']['superadmin']) {
		$anzahl = $db->result(DB_PRE.'ecp_wars', 'COUNT(warID)', 'status=0');
		$tpls =new smarty();
		if($anzahl) {
			$db->query('SELECT `warID` , '.DB_PRE.'ecp_wars.`tID` , `gID` , `datum` , `xonx` , `oID` , `oppname`, `oppshort`, `homepage`, `country`, tname, gamename, icon, tname, matchtypename
									FROM `'.DB_PRE.'ecp_wars` 
									LEFT JOIN `'.DB_PRE.'ecp_wars_games` ON ( gameID = gID ) 
									LEFT JOIN `'.DB_PRE.'ecp_wars_matchtype` ON ( matchtypeID = mID ) 
									LEFT JOIN `'.DB_PRE.'ecp_teams` ON ( '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID ) 
									LEFT JOIN `'.DB_PRE.'ecp_wars_opp` ON ( oppID = oID ) 
									WHERE status = 0
									GROUP BY warID
									ORDER BY datum ASC LIMIT '.LIMIT_NEXT_WARS);
			while($row1 = $db->fetch_assoc()) {
				$row1['countryname'] = @$countries[$row1['country']];
				$row1['datum'] = date(SHORT_DATE, $row1['datum']);
				$next[] = $row1;
			}	
			$tpls->assign('next', $next);
		}
		$tpls->display(DESIGN.'/tpl/clanwars/next_mini.html');
	} else {
		echo NO_ACCESS_RIGHTS;
	}
} else {
	echo 'Kein direktes Aufrufen der Datei!';
}
?>