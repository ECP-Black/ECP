<?php
function admin_news() {
	global $db;
	$tpl = new smarty;
	if(@$_GET['func'] == 'pin') {
		ob_end_clean();
		if($db->result(DB_PRE.'ecp_news', 'sticky', 'newsID = '.(int)$_GET['id']) == 0) {
			$db->query('UPDATE '.DB_PRE.'ecp_news SET sticky = 1 WHERE newsID = '.(int)$_GET['id']);
		} else {
			$db->query('UPDATE '.DB_PRE.'ecp_news SET sticky = 0 WHERE newsID = '.(int)$_GET['id']);
		}
		$tpl->assign('ajax', 1);
	}
	$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', 'datum > 0',0);
	if($anzahl) {
		$limits = get_sql_limit($anzahl, ADMIN_ENTRIES);
		$news = array();
		$db->query('SELECT `newsID`, `topicID`, `datum`, `headline`, `topicname`, sticky FROM `'.DB_PRE.'ecp_news` LEFT JOIN `'.DB_PRE.'ecp_topics` ON (`topicID` = `tID`) ORDER BY sticky DESC, `datum` DESC LIMIT '.$limits[1].', '.ADMIN_ENTRIES);
		while($row = $db->fetch_assoc()) {
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$news[] = $row;
		}
	}

	$tpl->assign('seiten', makepagelink('?section=admin&site=news', (isset($_GET['page']) ? $_GET['page'] : 1), $limits[0]));
	$tpl->assign('news', @$news);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/news.html');
	$content = ob_get_contents();
	ob_end_clean();
	if(@$_GET['func'] == 'pin') {
		echo html_ajax_convert($content);
		die();
	} else {
		main_content(ADMIN_NEWS, $content, '',1);
	}
}
function admin_news_add() {
	global $db,$groups, $language_array;
	if(isset($_POST['submit'])) {
		if($_POST['headline'] == '' OR (int)$_POST['topic']  == 0 OR $_POST['bodytext'] == '') {
			$tpl = new smarty;
			$links = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'ink_')) {
					$nr = substr($key,strpos($key, '_')+1);
					$links[$nr]['link'] = $value;
				} elseif (strpos($key, 'rl_')) {
					$nr = substr($key,strpos($key, '_')+1);
					$links[$nr]['url'] = check_url($value);
				}else {
					$tpl->assign($key, $value);
				}
			}
			$tpl->assign('links', $links);
			$db->query('SELECT tID, topicname FROM '.DB_PRE.'ecp_topics ORDER by topicname ASC');
			$topics = '';
			while($row = $db->fetch_assoc()) {
			($_POST['topic'] == $row['tID'])? $sub = ' selected="selected"' : $sub = '';
			$topics .= '<option'.$sub.' value="'.$row['tID'].'">'.$row['topicname'].'</option>';
			}
			$tpl->assign('topics', $topics);
			$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
			(in_array('all', $_POST['rights']))? $rights = '<option value="all" selected="selected">'.ALL.'</option>' : $rights = '<option value="all">'.ALL.'</option>';
			while($row = $db->fetch_assoc()) {
			(in_array($row['groupID'], $_POST['rights']))? $sub = ' selected="selected"' : $sub = '';
			if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
			$rights .= '<option'.$sub.' value="'.$row['groupID'].'">'.$row['name'].'</option>';
			}
			$files = scan_dir('inc/language', true);
			(in_array('all', $_POST['rights']))?$languages = '<option value="all" selected="selected">'.ALL.'</option>' : $languages  = '<option value="all">'.ALL.'</option>';
			foreach($files AS $lang) {
				if(strpos($lang, '.php')) {
					$lang = substr($lang,0,strpos($lang, '.'));
					(in_array($lang, $_POST['languages']))? $sub = ' selected="selected"' : $sub = '';
					@$languages .= '<option'.$sub.' value="'.$lang.'">'.@$language_array[$lang].'</option>';
				}
			}
			$tpl->assign('rights', $rights);
			$tpl->assign('topics', $topics);
			$tpl->assign('languages', $languages);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/news_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(ADMIN_NEWS_ADD, $content, '',1);
		} else {
			if(in_array('all', $_POST['rights']))
			$rights = '';
			else {
				$rights = ',';
				foreach($_POST['rights'] AS $key) {
					$rights .= (int)$key.',';
				}
			}
			if(in_array('all', $_POST['languages'])) $lang = ''; else $lang = ','.implode(',',$_POST['languages']).',';
			$zeit = strtotime($_POST['datum']);
			if($zeit == 0) $zeit = time();
			$i = 0;
			while(isset($_POST['link_'.$i])) {
				if($_POST['link_'.$i] != '' AND $_POST['url_'.$i] != '') {
					@$links .= '[URL='.check_url($_POST['url_'.$i].']'.$_POST['link_'.$i].'[/URL]');
				}
				$i++;
			}
			$sql = 'INSERT INTO '.DB_PRE.'ecp_news (`userID`, `topicID`, `access`, `lang`, `datum`, `headline`, `bodytext`, `extendtext`, `links`) VALUES (
					'.$_SESSION['userID'].', '.(int)$_POST['topic'].', \''.$rights.'\', \''.strsave($lang).'\', '.$zeit.', 
					\''.strsave($_POST['headline']).'\', \''.strsave($_POST['bodytext']).'\', \''.strsave($_POST['extendtext']).'\', 
					\''.strsave(@$links).'\');';
			if($db->query($sql)) {
				header1('?section=admin&site=news');
			}
		}
	} else {
		$tpl = new smarty;
		$links = array(0);
		$tpl->assign('links', $links);
		$db->query('SELECT tID, topicname FROM '.DB_PRE.'ecp_topics ORDER by topicname ASC');
		$topics = '';
		while($row = $db->fetch_assoc()) {
			$topics .= '<option value="'.$row['tID'].'">'.$row['topicname'].'</option>';
		}
		$tpl->assign('topics', $topics);
		$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
		$rights = '<option value="all" selected="selected">'.ALL.'</option>';
		while($row = $db->fetch_assoc()) {
			if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
			$rights .= '<option value="'.$row['groupID'].'">'.$row['name'].'</option>';
		}
		$files = scan_dir('inc/language', true);
		$languages = '<option value="all" selected="selected">'.ALL.'</option>';
		foreach($files AS $lang) {
			if(strpos($lang, '.php')) {
				$lang = substr($lang,0,strpos($lang, '.'));
				@$languages .= '<option value="'.$lang.'">'.@$language_array[$lang].'</option>';
			}
		}
		$tpl->assign('rights', $rights);
		$tpl->assign('topics', $topics);
		$tpl->assign('languages', $languages);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/news_add_edit.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(ADMIN_NEWS_ADD, $content, '',1);
	}
}
function admin_news_edit($id) {
	global $db,$groups, $language_array;
	if(isset($_POST['submit'])) {
		if($_POST['headline'] == '' OR (int)$_POST['topic']  == 0 OR $_POST['bodytext'] == '') {
			$tpl = new smarty;
			$links = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'ink_')) {
					$nr = substr($key,strpos($key, '_')+1);
					$links[$nr]['link'] = $value;
				} elseif (strpos($key, 'rl_')) {
					$nr = substr($key,strpos($key, '_')+1);
					$links[$nr]['url'] = check_url($value);
				}else {
					$tpl->assign($key, $value);
				}
			}
			$tpl->assign('links', $links);
			$db->query('SELECT tID, topicname FROM '.DB_PRE.'ecp_topics ORDER by topicname ASC');
			$topics = '';
			while($row = $db->fetch_assoc()) {
			($_POST['topic'] == $row['tID'])? $sub = ' selected="selected"' : $sub = '';
			$topics .= '<option'.$sub.' value="'.$row['tID'].'">'.$row['topicname'].'</option>';
			}
			$tpl->assign('topics', $topics);
			$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
			(in_array('all', $_POST['rights']))? $rights = '<option value="all" selected="selected">'.ALL.'</option>' : $rights = '<option value="all">'.ALL.'</option>';
			while($row = $db->fetch_assoc()) {
			(in_array($row['groupID'], $_POST['rights']))? $sub = ' selected="selected"' : $sub = '';
			if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
			$rights .= '<option'.$sub.' value="'.$row['groupID'].'">'.$row['name'].'</option>';
			}
			$files = scan_dir('inc/language', true);
			(in_array('all', $_POST['rights']))?$languages = '<option value="all" selected="selected">'.ALL.'</option>' : $languages  = '<option value="all">'.ALL.'</option>';
			foreach($files AS $lang) {
				if(strpos($lang, '.php')) {
					$lang = substr($lang,0,strpos($lang, '.'));
					(in_array($lang, $_POST['languages']))? $sub = ' selected="selected"' : $sub = '';
					@$languages .= '<option'.$sub.' value="'.$lang.'">'.@$language_array[$lang].'</option>';
				}
			}
			$tpl->assign('rights', $rights);
			$tpl->assign('topics', $topics);
			$tpl->assign('languages', $languages);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/news_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(ADMIN_NEWS_ADD, $content, '',1);
		} else {
			if(in_array('all', $_POST['rights']))
			$rights = '';
			else {
				$rights = ',';
				foreach($_POST['rights'] AS $key) {
					$rights .= (int)$key.',';
				}
			}
			if(in_array('all', $_POST['languages'])) $lang = ''; else $lang = ','.implode(',',$_POST['languages']).',';
			$zeit = strtotime($_POST['datum']);
			if($zeit == 0) $zeit = time();
			$i = 0;
			while(isset($_POST['link_'.$i])) {
				if($_POST['link_'.$i] != '' AND $_POST['url_'.$i] != '') {
					@$links .= '[URL='.check_url($_POST['url_'.$i].']'.$_POST['link_'.$i].'[/URL]');
				}
				$i++;
			}
			$sql = 'UPDATE '.DB_PRE.'ecp_news SET `topicID` = '.(int)$_POST['topic'].',
										`access` =  \''.$rights.'\', 
										`lang` =  \''.strsave($lang).'\', 
										`datum` = '.$zeit.', 
										`headline` = \''.strsave($_POST['headline']).'\', 
										`bodytext` = \''.strsave($_POST['bodytext']).'\', 
										`extendtext` = \''.strsave($_POST['extendtext']).'\', 
										`links` = \''.strsave(@$links).'\'
									WHERE newsID = '.$id.';';
			if($db->query($sql)) {
				header1('?section=admin&site=news');
			}
		}
	} else {
		$news = $db->fetch_assoc('SELECT `topicID`, `access`, `lang`, `datum`, `headline`, `bodytext`, `extendtext`, `links` FROM `'.DB_PRE.'ecp_news` WHERE newsID = '.$id);
		if(is_array($news)) {
			$tpl = new smarty;
			$news['datum'] = date('Y-m-d H:i:s', $news['datum']);
			$db->query('SELECT tID, topicname FROM '.DB_PRE.'ecp_topics ORDER by topicname ASC');
			$topics = '';
			while($row = $db->fetch_assoc()) {
			($news['topicID'] == $row['tID'])? $sub = ' selected="selected"' : $sub = '';
			$topics .= '<option'.$sub.' value="'.$row['tID'].'">'.$row['topicname'].'</option>';
			}
			$tpl->assign('topics', $topics);
			$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
			if ($news['access'] == '') {
				$rights = '<option value="all" selected="selected">'.ALL.'</option>';
				$rechte = array();
			} else {
				$rechte = explode(',', substr($news['access'],1,strlen($news['access'])-1));
				$rights = '<option value="all">'.ALL.'</option>';
			}
			while($row = $db->fetch_assoc()) {
			(in_array($row['groupID'], $rechte))? $sub = ' selected="selected"' : $sub = '';
			if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
			$rights .= '<option'.$sub.' value="'.$row['groupID'].'">'.$row['name'].'</option>';
			}
			$files = scan_dir('inc/language', true);
			if ($news['lang'] == '') {
				$languages = '<option value="all" selected="selected">'.ALL.'</option>';
				$lang1 = array();
			} else {
				$lang1 = explode(',', substr($news['lang'],1,strlen($news['lang'])-1));
				$languages = '<option value="all">'.ALL.'</option>';
			}
			foreach($files AS $lang) {
				if(strpos($lang, '.php')) {
					$lang = substr($lang,0,strpos($lang, '.'));
					(in_array($lang, $lang1))? $sub = ' selected="selected"' : $sub = '';
					@$languages .= '<option'.$sub.' value="'.$lang.'">'.@$language_array[$lang].'</option>';
				}
			}
			if($news['links'] == '')
			$links = array(0);
			else {
				preg_match_all('#\[URL=(.*)\](.*)\[/URL\]#Uis', $news['links'], $spe);
				for($i = 0; $i<count($spe[1]); $i++) {
					$links[$i]['url'] = $spe[1][$i];
					$links[$i]['link'] = $spe[2][$i];
				}
			}
			foreach($news AS $key => $value) $tpl->assign($key, $value);
			$tpl->assign('links', $links);
			$tpl->assign('rights', $rights);
			$tpl->assign('topics', $topics);
			$tpl->assign('languages', $languages);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/news_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(ADMIN_NEWS_ADD, $content, '',1);
		} else {
			table(ERROR, NO_ENTRIES);
		}
	}
}
function admin_news_del($id) {
	global $db;
	if(isset($_GET['agree'])) {
		$id = (int)$_GET['id'];
		if($db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', 'newsID = '.$id)) {
			if($db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE subID = '.$id.' AND bereich = "news"')){
				if($db->query('DELETE FROM '.DB_PRE.'ecp_news WHERE newsID = '.$id)) {
					header1('?section=admin&site=news');
				}
			}
		} else {
			echo NO_ENTRIES_ID;
		}
	} else {
		table(DELETE, '<center>'.DEL_NEWS.'<br /><a href="?section=admin&amp;site=news&amp;func=del&amp;id='.$id.'&amp;agree=1"><span class="error">'.YES.'</span></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="?section=admin&amp;site=news">'.NO.'</a></center>');
	}
}
if (!isset($_SESSION['rights']['admin']['news']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_news_add();
				break;
			case 'edit':
				admin_news_edit((int)$_GET['id']);
				break;
			case 'del':
				admin_news_del((int)$_GET['id']);
				break;
			case 'pin':
				admin_news();
				break;
			default:
				admin_news();
		}
	} else {
		admin_news();
	}
}
?>