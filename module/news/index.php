<?php
function news($topicID = 0) {
	global $db;
	if($topicID) {
		$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND topicID = '.$topicID.' AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].')');
		$seiten = get_sql_limit($anzahl, LIMIT_NEWS);
		$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`, `bodytext`, `extendtext`, `links`, `hits`,
							  `username`, `topicname`, `topicbild`, `beschreibung`, COUNT(comID) AS comments 
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND topicID = '.$topicID.' AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY sticky DESC, datum DESC';
	} else {
		$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].')');
		$seiten = get_sql_limit($anzahl, LIMIT_NEWS);
		$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`, `bodytext`, `extendtext`, `links`, `hits`,
							  `username`, `topicname`, `topicbild`, `beschreibung`, COUNT(comID) AS comments 
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY sticky DESC, datum DESC';			
	}
	if($anzahl) {
		if(!isset($_GET['page'])) $_GET['page'] = 1;
		$db->query($sql.' LIMIT '.$seiten[1].','.LIMIT_NEWS);
		while($row = $db->fetch_assoc()) {
			$tpl = new smarty;
			$row['bodytext'] = bb_code($row['bodytext']);
			$row['extendtext'] = bb_code($row['extendtext']);
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$row['links'] = news_links($row['links']);
			foreach($row AS $key=>$value)
			$tpl->assign($key, $value);
			$tpl->assign('pic', (file_exists('templates/'.DESIGN.'/images/topics/'.$row['topicbild']))? 'templates/'.DESIGN.'/images/topics/'.$row['topicbild'] : 'images/topics/'.$row['topicbild']);
			ob_start();
			$tpl->display(DESIGN.'/tpl/news/news.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content('<a href="?section=news&amp;action=topic&amp;id='.$row['topicID'].'">'.$row['topicname'].'</a>: '.$row['headline'], $content, '',0);
			if($row['extendtext'])
			@$slids .= 	'news_toogle_'.$row['newsID'].' = new Fx.Slide(\'news_'.$row['newsID'].'\'); news_toogle_'.$row['newsID'].'.hide();';
		}
		if($seiten[0] > 1)
		table(PAGES,'<div style="text-align:center">'.NEWS.': '.$anzahl.' | <a href="?section=news&amp;action=archiv">'.NEWS_ARCHIV.'</a> | '.PAGES.': '.makepagelink('?section=news&action=topic&id='.$topicID, $_GET['page'], $seiten[0]).'</div>');
		if(isset($slids)) echo '<script type="text/javascript">window.addEvent(\'domready\', function() { '.@$slids.' } );</script>';
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function news_archiv($topicID = 0) {
	global $db;
	$topics = array();
	$db->query('SELECT tID, topicname FROM '.DB_PRE.'ecp_topics ORDER BY topicname ASC');
	while($row = $db->fetch_assoc()) $topics[] = $row;
	if($topicID) {
		$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND topicID = '.$topicID.' AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].')');
		$seiten = get_sql_limit($anzahl, 30);
		$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`, `bodytext`, `extendtext`, `links`, `hits`,
							  `username`, `topicname`, `topicbild`, `beschreibung`, COUNT(comID) AS comments 
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND topicID = '.$topicID.' AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY sticky DESC, datum DESC';
	} else {
		$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].')');
		$seiten = get_sql_limit($anzahl, 30);
		$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`, `bodytext`, `extendtext`, `links`, `hits`,
							  `username`, `topicname`, `topicbild`, `beschreibung`, COUNT(comID) AS comments 
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY sticky DESC, datum DESC';			
	}
	if($anzahl) {
		if(!isset($_GET['page'])) $_GET['page'] = 1;
		$db->query($sql.' LIMIT '.$seiten[1].', 30');
		$news = array();
		while($row = $db->fetch_assoc()) {
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$row['comments'] = format_nr($row['comments']);
			$row['hits'] = format_nr($row['hits']);
			$news[] = $row;
		}
		$tpl = new smarty;
		$tpl->assign('topics', $topics);
		$tpl->assign('news', $news);
		ob_start();
		$tpl->display(DESIGN.'/tpl/news/news_archiv.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(NEWS_ARCHIV, $content, '', 1);
		if($seiten[0] > 1)
		table(PAGES,'<div style="text-align:center">'.NEWS.': '.$anzahl.' | '.PAGES.': '.makepagelink('?section=news&action=archiv&tid='.$topicID, $_GET['page'], $seiten[0]).'</div>');
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function news_once($id) {
	global $db;
	$anzahl = $db->result(DB_PRE.'ecp_news', 'COUNT(newsID)', '(lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') AND newsID = '.$id);
	if(!isset($_SESSION['news'][(int)$_GET['id']])) {
		if($db->query('UPDATE '.DB_PRE.'ecp_news SET hits = hits + 1 WHERE newsID = '.(int)$_GET['id'])) {
			$_SESSION['news'][(int)$_GET['id']] = true;
		}
	}	
	$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`, `bodytext`, `extendtext`, `links`, `hits`,
							  `username`, `topicname`, `topicbild`, `beschreibung`
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') AND newsID = '.$id.' GROUP BY newsID';			
	if($anzahl) {
		$db->query($sql);
		while($row = $db->fetch_assoc()) {
			$tpl = new smarty;
			$tpl->assign('comment', 1);
			$row['bodytext'] = bb_code($row['bodytext']);
			$row['extendtext'] = bb_code($row['extendtext']);			
			$row['datum'] = date(LONG_DATE, $row['datum']);
			$row['links'] = news_links($row['links']);
			foreach($row AS $key=>$value)
			$tpl->assign($key, $value);
			$tpl->assign('pic', (file_exists('templates/'.DESIGN.'/images/topics/'.$row['topicbild']))? 'templates/'.DESIGN.'/images/topics/'.$row['topicbild'] : 'images/topics/'.$row['topicbild']);
			ob_start();
			$tpl->display(DESIGN.'/tpl/news/news.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content('<a href="?section=news&amp;action=topic&amp;id='.$row['topicID'].'">'.$row['topicname'].'</a>: '.$row['headline'], $content, '',0);
		}
	} else {
		table(INFO, NO_ENTRIES_ID);
	}
}
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_NEWS_COMMENTS,
					'section'   => 'news');
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'topic':
			news((int)@$_GET['id']);
		break;
		case 'archiv':
			news_archiv((int)@$_GET['tid']);
		break;		
		case 'comments':
			if(@$_SESSION['rights']['public']['news']['com_view'] OR @$_SESSION['rights']['superadmin']) {
				news_once((int)$_GET['id']);
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=news&action=comments&id='.(int)$_GET['id'];
				comments_get('news', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		break;
		case 'addcomment':
			if(@$_SESSION['rights']['public']['news']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=news&action=comments&id='.(int)$_GET['id'];
				comments_add('news', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=news&action=comments&id='.(int)$_GET['subid'];
			comments_edit('news', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;		
		default:
		if(@$_SESSION['rights']['public']['news']['view'] OR @$_SESSION['rights']['superadmin'])
			news();
		else
			echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['news']['view'] OR @$_SESSION['rights']['superadmin'])
		news();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>