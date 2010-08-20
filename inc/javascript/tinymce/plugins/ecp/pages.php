<?php
session_start();
include_once('../../../../../inc/db.daten.php');
include_once('../../../../../inc/classes.php');
include_once('../../../../../inc/constant.php');	
include('../../../../../inc/language/'.LANGUAGE.'.php');	
include('../../../../../admin/lang/'.LANGUAGE.'.php');
include_once('../../../../../inc/functions.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#ecp.pages_desc}</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
    <script type="text/javascript">
	function insert_page(page, title) {
		page = document.getElementById('cms').options[document.getElementById('cms').selectedIndex].value;
		title = document.getElementById('text').value;
		if(title == '') title = document.getElementById('cms').options[document.getElementById('cms').selectedIndex].text;
		var ed = tinyMCEPopup.editor, dom = ed.dom;
		title = title.replace(/&/g, '&amp;');
		title = title.replace(/\"/g, '&quot;');
		title = title.replace(/</g, '&lt;');
		title = title.replace(/>/g, '&gt;');
		tinyMCEPopup.execCommand('mceInsertContent', false, '<a href="?section=cms&id='+page+'">'+title+'</a>');
		tinyMCEPopup.close();
	}
	</script>
	<base target="_self" />
</head>
<body style="display: none;">
    <form onsubmit="insert_page();return false;" id="user_form" action="#">
		<?php echo LINK_NAME; ?>: <input type="text" size="40" id="text" /><br />
		<?php echo OWN_SITES; ?>: <select id="cms" name="cms"><?php 
		global $db;
		$db->setMode(0);
		$db->query('SELECT cmsID, headline FROM '.DB_PRE.'ecp_cms ORDER BY headline ASC');
		while($row = $db->fetch_assoc()) {
			$row['headline'] = json_decode($row['headline'], true);
			if(isset($row['headline'][LANGUAGE]) AND $row['headline'][LANGUAGE] != '') $row['headline'] = $row['headline'][LANGUAGE]; else $row['headline'] = $row['headline'][DEFAULT_LANG];      			
			echo '<option value="'.$row['cmsID'].'">'.htmlspecialchars($row['headline']).'</option>';
		}
		?></select>
    	<br />    
		<div class="mceActionPanel">
			<div style="float: left">
				<input type="submit" id="insert" name="insert" value="{#insert}" />
			</div>
			<div style="float: right">
				<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
			</div>
		</div>
    </form>
</div>
</body>
</html>