<?php
function shoutbox() {
	global $db, $countries;
	$tpl = new smarty;	
	$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich="shoutbox"');
	if($anzahl) {
		$limits = get_sql_limit($anzahl, LIMIT_SHOUTBOX);
		$shouts = array();
		$db->query('SELECT comID, country, username, userID, author, datum, beitrag FROM '.DB_PRE.'ecp_comments LEFT JOIN '.DB_PRE.'ecp_user ON userID = ID WHERE bereich="shoutbox" ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_SHOUTBOX);
		$anzahl -= $limits[1];
		while($row = $db->fetch_assoc()) {
			$row['nr'] = format_nr($anzahl--, 0);
			$row['countryname'] = @$countries[$row['country']];
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$shouts[] = $row;
		}
		$tpl->assign('shoutbox', $shouts);
		if($limits[0] > 1)
		$tpl->assign('seiten', makepagelink_ajax('?section=shoutbox', 'return load_shout_page({nr});', @$_GET['page'], $limits[0]));
		ob_start();
		$tpl->display(DESIGN.'/tpl/shoutbox/shoutbox.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(SHOUTBOX, '<div id="shout_overview">'.$content.'</div>', '',1);								
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function shoutbox_add() {
	global $db;
	if(isset($_GET['ajax'])) {
		$db->setMode(0);	
		ob_end_clean();
		ajax_convert_array($_POST);
	}
	$last = @$db->result(DB_PRE.'ecp_comments', 'datum', 'bereich="shoutbox" AND (IP =\''.strsave($_SERVER['REMOTE_ADDR']).'\' OR (userID != 0 AND userID = '.@(int)$_SESSION['userID'].'))');
	if(!@$_SESSION['userID'] AND $_POST['shout_username'] == '' AND $_POST['shout_captcha'] == '' AND $_POST['shoutbox_msgbox'] == '') {
		if(isset($_GET['ajax'])) {
			echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
		} else {
			table(ERROR, NOT_NEED_ALL_INPUTS);
		}
	} elseif (!@$_SESSION['userID'] AND strtolower($_POST['shout_captcha']) != strtolower($_SESSION['captcha_mini'])) {
		if(isset($_GET['ajax'])) {
			echo html_ajax_convert(CAPTCHA_WRONG);
		} else {
			table(ERROR, CAPTCHA_WRONG);
		}
	} elseif (@$_SESSION['userID'] AND $_POST['shoutbox_msgbox'] == '') {
		if(isset($_GET['ajax'])) {
			echo html_ajax_convert(NOT_NEED_ALL_INPUTS);
		} else {
			table(ERROR, NOT_NEED_ALL_INPUTS);
		}
	} elseif ($last > (time() - SPAM_SHOUTBOX) OR @(int)$_COOKIE['shoutbox'] > (time() - SPAM_SHOUTBOX)) {
		($last > (time() - SPAM_SHOUTBOX)) ? $zeit = (SPAM_SHOUTBOX+$last)-time() : $zeit = (SPAM_SHOUTBOX+$_COOKIE['shoutbox'])-time(); 
		if(isset($_GET['ajax'])) {
			echo html_ajax_convert(str_replace(array('{sek}', '{zeit}'), array(SPAM_SHOUTBOX, $zeit), SPAM_PROTECTION_MSG));
		} else {
			table(ERROR, str_replace(array('{sek}', '{zeit}'), array(SPAM_SHOUTBOX, $zeit), SPAM_PROTECTION_MSG));
		}		
	} else {
		$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_comments (`bereich`, `userID`, `author`, `beitrag`, `datum`, `IP`) VALUES (\'shoutbox\', %d, \'%s\', \'%s\', %d, \'%s\')', @$_SESSION['userID'], strsave(htmlspecialchars(@$_POST['shout_username'])), strsave(htmlspecialchars(substr($_POST['shoutbox_msgbox'],0, SHOUTBOX_MAX_CHARS))), time(), strsave($_SERVER['REMOTE_ADDR']));
		if($db->query($sql)) {
			setcookie('shoutbox', time(), (time()+365*86400));
			if(isset($_GET['ajax'])) {
				echo 'ok';
			} else {
				if($_SERVER['HTTP_REFERER'] != '') {
					header('Location: '.$_SERVER['HTTP_REFERER'].'#com_'.$db->last_id());
				} else {
					header1('?section=news#com_'.$db->last_id());
				}
			}			
		}
	}
	if(isset($_GET['ajax'])) {
		die();
	}	
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'add':
			if(@$_SESSION['rights']['public']['shoutbox']['add'] OR @$_SESSION['rights']['superadmin']) {
				shoutbox_add();
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;	
		default:
			if(@$_SESSION['rights']['public']['shoutbox']['view'] OR @$_SESSION['rights']['superadmin']) {
				shoutbox();
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['shoutbox']['view'] OR @$_SESSION['rights']['superadmin']) {
		shoutbox();
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>