<?php
	if(@$_SESSION['rights']['public']['links']['view'] OR @$_SESSION['rights']['superadmin']) {
		global $db;
		if(isset($_GET['goto'])) {
			$link = $db->result(DB_PRE.'ecp_links', 'url', 'linkID= '.(int)$_GET['goto']);
			if($link) {
				if(!isset($_SESSION['links'][(int)$_GET['id']])) {
					$_SESSION['links'][(int)$_GET['id']] = true;
					$db->query('UPDATE '.DB_PRE.'ecp_links SET hits = hits + 1 WHERE linkID = '.(int)$_GET['goto']);
				}
				header('Location: '.$link);
			} else {
				table(ERROR, NO_ENTRIES_ID);
			}
		} else {
			$tpl = new smarty;	
			$anzahl = $db->result(DB_PRE.'ecp_links', 'COUNT(linkID)', '1');
			if($anzahl) {
				$limits = get_sql_limit($anzahl, LIMIT_LINKS);
				$links = array();
				$db->query('SELECT * FROM '.DB_PRE.'ecp_links ORDER BY name ASC LIMIT '.$limits[1].','.LIMIT_LINKS);
				while($row = $db->fetch_assoc()) {
					$row['hits'] = format_nr($row['hits'], 0);
					$links[] = $row;
				}
				$tpl->assign('links', $links);
				if($limits[0] > 1)
					$tpl->assign('seiten', makepagelink_ajax('?section=links', 'return load_links({nr});', @$_GET['page'], $limits[0]));
				ob_start();
				$tpl->display(DESIGN.'/tpl/links/links.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(LINKS, '<div id="weblinks">'.$content.'</div>', '',1);								
			} else {
				table(INFO, NO_ENTRIES);
			}
		}
	} else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
?>