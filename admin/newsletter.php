<?php
function admin_newsletter() {
	global $db;
	if(isset($_POST['submit'])) {
		if(count(@$_POST['user']) == 0) {
			table(ERROR, NEWSLETTER_ONE_USER);
	    	$tpl = new smarty;
		    $tpl->assign('options', get_user_newsletter());
		    $tpl->assign('titel', $_POST['titel']);
		    $tpl->assign('art', $_POST['art']);
		    ob_start();
		    $tpl->display(DESIGN.'/tpl/admin/rundmail.html');
		    $content = ob_get_contents();
		    ob_end_clean();
		    main_content(NEWSLETTER, $content, '', 1);				
		} elseif ($_POST['message'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
	    	$tpl = new smarty;
		    $tpl->assign('options', get_user_newsletter());
		    $tpl->assign('titel', $_POST['titel']);
		    $tpl->assign('art', $_POST['art']);
	    	ob_start();
		    $tpl->display(DESIGN.'/tpl/admin/rundmail.html');
		    $content = ob_get_contents();
		    ob_end_clean();
		    main_content(NEWSLETTER, $content, '', 1);				
		} elseif ($_POST['titel'] == '') {
			table(ERROR, NOT_NEED_ALL_INPUTS);
	    	$tpl = new smarty;
		    $tpl->assign('options', get_user_newsletter());
		    $tpl->assign('titel', $_POST['titel']);
		    $tpl->assign('art', $_POST['art']);
		    ob_start();
		    $tpl->display(DESIGN.'/tpl/admin/rundmail.html');
		    $content = ob_get_contents();
		    ob_end_clean();
		    main_content(NEWSLETTER, $content, '', 1);				
		} else {
			$user = array();
			if(in_array('all_users',$_POST['user'])) {
				$result = $db->query('SELECT username, ID, email FROM '.DB_PRE.'ecp_user');
				while($row = mysql_fetch_assoc($result)) {
				    $user[$row['ID']]['username'] = $row['username'];
				    $user[$row['ID']]['email'] = $row['email'];
				}
			} else {
				foreach($_POST['user'] AS $value) {
					if(strlen((int)$value) == strlen($value)) {
						$row = $db->fetch_assoc('SELECT username, email FROM '.DB_PRE.'ecp_user WHERE ID = '.$value);
						$user[$value]['username'] = $row['username'];
						$user[$value]['email'] = $row['email'];
					} elseif (strpos($value,'team_') === 0) {
						$subresult = $db->query('SELECT '.DB_PRE.'ecp_members.userID, username, email FROM '.DB_PRE.'ecp_members LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_members.userID = '.DB_PRE.'ecp_user.ID) WHERE teamID = '.(int)substr($value,strpos($value, '_')+1));
						while($row = mysql_fetch_assoc($subresult)) {
						    $user[$row['userID']]['username'] = $row['username'];
						    $user[$row['userID']]['email'] = $row['email'];
						}
					} elseif (strpos($value,'group_')  === 0) {
						$subresult = $db->query('SELECT '.DB_PRE.'ecp_user_groups.userID, username, email FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_user_groups.userID = '.DB_PRE.'ecp_user.ID) WHERE gID = '.(int)substr($value,strpos($value, '_')+1));
						while($row = mysql_fetch_assoc($subresult)) {
						    $user[$row['userID']]['username'] = $row['username'];
						    $user[$row['userID']]['email'] = $row['email'];
						}
					}
				}
			}
			$i = 0;
			$fehler = '';
			$sender = '';
		    switch($_POST['art']) {
		    	case 'message': 
		    		foreach($user AS $key=>$value) {
		    	       IF(message_send($key, $_SESSION['userID'], $_POST['titel'], strsave(str_replace(array('{username}',"\r\n"),array($value['username'], '<br />'),$_POST['message'])),0,1)) {
		    	       	    $i++;
		    	       	    $sender .= '<a href="?section=user&id='.$key.'" target="_blank">'.$value['username'].'</a>, ';
		    	       }		    	       
		    		}
		    	break;
		    	case 'mail':
		    		foreach($user AS $key=>$value) {
		    	       IF(send_email($value['email'], $_POST['titel'], str_replace('{username}',$value['username'],$_POST['message']), 0)) {
		    	       	    $i++;
		    	       	    $sender .= '<a href="?section=user&id='.$key.'" target="_blank">'.$value['username'].'</a>, ';
		    	       } else {
		    	           $fehler .= str_replace('{username}', $value['username'], NEWSLETTER_NOT_SEND);
		    	       }		    	       
		    		}		    		
		    	break;
		    	case 'both':
		    		foreach($user AS $key=>$value) {
		    	       IF(send_email($value['email'], $_POST['titel'], str_replace('{username}',$value['username'],$_POST['message']))) {
		    	           IF(message_send($key, $_SESSION['userID'], $_POST['titel'], strsave(str_replace(array('{username}',"\r\n"),array($value['username'], '<br />'),$_POST['message'])),0,1)) {
		    	       	       $i++;
		    	       	       $sender .= '<a href="?section=user&id='.$key.'" target="_blank">'.$value['username'].'</a>, ';
		    	           } else {
		    	               $fehler .= str_replace('{username}', $value['username'], NEWSLETTER_NOT_SEND);
		    	           }
		    	       } else {
		    	           $fehler .= str_replace('{username}', $value['username'], NEWSLETTER_NOT_SEND);
		    	       }		    	       
		    		}			    	
		        break;
		    }
		    IF(strlen($fehler)) {
		    	table(ERROR, $fehler);
		    } else {
		    	table(INFO, str_replace('{anzahl}', $i, NEWSLETTER_SUCCESS).'<br />'.NEWSLETTER_RECEIVER.rtrim($sender, ', '));
		    }
		}
		
	} else {
	    $tpl = new smarty;
	    $tpl->assign('options', get_user_newsletter());
	    ob_start();
	    $tpl->display(DESIGN.'/tpl/admin/rundmail.html');
	    $content = ob_get_contents();
	    ob_end_clean();
	    main_content(NEWSLETTER, $content, '', 1);
	}
}
function get_user_newsletter() {
	global $db, $groups;
	$result = $db->query('SELECT tID, tname FROM '.DB_PRE.'ecp_teams ORDER BY posi ASC');
	$players = "";
	while($row1 = mysql_fetch_assoc($result)) {
		$subresult = $db->query('SELECT '.DB_PRE.'ecp_members.userID, username FROM '.DB_PRE.'ecp_members LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_members.userID = '.DB_PRE.'ecp_user.ID) WHERE teamID = '.$row1['tID'].' ORDER BY username ASC');
		$players .= '<option>---------------------------------------------------------------</option>';
		$players .= '<option value="team_'.$row1['tID'].'">'.HOLE_TEAM.': '.$row1['tname'].'</option>';
		$players .= '<option>---------------------------------------------------------------</option>';
		while($subrow = mysql_fetch_assoc($subresult)) {
			IF(@in_array($subrow['userID'], @$_POST['user']) OR @in_array('team_'.$row1['tID'],$_POST['user']) OR @in_array('all_users', $_POST['user'])) $sub = 'selected'; else $sub = '';
			$players .= '<option '.$sub.' value="'.$subrow['userID'].'">-'.$subrow['username'].'</option>';
		}
	}
	$result = $db->query('SELECT groupID,name FROM '.DB_PRE.'ecp_groups WHERE groupID != 4 ORDER BY name ASC ');
	while($row1 = mysql_fetch_assoc($result)) {
		if(key_exists($row1['name'], $groups)) $row1['name'] = $groups[$row1['name']];
		$subresult = $db->query('SELECT '.DB_PRE.'ecp_user_groups.userID, username FROM '.DB_PRE.'ecp_user_groups LEFT JOIN '.DB_PRE.'ecp_user ON ('.DB_PRE.'ecp_user_groups.userID = '.DB_PRE.'ecp_user.ID) WHERE gID = '.$row1['groupID'].' ORDER BY username ASC');
		$players .= '<option>---------------------------------------------------------------</option>';
		$players .= '<option value="group_'.$row1['groupID'].'">'.HOLE_GROUP.': '.$row1['name'].'</option>';
		$players .= '<option>---------------------------------------------------------------</option>';
		while($subrow = mysql_fetch_assoc($subresult)) {
			IF(@in_array($subrow['userID'], @$_POST['user']) OR @in_array('group_'.$row1['groupID'],$_POST['user']) OR @in_array('all_users', $_POST['user'])) $sub = 'selected'; else $sub = '';
			$players .= '<option '.$sub.' value="'.$subrow['userID'].'">-'.$subrow['username'].'</option>';
		}
	}	
	return $players;
}
if (!isset($_SESSION['rights']['admin']['newsletter']['send']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	admin_newsletter();
}

?>