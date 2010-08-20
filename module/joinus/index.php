<?php
	if(@$_SESSION['rights']['public']['joinus']['view'] OR @$_SESSION['rights']['superadmin']) {
		if(isset($_POST['name'])) {
			global $db;
			if($_POST['name'] == '' OR $_POST['username'] == '' OR $_POST['age'] == '' OR $_POST['comment'] == '' OR !(int)$_POST['age'] OR $_POST['email'] == '' OR $_POST['country'] == '' OR !$_POST['teamID'] OR !check_email($_POST['email']) OR $_POST['captcha']== '') {
				table(ERROR, NOT_NEED_ALL_INPUTS);
				$tpl = new smarty;	
				ob_start();
				$tpl->assign('countries', form_country($_POST['country']));
				$tpl->assign('teams', get_teams_form_joinus($_POST['teamID']));			
				$tpl->display(DESIGN.'/tpl/joinus/joinus.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(JOINUS, $content, '',1);					
			} elseif (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha'])) {
				table(ERROR, CAPTCHA_WRONG);
				$tpl = new smarty;	
				ob_start();
				$tpl->assign('countries', form_country($_POST['country']));
				$tpl->assign('teams', get_teams_form_joinus($_POST['teamID']));			
				$tpl->display(DESIGN.'/tpl/joinus/joinus.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(JOINUS, $content, '',1);					
			} elseif (!$db->result(DB_PRE.'ecp_teams', 'COUNT(tID)', 'joinus = 1 AND tID = '.(int)$_POST['teamID'])) {
				table(ERROR, JOINUS_NO_TEAM);
				$tpl = new smarty;	
				ob_start();
				$tpl->assign('countries', form_country($_POST['country']));
				$tpl->assign('teams', get_teams_form_joinus($_POST['teamID']));			
				$tpl->display(DESIGN.'/tpl/joinus/joinus.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(JOINUS, $content, '',1);	
			} else {
				$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_joinus (`name`, `username`, `email`, `icq`, `msn`, `age`, `country`, `teamID`, `comment`, `IP`, `datum`) VALUES (\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',%d,\'%s\',%d,\'%s\',\'%s\',%d)', 
														strsave(htmlspecialchars($_POST['name'])), strsave(htmlspecialchars($_POST['username'])),strsave($_POST['email']),strsave(htmlspecialchars($_POST['icq'])),
														strsave(htmlspecialchars($_POST['msn'])),(int)$_POST['age'],strsave(htmlspecialchars($_POST['country'])),(int)$_POST['teamID'],
														strsave(comment_save($_POST['comment'])),strsave($_SERVER['REMOTE_ADDR']),time());
				if($db->query($sql)) {
					$id = $db->last_id();
					$result = $db->query('SELECT groupID FROM '.DB_PRE.'ecp_groups WHERE admin LIKE "%joinus:view%"');
					$search = 'gID = 1 ';
					while($row = $db->fetch_assoc()) {
						$search .= 'OR gID = '.$row['groupID'];
					}
					$result = $db->query('SELECT DISTINCT(userID) as userID, username, country FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_user ON ID = userID WHERE '.$search);
					$db->query('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "NEW_JOINUS"');
					$text = array();
					while($row = $db->fetch_assoc()) {
						$text[$row['lang']] = $row;								
					}
					while($row = mysql_fetch_assoc($result)) {
						$search = array('{username}', '{from_username}', '{id}');
						$replace = array(strsave($row['username']), strsave(htmlspecialchars($_POST['username'])), $id);
						if(!isset($text[$row['country']]))	$row['country'] = 'de';
						message_send($row['userID'], 0, $text[$row['country']]['content2'], str_replace($search, $replace, $text[$row['country']]['content']), 0, 1);							
					}
					unset($_SESSION['captcha']);
					table(INFO, JOINUS_SUCCESS);
				}
			}
		} else {
			$tpl = new smarty;	
			ob_start();
			$tpl->assign('countries', form_country());
			$tpl->assign('teams', get_teams_form_joinus());			
			$tpl->display(DESIGN.'/tpl/joinus/joinus.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(JOINUS, $content, '',1);				
		}
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
?>