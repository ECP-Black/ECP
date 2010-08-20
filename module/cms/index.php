<?php
		global $db;
		if(isset($_GET['id'])) {
			$row = $db->fetch_assoc('SELECT headline, content FROM '.DB_PRE.'ecp_cms WHERE cmsID = '.(int)$_GET['id'].' AND (access = "" OR '.$_SESSION['access_search'].')');
			if(isset($row['headline'])) {  
				$row['headline'] = json_decode($row['headline'], true);
				if(isset($row['headline'][LANGUAGE]) AND $row['headline'][LANGUAGE] != '') $row['headline'] = $row['headline'][LANGUAGE]; else $row['headline'] = $row['headline'][DEFAULT_LANG];      
				$row['content'] = json_decode($row['content'], true);
				if(isset($row['content'][LANGUAGE]) AND $row['content'][LANGUAGE] != '') $row['content'] = $row['content'][LANGUAGE]; else $row['content'] = $row['content'][DEFAULT_LANG]; 				
				if(!isset($_SESSION['cms'][(int)$_GET['id']])) {
					if($db->query('UPDATE '.DB_PRE.'ecp_cms SET views = views + 1 WHERE cmsID = '.(int)$_GET['id'])) {
						$_SESSION['cms'][(int)$_GET['id']] = true;
					}
				}
				main_content($row['headline'], $row['content'], '', 1);	
			} else {
				table(ERROR, ACCESS_DENIED);
			}
		} else {
			
		}
?>