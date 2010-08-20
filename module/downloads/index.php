<?php
function downloads() {
	global $db;
	$tpl = new smarty;
	ob_start();
	$tpl->display(DESIGN.'/tpl/downloads/head.html');
	$downloads = array();
	$result = $db->query('SELECT  DISTINCT(a.kID) as kID,a.kname, a.subkID, a.beschreibung, COUNT(b.kID) as subs, COUNT(c.dID) as dls
						  FROM '.DB_PRE.'ecp_downloads_kate a 
						  LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
						  LEFT JOIN '.DB_PRE.'ecp_downloads c ON a.kID = c.cID
						  WHERE a.subkID = 0 AND (a.access = "" OR '.str_replace('access', 'a.access',$_SESSION['access_search']).')
						  GROUP BY a.kID
						  ORDER BY a.kname ASC');
	$i = 0;
	$spe = array();
	while($row = mysql_fetch_assoc($result)) {
		$lang = json_decode($row['beschreibung'], true);
		if(isset($lang[LANGUAGE])) {
			$row['beschreibung'] = $lang[LANGUAGE]; 
		} else  {
			$row['beschreibung'] = @$lang['de'];
		}
		$spe[$i] = $row;
		if($row['subs']) { 
			$spe[$i]['unter'] = get_dl_subs($row['kID']);
		} 
		if(++$i == 2) {
			$i = 0;
			$downloads[] = $spe;
			$spe = array();
		}
	}
	if($i > 0) $downloads[] = $spe;
	$tpl->assign('downloads', $downloads);
	$tpl->display(DESIGN.'/tpl/downloads/kate.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(DOWNLOADS, $content, '', 1);	
}
function downloads_kate($id) {
	global $db;
	$tpl = new smarty;
	ob_start();
	$kate = $db->fetch_assoc('SELECT kname, subkID, beschreibung FROM '.DB_PRE.'ecp_downloads_kate WHERE kID = '.$id.' AND (access = "" OR '.$_SESSION['access_search'].')');
	if(isset($kate['kname'])) {
		$lang = json_decode($kate['beschreibung'], true);
		$tpl->assign('beschreibung', (isset($lang[LANGUAGE])) ? $lang[LANGUAGE] : @$lang['de']); 
		if($kate['subkID']) {
			$tpl->assign('pfad', dl_get_path($kate['subkID']).'->'.$kate['kname']);
		} else {
			$tpl->assign('pfad', '<a href="?section=downloads">'.OVERVIEW.'</a>->'.$kate['kname']);
		}
		$tpl->display(DESIGN.'/tpl/downloads/head.html');	
		$downloads = array();
		$result = $db->query('SELECT DISTINCT(a.kID) as kID, a.kname, a.subkID, a.beschreibung, COUNT(b.kID) as subs, COUNT(c.dID) as dls
							  FROM '.DB_PRE.'ecp_downloads_kate a 
							  LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON a.kID = b.subkID
							  LEFT JOIN '.DB_PRE.'ecp_downloads c ON a.kID = c.cID
							  WHERE a.subkID = '.$id.' AND (a.access = "" OR '.str_replace('access', 'a.access',$_SESSION['access_search']).')
							  GROUP BY a.kID
							  ORDER BY a.kname ASC');
		$i = 0;
		$spe = array();
		while($row = mysql_fetch_assoc($result)) {
			$lang = json_decode($row['beschreibung'], true);
			if(isset($lang[LANGUAGE])) {
				$row['beschreibung'] = $lang[LANGUAGE]; 
			} else  {
				$row['beschreibung'] = @$lang['de'];
			}
			$spe[$i] = $row;
			if($row['subs']) { 
				$spe[$i]['unter'] = get_dl_subs($row['kID']);
			}
			if(++$i == 2) {
				$i = 0;
				$downloads[] = $spe;
				$spe = array();
			}
		}
		if($i > 0) $downloads[] = $spe;
		$tpl->assign('downloads', $downloads);
		$tpl->display(DESIGN.'/tpl/downloads/kate.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(DOWNLOADS_MENU, $content, '', 1);
		$db->query('SELECT dID,	name, '.DB_PRE.'ecp_downloads.userID, info, 
					'.DB_PRE.'ecp_downloads.homepage, version, size, traffic, downloads, 
					'.DB_PRE.'ecp_downloads.datum, COUNT(comID) as comments, username 
					FROM '.DB_PRE.'ecp_downloads 
					LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_downloads.userID = ID) 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "downloads" AND subID = dID) 
					WHERE cID = '.$id.' AND (access = "" OR '.$_SESSION['access_search'].') 
					GROUP BY dID
					ORDER BY name ASC');	
		if($db->num_rows()) {
			$downloads = array();
			while($row = $db->fetch_assoc()) {
				$row['size'] = goodsize($row['size']);
				$row['traffic'] = goodsize($row['traffic']);
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$row['downloads'] = number_format($row['downloads'], 0,',', '.');
				$lang = json_decode($row['info'], true);
				if(isset($lang[LANGUAGE])) {
					$row['info'] = $lang[LANGUAGE]; 
				} else  {
					$row['info'] = @$lang['de'];
				}				
				$downloads[] = $row;
			}
			$tpl = new smarty();
			$tpl->assign('dls', $downloads);
			ob_start();		
			$tpl->display(DESIGN.'/tpl/downloads/downloads.html');				
			$content = ob_get_contents();
			ob_end_clean();
			main_content(DOWNLOADS, $content, '', 1);
		}
	} else {
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
}
function get_dl_subs($id) {
	global $db;
	$result = $db->query('SELECT a.kname, a.kID, a.subkID, COUNT(c.dID) as dls
						  FROM '.DB_PRE.'ecp_downloads_kate a 
						  LEFT JOIN '.DB_PRE.'ecp_downloads c ON a.kID = c.cID
						  WHERE a.subkID = '.$id.' AND (a.access = "" OR '.str_replace('access', 'a.access',$_SESSION['access_search']).')
						  GROUP BY a.kID
						  ORDER BY a.kname ASC');
	$dl = array();
	while($row = mysql_fetch_assoc($result)) {
		$dl[] = $row;
	}
	return $dl;
}
function dl_get_path($sub) { 
	global $db;
	$link = '';	
	do {
		$row = $db->fetch_assoc('SELECT a.kname AS kname1, a.kID AS kID1, a.subkID AS subkID1, b.kname, b.kID, b.subkID
								FROM '.DB_PRE.'ecp_downloads_kate a
								LEFT JOIN '.DB_PRE.'ecp_downloads_kate b ON ( a.subkID = b.kID ) 
								WHERE a.kID ='.$sub);
		$sub = (int)$row['subkID'];
		$link = '-><a href="?section=downloads&action=viewkate&id='.$row['kID1'].'">'.$row['kname1'].'</a>'.$link;
		if($row['subkID'] != '') {
			$link = '-><a href="?section=downloads&action=viewkate&id='.$row['kID'].'">'.$row['kname'].'</a>'.$link;
		}
	} while ($sub);
	return '<a href="?section=downloads">'.OVERVIEW.'</a>'.$link;
}
function get_file($id) {
	global $db;
	$row = $db->fetch_assoc('SELECT url, size FROM '.DB_PRE.'ecp_downloads WHERE dID = '.$id.' AND (access = "" OR '.$_SESSION['access_search'].')');
	if(isset($row['url'])) {
		$db->query('UPDATE '.DB_PRE.'ecp_downloads SET downloads = downloads + 1, traffic =  traffic + '.$row['size'].' WHERE dID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_stats SET dltraffic = dltraffic + '.$row['size']);
		header("Location: ".$row['url']);
	} else {
		table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
}
function dl_view($id) {
	global $db;
	$row = $db->fetch_assoc('SELECT dID, name, '.DB_PRE.'ecp_downloads.userID, info, kID, subkID, kname, 
					'.DB_PRE.'ecp_downloads.homepage, version, size, traffic, downloads, 
					'.DB_PRE.'ecp_downloads.datum, COUNT(comID) as comments, username 
					FROM '.DB_PRE.'ecp_downloads 
					LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_downloads.userID = ID) 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "downloads" AND subID = dID) 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate ON (kID = cID)
					WHERE dID = '.$id.' AND ('.DB_PRE.'ecp_downloads.access = "" OR '.str_replace('access', DB_PRE.'ecp_downloads.access',$_SESSION['access_search']).') 
					GROUP BY dID
					ORDER BY name ASC');
	if(isset($row['dID'])) {
		$tpl = new smarty();
		if($row['subkID']) {
			$tpl->assign('pfad', dl_get_path($row['kID']).'->'.$row['name']);
		} else {
			$tpl->assign('pfad', '<a href="?section=downloads">'.OVERVIEW.'</a>-><a href="?section=downloads&action=viewkate&id='.$row['kID'].'">'.$row['kname'].'</a>'.'->'.$row['name']);
		}		
		$row['size'] = goodsize($row['size']);
		$row['datum'] = date(LONG_DATE, $row['datum']);
		$row['traffic'] = goodsize($row['traffic']);
		$row['downloads'] = number_format($row['downloads'], 0,',', '.');
		$lang = json_decode($row['info'], true);
		if(isset($lang[LANGUAGE])) {
			$row['info'] = $lang[LANGUAGE]; 
		} else  {
			$row['info'] = @$lang['de'];
		}					
		foreach($row AS $key=>$value) $tpl->assign($key, $value);
		ob_start();		
		$tpl->display(DESIGN.'/tpl/downloads/download_view.html');				
		$content = ob_get_contents();
		ob_end_clean();
		main_content(DOWNLOADS, $content, '', 1);
		if(@$_SESSION['rights']['public']['downloads']['com_view'] OR @$_SESSION['rights']['superadmin']) {
			$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_DOWNLOADS_COMMENTS,
					'section'   => 'downloads');
			$conditions['action'] = 'add';
			$conditions['link'] = '?section=downloads&action=viewdl&id='.$id;
			comments_get('downloads', $id, $conditions);					
		}
	} else {
		table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
			
}
function download_search() {
	global $db;
	downloads();
	$db->query('SELECT dID, name, '.DB_PRE.'ecp_downloads.userID, info, kID, subkID, kname, 
					'.DB_PRE.'ecp_downloads.homepage, version, size, traffic, downloads, 
					'.DB_PRE.'ecp_downloads.datum, COUNT(comID) as comments, username 
					FROM '.DB_PRE.'ecp_downloads 
					LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_downloads.userID = ID) 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "downloads" AND subID = dID) 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate ON (kID = cID)
					WHERE name LIKE \'%'.strsave($_POST['search_name']).'%\' AND ('.DB_PRE.'ecp_downloads.access = "" OR '.str_replace('access', DB_PRE.'ecp_downloads.access',$_SESSION['access_search']).') 
					GROUP BY dID
					ORDER BY name ASC');	
	if($db->num_rows()) {
			$tpl = new smarty;		
			$downloads = array();
			while($row = $db->fetch_assoc()) {
				$row['size'] = goodsize($row['size']);
				$row['traffic'] = goodsize($row['traffic']);
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$row['downloads'] = number_format($row['downloads'], 0,',', '.');
				$lang = json_decode($row['info'], true);
				if(isset($lang[LANGUAGE])) {
					$row['info'] = $lang[LANGUAGE]; 
				} else  {
					$row['info'] = @$lang['de'];
				}				
				$downloads[] = $row;
			}
			$tpl->assign('dls', $downloads);
			ob_start();		
			$tpl->display(DESIGN.'/tpl/downloads/downloads.html');				
			$content = ob_get_contents();
			ob_end_clean();
			main_content(DOWNLOADS, $content, '', 1);
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function download_spezial() {
	global $db;
	downloads();
	switch ($_GET['view']) {
		case 'new':
			$field = 'datum';
		break;
		case 'hits':
			$field = 'downloads';
		break;
		case 'traffic':
			$field = 'traffic';
		break;
		default:
			$field = 'datum';
	}
	$db->query('SELECT dID, name, '.DB_PRE.'ecp_downloads.userID, info, kID, subkID, kname, 
					'.DB_PRE.'ecp_downloads.homepage, version, size, traffic, downloads, 
					'.DB_PRE.'ecp_downloads.datum, COUNT(comID) as comments, username 
					FROM '.DB_PRE.'ecp_downloads 
					LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_downloads.userID = ID) 
					LEFT JOIN '.DB_PRE.'ecp_comments ON (bereich = "downloads" AND subID = dID) 
					LEFT JOIN '.DB_PRE.'ecp_downloads_kate ON (kID = cID)
					WHERE '.DB_PRE.'ecp_downloads.access = "" OR '.str_replace('access', DB_PRE.'ecp_downloads.access',$_SESSION['access_search']).' 
					GROUP BY dID
					ORDER BY '.$field.' DESC LIMIT 15');	
	if($db->num_rows()) {
			$tpl = new smarty;		
			$downloads = array();
			while($row = $db->fetch_assoc()) {
				$row['size'] = goodsize($row['size']);
				$row['traffic'] = goodsize($row['traffic']);
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$row['downloads'] = number_format($row['downloads'], 0,',', '.');
				$lang = json_decode($row['info'], true);
				if(isset($lang[LANGUAGE])) {
					$row['info'] = $lang[LANGUAGE]; 
				} else  {
					$row['info'] = @$lang['de'];
				}				
				$downloads[] = $row;
			}
			$tpl->assign('dls', $downloads);
			ob_start();		
			$tpl->display(DESIGN.'/tpl/downloads/downloads.html');				
			$content = ob_get_contents();
			ob_end_clean();
			main_content(DOWNLOADS, $content, '', 1);
	} else {
		table(INFO, NO_ENTRIES);
	}
}
if(isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'viewkate':
			if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin']) 
				downloads_kate((int)$_GET['id']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 
		case 'viewdl':
			if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin']) 
				dl_view((int)$_GET['id']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 	
		case 'search':
			if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin']) 
				download_search();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 	
		case 'spezial':
			if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin']) 
				download_spezial();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 
		case 'getfile':
			if(@$_SESSION['rights']['public']['downloads']['download'] OR @$_SESSION['rights']['superadmin']) 
				get_file((int)$_GET['id']);
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);	
		break; 
		case 'addcomment':
			if(@$_SESSION['rights']['public']['downloads']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=downloads&action=viewdl&id='.(int)$_GET['id'];
				comments_add('downloads', (int)$_GET['id'], $conditions);
			} else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
		break;
		case 'editcomment':
			$conditions['action'] = 'edit';
			$conditions['link'] = '?section=downloads&action=viewdl&id='.(int)$_GET['subid'];
			comments_edit('downloads', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
		break;					
		default:
			if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin'])
				downloads();
			else
				echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
	}
} else {
	if(@$_SESSION['rights']['public']['downloads']['view'] OR @$_SESSION['rights']['superadmin'])
		downloads();
	else
		echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
}
?>