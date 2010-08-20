<?php
if(defined('VERSION')) {
	if(@$_SESSION['rights']['public']['news']['view'] OR @$_SESSION['rights']['superadmin']) {
		$sql 	= 'SELECT `newsID`, `'.DB_PRE.'ecp_news`.`userID`, `topicID`, `'.DB_PRE.'ecp_news`.`datum`, `headline`,
						`username`, `topicname`, COUNT(comID) AS comments, country
						FROM '.DB_PRE.'ecp_news 
						LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_news.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND '.DB_PRE.'ecp_news.datum < '.time().' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY datum DESC';					

		$db->query($sql.' LIMIT '.LIMIT_MINI_NEWS);
		if($db->num_rows()) {
			$tpls = new smarty;
			$news = array();
			while($row1 = $db->fetch_assoc()) {
				$row1['datum'] = date(LONG_DATE, $row1['datum']);
				$news[] =$row1;
			}
			$tpls->assign('news', $news);
			$tpls->display(DESIGN.'/tpl/modul/lastnews.html');			
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