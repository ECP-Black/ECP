<?php
	if(@$_SESSION['rights']['public']['teams']['view'] OR @$_SESSION['rights']['superadmin']) {
		global $db, $countries;
		$result = $db->query('SELECT `tID`, `tname`, `tpic`, `info`, `cw`, `fightus`, `joinus`, `posi` FROM '.DB_PRE.'ecp_teams ORDER BY posi ASC');
		$teams =array();
		if($db->num_rows()) {
			while($row = mysql_fetch_assoc($result)) {
				$members = array();
				$db->query('SELECT geburtstag, xfire, icq, registerdate, lastlogin, wohnort, user_pic, `userID`, `name`, `aufgabe`, `aktiv`, username, country, uID as online FROM '.DB_PRE.'ecp_members LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID) LEFT JOIN '.DB_PRE.'ecp_online ON (uID = userID AND lastklick > '.(time()- SHOW_USER_ONLINE).') WHERE teamID = '.$row['tID'].' GROUP BY userID ORDER BY posi ASC');
				while($sub = $db->fetch_assoc()) {
					if($sub['name'] == '') $sub['name'] = $sub['username'];
					$sub['countryname'] = $countries[$sub['country']];
					$sub['registerdate'] = date(LONG_DATE, $sub['registerdate']);
					if($sub['lastlogin'] == 0) {
						$sub['lastlogin'] = NEVER_LOGGED_IN;
 					} else
					$sub['lastlogin'] = date(LONG_DATE, $sub['lastlogin']);
					if($sub['geburtstag'] == '0000-00-00') $sub['geburtstag'] = '';
					if($sub['geburtstag']) {
		            	$birthday = explode('-', $sub['geburtstag']);
		            	$sub['geburtstag'] = $birthday[2].'.'.$birthday[1].'.'.$birthday[0];
		            	$alter = alter($birthday[2], $birthday[1], $birthday[0]);
		            	IF(date('m') == $birthday[1] AND date('d') < $birthday[2]) $alter -=1;
		            	$next = @mktime(0,0,0,$birthday[1],$birthday[2],$birthday[0] + $alter + 1) - time();
		            	$sub['alter'] =  $alter;
		            			
					}
					$sub['icqtrim'] = str_replace('-', '',$sub['icq']);					
					$members[] = $sub;
				}
				$row['members'] = $members;
				$info = json_decode($row['info'], true);
				if(isset($info[LANGUAGE]) AND $info[LANGUAGE]) 
					$row['info'] = $info[LANGUAGE]; 
				else 
					$row['info'] = $info[DEFAULT_LANG];
				$teams[] = $row;
			}	
			$tpl = new Smarty();
			$tpl->assign('teams', $teams);
			ob_start();
			$tpl->display(DESIGN.'/tpl/teams/teams.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(TEAMS, $content, '',1);		
		} else {
			table(INFO, NO_ENTRIES);
		}
	} else {
		table(ERROR, ACCESS_DENIED);
	}
?>
