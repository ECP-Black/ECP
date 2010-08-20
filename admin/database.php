<?php
function admin_database() {
	global $db;
	if(@$_SESSION['rights']['admin']['database']['backup'] OR @$_SESSION['rights']['superadmin']) {	
		if(isset($_POST['submit'])) {
			if(isset($_POST['backup_aktiv'])) {
				if(!check_email($_POST['backup_email'])) {
					table(ERROR, WRONG_EMAIL);	
				    $tpl = new smarty;
					ob_start();
					$tpl->display(DESIGN.'/tpl/admin/database_backup.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(DATABASE_BACKUP, $content, '',1);   				
				} else {
					switch($_POST['backup_cycle']) {
						case 'day':
							$cycle = 'day';
						break;
						case 'week':
							$cycle = 'week';
						break;
						case 'month':
							$cycle = 'month';
						break;
						default:
							$cycle = 'week';
					}
					if($db->query('UPDATE '.DB_PRE.'ecp_settings SET BACKUP_AKTIV = 1, BACKUP_EMAIL = \''.strsave($_POST['backup_email']).'\', BACKUP_CYCLE = \''.$cycle.'\'')) {
						header1('?section=admin&site=database');
					}				
				}
			} else {
					switch($_POST['backup_cycle']) {
						case 'day':
							$cycle = 'day';
						break;
						case 'week':
							$cycle = 'week';
						break;
						case 'month':
							$cycle = 'month';
						break;
						default:
							$cycle = 'week';
					}			
				if($db->query('UPDATE '.DB_PRE.'ecp_settings SET BACKUP_AKTIV = 0, BACKUP_EMAIL = \''.strsave($_POST['backup_email']).'\', BACKUP_CYCLE = \''.$cycle.'\'')) {
					header1('?section=admin&site=database');
				}
			}
		} else {
		    $tpl = new smarty;
			ob_start();
			$tpl->display(DESIGN.'/tpl/admin/database_backup.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(DATABASE_BACKUP, $content, '',1);    	
		}
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_database_make() {
	if(@$_SESSION['rights']['admin']['database']['backup'] OR @$_SESSION['rights']['superadmin']) {	
		ob_end_clean();
		$backup_obj = new MySQL_Backup();
		$backup_obj->server = MYSQL_HOST;
		$backup_obj->username = MYSQL_USER;
		$backup_obj->password = MYSQL_PASS;
		$backup_obj->database = MYSQL_DATABASE;
		$backup_obj->tables = array();
		$backup_obj->drop_tables = true;
		$backup_obj->struct_only = false;
		$backup_obj->comments = true;
		$backup_obj->fname_format = 'd_m_y__H_i_s';
		if (!$backup_obj->Execute(MSB_DOWNLOAD, '', false))
		{
		  die($backup_obj->error);
		}	
		die();
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
function admin_database_optimize() {
	if(@$_SESSION['rights']['admin']['database']['optimize'] OR @$_SESSION['rights']['superadmin']) {	
		global $db;
	    $result = $db->query("SHOW TABLE STATUS");
	    $gesamt = 0;
	    $database = array();
	    while ($row = mysql_fetch_assoc($result)) {
	        @$free = $row['Data_free'];
	        $gesamt += $free;
	        @$groesse += $row['Data_length'];
	        $sql = "OPTIMIZE TABLE ".$row['Name'];
	        $row['Data_length'] = goodsize($row['Data_length']);
	        $row['free'] = goodsize($free);
	        $database[] = $row;
	       	$db->query($sql);
	    }
	    $db->query('UPDATE '.DB_PRE.'ecp_stats SET sqlfree = sqlfree + '.$gesamt);
	    $tpl = new smarty;
	    $tpl->assign('dbsize', str_replace(array('{db_size}', '{free}'), array(goodsize($groesse), goodsize($gesamt)), DATABASE_INFO));
	    $tpl->assign('new_free', str_replace('{free}', goodsize($db->result(DB_PRE.'ecp_stats', 'sqlfree', '1')),DATABASE_INFO2));
	    $tpl->assign('database', $database);
		ob_start();
		$tpl->display(DESIGN.'/tpl/admin/database_optimize.html');
		$content = ob_get_contents();
		ob_end_clean();
		main_content(DATABASE_OPTIMIZE, $content, '',1);      
	} else {
		table(ERROR, NO_ADMIN_RIGHTS);
	}
}
if (!isset($_SESSION['rights']['admin']['database']) AND !isset($_SESSION['rights']['superadmin'])) {
	table(ERROR, NO_ADMIN_RIGHTS);
} else {
	if(isset($_GET['func'])) {
		switch($_GET['func']) {
			case 'optimize':
				admin_database_optimize();
				break;
			case 'makebackup':
				admin_database_make();
				break;																
			default:
				admin_database();
		}
	} else {
		admin_database();
	}
}
?>