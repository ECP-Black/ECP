<?php
if(@$_SESSION['rights']['public']['fightus']['submit'] OR @$_SESSION['rights']['superadmin']) {
	if(isset($_POST['submit'])) {
		if($_POST['clanname'] == '' OR !check_email($_POST['email']) OR !strtotime($_POST['datum']) OR $_POST['serverip'] == '' OR !(int)$_POST['teamID'] OR !(int)$_POST['gameID'] OR !(int)$_POST['matchtypeID']) {
			table(ERROR, NOT_NEED_ALL_INPUTS);
			$tpl = new smarty;
			$tpl->assign('games', get_games_form((int)$_POST['gameID'], 0));
			$tpl->assign('teams', get_teams_form((int)$_POST['teamID'], 0));
			$tpl->assign('liggen', get_matchtype_form((int)$_POST['matchtypeID'], 0));
			ob_start();
			$tpl->display(DESIGN.'/tpl/fightus/fightus.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FIGHTUS, $content, '',1);		
 		} elseif(strtolower(@$_SESSION['captcha']) != strtolower($_POST['captcha'])) {
 			table(ERROR, CAPTCHA_WRONG);
			$tpl = new smarty;
			$tpl->assign('games', get_games_form((int)$_POST['gameID'], 0));
			$tpl->assign('teams', get_teams_form((int)$_POST['teamID'], 0));
			$tpl->assign('liggen', get_matchtype_form((int)$_POST['matchtypeID'], 0));
			ob_start();
			$tpl->display(DESIGN.'/tpl/fightus/fightus.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(FIGHTUS, $content, '',1);
 		} else {
 			global $db;
 			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_fightus 	(`gID`, `mID`, `teamID`, `clanname`, `homepage`, `email`, `icq`, `skype`, `msn`, `wardatum`, `serverip`, `info`, `IP`, datum) VALUES 
 																(%d, %d, %d, \'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',%d,\'%s\',\'%s\',\'%s\', %d)',
 																(int)$_POST['gameID'],(int)$_POST['matchtypeID'],(int)$_POST['teamID'],strsave(htmlspecialchars($_POST['clanname'])),
 																strsave(check_url(htmlspecialchars($_POST['homepage']))),strsave($_POST['email']),strsave(htmlspecialchars($_POST['icq'])),strsave(htmlspecialchars($_POST['skype'])),
 																strsave(htmlspecialchars($_POST['msn'])),strtotime($_POST['datum']),strsave(htmlspecialchars($_POST['serverip'])), strsave(comment_save($_POST['info'])), $_SERVER['REMOTE_ADDR'], time());
 			if($db->query($sql)) {
				$id = $db->last_id();
				$result = $db->query('SELECT groupID FROM '.DB_PRE.'ecp_groups WHERE admin LIKE "%fightus:view%"');
				$search = 'gID = 1 ';
				while($row = $db->fetch_assoc()) {
					$search .= ' OR gID = '.$row['groupID'];
				}
				$result = $db->query('SELECT DISTINCT(userID) as userID, username, country FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_user ON ID = userID WHERE '.$search);
				$db->query('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "NEW_FIGHTUS"');
				$text = array();
				while($row = $db->fetch_assoc()) {
					$text[$row['lang']] = $row;								
				}
				while($row = mysql_fetch_assoc($result)) {
					$search = array('{username}', '{from_clan}', '{id}');
					$replace = array(strsave($row['username']), strsave(htmlspecialchars($_POST['clanname'])), $id);
					if(!isset($text[$row['country']]))	$row['country'] = DEFAULT_LANG;
					message_send($row['userID'], 0, $text[$row['country']]['content2'], str_replace($search, $replace, $text[$row['country']]['content']), 0, 1);							
				}
				unset($_SESSION['captcha']); 				
 				table(INFO, FIGHTUS_REQUEST_SEND);
 			}
 		}
	} else {
		$tpl = new smarty;
		$tpl->assign('games', get_games_form('', 0));
		$tpl->assign('teams', get_teams_form('', 0));
		$tpl->assign('liggen', get_matchtype_form('', 0));
		ob_start();
		$tpl->display(DESIGN.'/tpl/fightus/fightus.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(FIGHTUS, $content, '',1);
	}
} else
echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
?>