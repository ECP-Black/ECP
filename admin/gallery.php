<?php
function admin_gallery () {
	global $db;
	$tpl = new smarty;
	$tpl->assign('access', get_form_rights());
	$tpl->assign('folders', get_dirs());
	$tpl->assign('lang', get_languages());
	$db->query('SELECT kateID, katename, galleries FROM '.DB_PRE.'ecp_gallery_kate ORDER BY katename ASC');
	$kate = array();
	while($row = $db->fetch_assoc()) {
		@$options .= '<option value="'.$row['kateID'].'">'.$row['katename'].'</option>';
		$kate[] = $row;
	}
	$tplc = new Smarty();
	$tplc->assign('kate', $kate);
	ob_start();
	$tplc->display(DESIGN.'/tpl/admin/gallery_kate_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	$tpl->assign('kate', $content);
	$tpl->assign('kategorien', @$options);
	$db->query('SELECT galleryID, folder, name, images, katename FROM '.DB_PRE.'ecp_gallery LEFT JOIN '.DB_PRE.'ecp_gallery_kate ON cID = kateID ORDER BY name ASC');
	$gallery = array();
	while($row = $db->fetch_assoc()) {
		$gallery[] = $row;
	}
	$tplc = new Smarty();
	$tplc->assign('gallery', $gallery);
	ob_start();
	$tplc->display(DESIGN.'/tpl/admin/gallery_overview.html');
	$content = ob_get_contents();
	ob_end_clean();
	$tpl->assign('gallery', $content);	
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/gallery.html');
	$content = ob_get_contents();
	ob_end_clean();
	main_content(GALLERY, $content, '',1);
}
function admin_gallery_del($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	if(!isset($_SESSION['rights']['admin']['gallery']['del']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($db->query()) {
			echo 'ok';
		}
	}
	die();
}
function admin_gallery_add_kate() {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['gallery']['kate_add']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($_POST['katename'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {		
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}		
			$lang = json_encode($lang);
			$sql =sprintf('INSERT INTO '.DB_PRE.'ecp_gallery_kate (katename, access, beschreibung) VALUES (\'%s\', \'%s\', \'%s\')', strsave($_POST['katename']), strsave(admin_make_rights($_POST['access'])), strsave($lang));
			if($db->query($sql)) {
				echo $db->last_id();
			}
		}
	}	
	die();
}
function admin_gallery_edit_kate($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['gallery']['kate_edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($_POST['katename'] == '') {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$lang = array();
			foreach($_POST AS $key => $value) {
				if(strpos($key, 'cription_')) {
					$lang[substr($key,strpos($key, '_')+1)] = $value;
				}
			}		
			$lang = json_encode($lang);
			$sql =sprintf('UPDATE '.DB_PRE.'ecp_gallery_kate SET katename = \'%s\', access = \'%s\', beschreibung = \'%s\' WHERE kateID = %d', strsave($_POST['katename']), strsave(admin_make_rights($_POST['access'])), strsave($lang), $id);
			if($db->query($sql)) {
				echo 'ok';
			}
		}
	}	
	die();
}
function admin_gallery_add() {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['gallery']['add']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($_POST['name'] == '' OR !$_POST['folder'] OR !$_POST['cID']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {		
			$sql =sprintf('INSERT INTO '.DB_PRE.'ecp_gallery (`name`, `userID`, `folder`, `cID`, `datum`, `access`) VALUES (\'%s\', %d, \'%s\', %d, %d, \'%s\')', strsave($_POST['name']), $_SESSION['userID'], strsave($_POST['folder']), (int)$_POST['cID'], time(), strsave(admin_make_rights($_POST['access'])));
			if($db->query($sql)) {
				echo 'ok';
				$db->query('UPDATE '.DB_PRE.'ecp_gallery_kate SET galleries = galleries +1 WHERE kateID ='.(int)$_POST['cID']);
			}
		}
	}	
	die();
}
function admin_gallery_edit($id) {
	global $db;
	$db->setMode(0);
	ob_end_clean();
	ajax_convert_array($_POST);
	if(!isset($_SESSION['rights']['admin']['gallery']['edit']) AND !isset($_SESSION['rights']['superadmin'])) {
		table(ERROR, NO_ADMIN_RIGHTS);
	} else {
		if($_POST['name'] == '' OR !$_POST['folder'] OR !$_POST['cID']) {
			echo NOT_NEED_ALL_INPUTS;
		} else {
			$old = $db->result(DB_PRE.'ecp_gallery', 'cID', 'galleryID = '.$id);
			$sql =sprintf('UPDATE '.DB_PRE.'ecp_gallery SET `name` = \'%s\', `folder` = \'%s\', `cID` = %d, `access` = \'%s\' WHERE galleryID = %d', strsave($_POST['name']), strsave($_POST['folder']), (int)$_POST['cID'], strsave(admin_make_rights($_POST['access'])), $id);
			if($db->query($sql)) {
				echo 'ok';
				if($_POST['cID'] != $old) {
					$db->query('UPDATE '.DB_PRE.'ecp_gallery_kate SET galleries = galleries +1 WHERE kateID ='.(int)$_POST['cID']);
					$db->query('UPDATE '.DB_PRE.'ecp_gallery_kate SET galleries = galleries -1 WHERE kateID ='.$old);
				}
			}
		}
	}	
	die();
}
function get_dirs($dir="") {
	global  $db;
    $result = $db->query('SELECT folder FROM '.DB_PRE.'ecp_gallery');
    while($row = mysql_fetch_array($result)) {
        $verzeichnisse[] = $row[0];
    }
    $dirs = scan_dir("images/gallery/",1);
    foreach($dirs AS $var1) {
        IF(!@in_array($var1,@$verzeichnisse) OR $var1 == $dir) {
            IF($var1 == $dir) $option='selected="selected"'; else $option='';
            @$dirs .= '<option '.$option.' value="'.$var1.'">'.$var1.'</option>';
        }
    }
    return @$dirs;
}
function admin_gallery_view($id) {
	global $db;
	$gallery = $db->fetch_assoc('SELECT name, folder, images FROM '.DB_PRE.'ecp_gallery WHERE galleryID = '.$id);
	if(isset($gallery['name'])) {
		if($gallery['images']) {
			$limits = get_sql_limit($gallery['images'], LIMIT_GALLERY_PICS);
			$db->query('SELECT * FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$id.' ORDER BY imageID ASC LIMIT '.$limits[1].','.LIMIT_GALLERY_PICS);
			$pics = array();
			while($row = $db->fetch_assoc()) {
				$row['uploaded'] = date(SHORT_DATE, $row['uploaded']);
				$pics[] = $row;	
			}			
			$tpl = new smarty();
			$tpl->assign('pics', $pics);
			$tpl->assign('seiten', makepagelink('?section=admin&site=gallery&func=viewgallery&id='.$id, (isset($_GET['page']) ? $_GET['page'] : 1), $limits[0]));			
			$tpl->assign('folder', $gallery['folder']);
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/gallery_view_overview.html');
			$content = ob_get_contents();
			ob_end_clean();
		}
		$tpl = new smarty;
		$tpl->assign('sid', session_name().'='.session_id());
		$tpl->assign('pics', @$content);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/gallery_view'.(UPLOAD_METHOD == 'old' ? '_old' : '').'.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(GALLERY, $content, '',1);
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function ordner_einlesen($id) {
	global $db;
    $verzeichnis = $db->result(DB_PRE.'ecp_gallery', 'folder', 'galleryID = '.$id);
    $files = scan_dir('images/gallery/'.$verzeichnis, true);
    $result = $db->query('SELECT imageID, filename FROM '.DB_PRE.'ecp_gallery_images WHERE gID = '.$id);
    $bilder = array();
    while($row = $db->fetch_assoc()) {
        $bilder[$row['imageID'].'_'.$row['filename']] = 0;
    }
    print_r($files);print_r($bilder);
    IF(!file_exists('images/gallery/'.$verzeichnis.'/thumbs')) { umask(0); mkdir('images/gallery/'.$verzeichnis.'/thumbs',0777); }
    foreach($files AS $name) {
        IF(strpos($name,'.')) {
            IF(array_key_exists($name, $bilder)) {
                $bilder[$name] = 1;
            } else {
                $size = getimagesize('images/gallery/'.$verzeichnis.'/'.$name);
				$db->query('INSERT INTO '.DB_PRE.'ecp_gallery_images (`gID`, `filename`, `uploaded`, `userID`) VALUES ('.$id.', \''.strsave($name).'\', '.time().', '.(int)$_SESSION['userID'].')');
				$pid = $db->last_id();
				$db->query('UPDATE '.DB_PRE.'ecp_gallery SET images = images + 1 WHERE galleryID= '.$id);		                
				if($size[0] > GALLERY_THUMB_SIZE) {
					resize_picture('images/gallery/'.$verzeichnis.'/'.$name, GALLERY_THUMB_SIZE, 'images/gallery/'.$verzeichnis.'/thumbs/'.$pid.'_'.$name, 100);
				} else {
					copy('images/gallery/'.$verzeichnis.'/'.$name, 'images/gallery/'.$verzeichnis.'/thumbs/'.$pid.'_'.$name);
					umask(0);
					chmod('images/gallery/'.$verzeichnis.'/thumbs/'.$pid.'_'.$name, CHMOD);
				}
				if($size[0] > GALLERY_PIC_SIZE) {
					resize_picture('images/gallery/'.$verzeichnis.'/'.$name, GALLERY_PIC_SIZE, 'images/gallery/'.$verzeichnis.'/'.$pid.'_'.$name, 100);
					unlink('images/gallery/'.$verzeichnis.'/'.$name);
				} else {
					rename('images/gallery/'.$verzeichnis.'/'.$name, 'images/gallery/'.$verzeichnis.'/'.$pid.'_'.$name);
				}
                $bilder[$name] = 1;
            }
        }
    }
    foreach($bilder AS $key=>$value) {
       IF($value == 0) {
           IF(file_exists('images/gallery/'.$verzeichnis.'/thumbs/'.$key)) unlink('images/gallery/'.$verzeichnis.'/thumbs/'.$key);
           $key = substr($key, strpos($key, '_')+1);
           $pid = $db->result(DB_PRE.'ecp_gallery_images', 'imageID', 'gID = '.$id.' AND filename = \''.strsave($key).'\'');
           $db->query('DELETE FROM '.DB_PRE.'ecp_gallery_images WHERE imageID='.$pid);
           $db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = "gallery" AND subID = '.$pid);
           $db->query('UPDATE '.DB_PRE.'ecp_gallery SET images = images - 1 WHERE galleryID='.$id);
       }
    }
    header1('?section=admin&site=gallery&func=viewgallery&id='.$id);
}
if (!isset($_SESSION['rights']['admin']['gallery']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'add':
				admin_gallery_add();
			break;	
			case 'edit':
				admin_gallery_edit((int)$_GET['id']);
			break;								
			case 'addkate':
				admin_gallery_add_kate();
			break;	
			case 'editkate':
				admin_gallery_edit_kate((int)$_GET['id']);
			break;	
			case 'einlesen':
				ordner_einlesen((int)$_GET['id']);
			break;				
			case 'viewgallery':
				admin_gallery_view((int)$_GET['id']);
			break;															
			default:
				admin_gallery();
		}
	} else {
		admin_gallery();
	}
}
?>