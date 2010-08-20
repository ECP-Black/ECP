<?php
function guestbook() {
	global $db, $countries;
	$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich = \'guestbook\'');
	if($anzahl) {
		$seiten = get_sql_limit($anzahl, LIMIT_GUESTBOOK);
		$db->query('SELECT
	                    a.author, a.homepage, a.email, a.comID, a.beitrag, a.datum, COUNT(b.comID) as comments
	                 FROM
	                     '.DB_PRE.'ecp_comments as a
	                 LEFT JOIN '.DB_PRE.'ecp_comments as b ON (b.subID = a.comID AND b.bereich = "gb_com")
	                 WHERE
	                    a.bereich = "guestbook"
	                 GROUP BY a.comID
	                 ORDER BY
	                     a.datum DESC
	                 LIMIT '.$seiten[1].','.LIMIT_GUESTBOOK);
		$comments = array();
		while($row = $db->fetch_assoc()) {
			$row['nr'] = $anzahl--;		
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$comments[] = $row;
		}
		$tpl = new smarty;
		if($seiten[0] > 1)
			$tpl->assign('seiten', makepagelink_ajax('?section=guestbook', '', @$_GET['page'], $seiten[0]));
		$tpl->assign('comments', $comments);
		ob_start();
		$tpl->display(DESIGN.'/tpl/guestbook/guestbook.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GUESTBOOK, $content, '',1);
	} else {
		table(GUESTBOOK, NO_ENTRIES.'<br /> <a href="?section=guestbook&action=add">'.GUESTBOOK_ADD.'</a>');
	}
}
function gb_once($id) {
	global $db, $countries;
	$row = $db->fetch_assoc('SELECT
	                    a.author, a.homepage, a.email, a.comID, a.beitrag, a.datum, COUNT(b.comID) as comments
	                 FROM
	                     '.DB_PRE.'ecp_comments as a
	                 LEFT JOIN '.DB_PRE.'ecp_comments as b ON (b.subID = a.comID AND b.bereich = "gb_com")
	                 WHERE
	                    a.bereich = "guestbook" AND a.comID = '.$id.'
	                 GROUP BY a.comID');
	if($db->num_rows()) {
		$comments = array();
		$row['nr'] = 1;		
		$row['datum'] = date(LONG_DATE, $row['datum']);
		$comments[] = $row;
		$tpl = new smarty;
		$tpl->assign('ajax', 1);
		$tpl->assign('comments', $comments);
		ob_start();
		$tpl->display(DESIGN.'/tpl/guestbook/guestbook.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GUESTBOOK, $content, '',1);
	} else {
		table(GUESTBOOK, NO_ENTRIES_ID);
	}
}
function guestbook_add() {
	global $db;
	if(isset($_POST['submit'])) {
		$last = @$db->result(DB_PRE.'ecp_comments', 'datum', 'bereich="guestbook" AND IP =\''.strsave($_SERVER['REMOTE_ADDR']).'\'');
		if($_POST['author'] == '' OR $_POST['commentstext'] == '' OR $_POST['captcha'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/guestbook/guestbook_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GUESTBOOK_ADD, $content, '',1);	
		} elseif (!check_email($_POST['email']) AND $_POST['email'] != '') {
			table(ERROR, WRONG_EMAIL);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/guestbook/guestbook_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GUESTBOOK_ADD, $content, '',1);	
		} elseif (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha'])) {
			table(ERROR, CAPTCHA_WRONG);
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/guestbook/guestbook_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GUESTBOOK_ADD, $content, '',1);
		} elseif ($last > (time() - SPAM_GUESTBOOK) OR @(int)$_COOKIE['guestbook'] > (time() - SPAM_GUESTBOOK)) {
			($last > (time() - SPAM_GUESTBOOK)) ? $zeit = (SPAM_GUESTBOOK+$last)-time() : $zeit = (SPAM_GUESTBOOK+$_COOKIE['guestbook'])-time(); 
			table(ERROR, str_replace(array('{sek}', '{zeit}'), array(SPAM_GUESTBOOK, $zeit), SPAM_PROTECTION_MSG));
			$tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/guestbook/guestbook_add.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(GUESTBOOK_ADD, $content, '',1);
		} else {
			$sql = sprintf('INSERT INTO '.DB_PRE.'ecp_comments (`bereich`, `author`, `beitrag`, `email`, `homepage`, `datum`, `IP`) VALUES ("guestbook", \'%s\', \'%s\', \'%s\', \'%s\', %d, \'%s\')', strsave(htmlspecialchars($_POST['author'])), strsave(comment_save($_POST['commentstext'])), strsave(htmlspecialchars($_POST['email'])), strsave(htmlspecialchars(check_url($_POST['homepage']))), time(), strsave($_SERVER['REMOTE_ADDR']));
			if($db->query($sql)) {
				setcookie('guestbook', time(), (time()+365*86400));
				header1('?section=guestbook');
			}
		}
		unset($_SESSION['captcha']);
	} else {
		$tpl = new smarty;
		ob_start();
		$tpl->display(DESIGN.'/tpl/guestbook/guestbook_add.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GUESTBOOK_ADD, $content, '',1);
	}
}
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_GUESTBOOK_COMMENTS,
					'section'   => 'guestbook');
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'add':
			if(@$_SESSION['rights']['public']['guestbook']['add'] OR @$_SESSION['rights']['superadmin']) {
				guestbook_add();
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;
		case 'comments':
			if(@$_SESSION['rights']['public']['guestbook']['com_view'] OR @$_SESSION['rights']['superadmin']) {			
				gb_once((int)$_GET['id']);
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=guestbook&action=comments&id='.(int)$_GET['id'];
				comments_get('gb_com', (int)$_GET['id'], $conditions, 0, 1, 'guestbook');			
			} else {
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
			}
		break;
		case 'addcomment':
			if(@$_SESSION['rights']['public']['guestbook']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=guestbook&action=comments&id='.(int)$_GET['id'];
				comments_add('gb_com', (int)$_GET['id'], $conditions, 'guestbook');
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=guestbook&action=comments&id='.(int)$_GET['subid'];
			comments_edit('gb_com', (int)$_GET['subid'], (int)$_GET['id'], $conditions, 'guestbook');		
		break;	
		case 'editgbcomment':
			$conditions['action'] = 'editgb';
			$conditions['link'] = '?section=guestbook&action=comments&id='.(int)$_GET['id'];
			comments_edit('guestbook', 0, (int)$_GET['id'], $conditions, 'guestbook', 'edit');		
		break;							
		default:
		if(@$_SESSION['rights']['public']['guestbook']['view'] OR @$_SESSION['rights']['superadmin']) {
			guestbook();
		} else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['guestbook']['view'] OR @$_SESSION['rights']['superadmin']) {
		guestbook();
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>