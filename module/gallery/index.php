<?php
function gallery() {
	global $db;
	$db->query('SELECT `kateID`, `katename`, `beschreibung`, `galleries` FROM '.DB_PRE.'ecp_gallery_kate WHERE access = "" OR '.$_SESSION['access_search'].' ORDER by katename ASC');
	if($db->num_rows()) {
    	$kates = array();
    	while($row = $db->fetch_assoc()) {
    		$beschreibung = json_decode($row['beschreibung'],true);
    		if(isset($beschreibung[LANGUAGE])) $row['beschreibung'] = $beschreibung[LANGUAGE]; else $row['beschreibung'] = $beschreibung[DEFAULT_LANG];
    		$kates[] =$row;
    	}
    	$tpl = new smarty;
    	$tpl->assign('kate', $kates);
		ob_start();
		$tpl->display(DESIGN.'/tpl/gallery/kate.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GALLERY, $content, '',1);
	} else {
		table(INFO, NO_ENTRIES);
	}
}
function gallery_kate($id) {
	global $db;
	$kate = $db->fetch_assoc('SELECT katename, galleries FROM '.DB_PRE.'ecp_gallery_kate WHERE (access = "" OR '.$_SESSION['access_search'].') AND kateID = '.$id);
	if(isset($kate['katename'])) {
		$limits = get_sql_limit($kate['galleries'], LIMIT_GALLERY);
		$gallery = array();
		$result = $db->query('SELECT `galleryID`, `name`, userID, `folder`, `images`, `datum`, username FROM '.DB_PRE.'ecp_gallery AS a LEFT JOIN '.DB_PRE.'ecp_user ON ID=userID WHERE cID = '.$id.' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY datum DESC LIMIT '.$limits[1].','.LIMIT_GALLERY);
		while($row = mysql_fetch_assoc($result)) {
			$row = array_merge($row, $db->fetch_assoc('SELECT imageID, filename FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$row['galleryID'].' ORDER BY rand() LIMIT 1'));
			$row['datum'] = date('d.m.Y', $row['datum']);
			$gallery[] = $row;
		}
		$tpl = new smarty;
		if($limits[0] > 1)
			$tpl->assign('seiten', makepagelink_ajax('?section=gallery&action=kate&id='.$_GET['id'], 'return load_kate_page('.$_GET['id'].', {nr});', @$_GET['page'], $limits[0]));
		$tpl->assign('gallery', $gallery);
		ob_start();
		$tpl->display(DESIGN.'/tpl/gallery/gallery.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GALLERY, '<div id="kate_overview">'.$content.'</div>', '',1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function gallery_gallery($id) {
	global $db;
	$gallery = $db->fetch_assoc('SELECT katename, b.access, name, images, cID, folder, userID, datum, username FROM '.DB_PRE.'ecp_gallery as a LEFT JOIN '.DB_PRE.'ecp_user ON ID=userID LEFT JOIN '.DB_PRE.'ecp_gallery_kate as b ON (cID = kateID) WHERE (a.access = "" OR '.str_replace('access', 'a.access', $_SESSION['access_search']).') AND galleryID = '.$id);
	if(isset($gallery['name']) AND find_access($gallery['access'])) {
		$limits = get_sql_limit($gallery['images'], LIMIT_GALLERY_PICS);
		$pics = array();
		$result = $db->query('SELECT imageID, filename, klicks, COUNT(comID) as comments FROM '.DB_PRE.'ecp_gallery_images as A LEFT JOIN '.DB_PRE.'ecp_comments ON (subID=imageID AND bereich="gallery") WHERE gID = '.$id.' GROUP BY imageID ORDER BY imageID ASC LIMIT '.$limits[1].','.LIMIT_GALLERY_PICS);
		while($row = mysql_fetch_assoc($result)) {
			$row['klicks'] = format_nr($row['klicks'], 0);
			$pics[] = $row;
		}
		$tpl = new smarty;
		if($limits[0] > 1)
			$tpl->assign('seiten', makepagelink_ajax('?section=gallery&action=gallery&id='.$id, 'return load_gallery_page('.$id.', {nr});', @$_GET['page'], $limits[0]));
		$tpl->assign('pics', $pics);
		$tpl->assign('datum', date(LONG_DATE, $gallery['datum']));
		$tpl->assign('username', $gallery['username']);
		$tpl->assign('userID', $gallery['userID']);
		$tpl->assign('pfad', '<a href="?section=gallery">'.GALLERY.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=kate&id='.$gallery['cID'].'">'.$gallery['katename'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=gallery&id='.$id.'">'.$gallery['name'].'</a>');
		$tpl->assign('folder', $gallery['folder']);
		ob_start();
		$tpl->display(DESIGN.'/tpl/gallery/pictures.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GALLERY, $content, '',1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function gallery_viewpic($id) {
	global $db;
	$image = $db->fetch_assoc('SELECT gID, katename, b.access as kateacces, a.access as access, name, images, cID, folder, filename, uploaded, c.beschreibung, klicks, c.userID, username FROM '.DB_PRE.'ecp_gallery_images AS c LEFT JOIN '.DB_PRE.'ecp_gallery as a ON (gID = galleryID) LEFT JOIN '.DB_PRE.'ecp_user ON ID=c.userID LEFT JOIN '.DB_PRE.'ecp_gallery_kate as b ON (cID = kateID) WHERE imageID = '.$id);
	if(isset($image['uploaded']) AND find_access($image['access']) AND find_access($image['kateacces'])) {
		if(!isset($_SESSION['gallery'][$id])) {
			$db->query('UPDATE '.DB_PRE.'ecp_gallery_images SET klicks=klicks+1 WHERE imageID = '.$id);	
			$_SESSION['gallery'][$id] = true;
		}
		$tpl = new smarty;
		$image['uploaded'] = date(LONG_DATE, $image['uploaded']);
		$tpl->assign('pfad', '<a href="?section=gallery">'.GALLERY.'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=kate&id='.$image['cID'].'">'.$image['katename'].'</a> <img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="" /> <a href="?section=gallery&action=gallery&id='.$image['gID'].'">'.$image['name'].'</a>');
		$tpl->assign('vorID', @$db->result(DB_PRE.'ecp_gallery_images', 'imageID', 'gID = '.$image['gID'].' AND imageID < '.$id.' ORDER BY imageID DESC LIMIT 1'));
		$tpl->assign('nachID', @$db->result(DB_PRE.'ecp_gallery_images', 'imageID', 'gID = '.$image['gID'].' AND imageID > '.$id.' ORDER BY imageID ASC LIMIT 1'));
		foreach($image AS $key=>$value) $tpl->assign($key, $value);
		ob_start();
		$tpl->display(DESIGN.'/tpl/gallery/view_pic.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GALLERY, '<div id="display_pic">'.$content.'</div>', '',1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
if(@$_SESSION['rights']['public']['gallery']['view'] OR @$_SESSION['rights']['superadmin']) {	
$conditions = array('LIMIT' 	=> LIMIT_COMMENTS,
					'ORDER'		=> COMMENTS_ORDER,
					'SPAM'		=> SPAM_GALLERY_COMMENTS,
					'section'   => 'gallery');	
	if(isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'kate':
				gallery_kate((int)$_GET['id']);
			break;	
			case 'gallery':
				gallery_gallery((int)$_GET['id']);
			break;	
			case 'viewpic':
				gallery_viewpic((int)$_GET['id']);
				$conditions['action'] = 'add';
				$conditions['link'] = '?section=gallery&action=viewpic&id='.(int)$_GET['id'];
				comments_get('gallery',  (int)$_GET['id'], $conditions);				
			break;	
			case 'addcomment':
				if(@$_SESSION['rights']['public']['gallery']['com_add'] OR @$_SESSION['rights']['superadmin']) {		
					$conditions['action'] = 'add';
					$conditions['link'] = '?section=gallery&action=viewpic&id='.(int)$_GET['id'];
					comments_add('gallery', (int)$_GET['id'], $conditions);
				} else
					echo table(ACCESS_DENIED, NO_ACCESS_RIGHTS);			
			break;	
			case 'editcomment':
				$conditions['action'] = 'edit';
				$conditions['link'] = '?section=gallery&action=viewpic&id='.(int)$_GET['subid'];
				comments_edit('gallery', (int)$_GET['subid'], (int)$_GET['id'], $conditions);		
			break;															
			default:
				gallery();
		}
	} else {
		gallery();
	}
}  else {
	table(ERROR, NO_ACCESS_RIGHTS);
}
?>