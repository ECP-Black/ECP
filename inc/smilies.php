<?php
	require('db.daten.php');
	require('classes.php');
	$db = new db();
	$db->query('SELECT ID, bedeutung, filename FROM '.DB_PRE.'ecp_smilies');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#emotions_dlg.title}</title>
	<script language="javascript" type="text/javascript" src="javascript/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="javascript/tinymce/plugins/emotions/js/emotions.js"></script>
	<base target="_self" />
</head>
<body style="display: none">
	<div align="center">
		<table border="0" cellspacing="0" cellpadding="4">
		  	<?php
		  	while($row = $db->fetch_assoc()) {
		  		echo '<tr><td><a href="javascript:EmotionsDialog.insert(\''.$row['filename'].'\',\''.$row['bedeutung'].'\');"><img src="../images/smilies/'.$row['filename'].'" border="0" alt="'.$row['bedeutung'].'" title="'.$row['bedeutung'].'" /></a></td>';
		  		$row = $db->fetch_assoc();
		  		if($row['filename'])
		  			echo '<td><a href="javascript:EmotionsDialog.insert(\''.$row['filename'].'\',\''.$row['bedeutung'].'\');"><img src="../images/smilies/'.$row['filename'].'" border="0" alt="'.$row['bedeutung'].'" title="'.$row['bedeutung'].'" /></a></td>';
		  		else 
		  			echo '<td></td>';
		  		$row = $db->fetch_assoc();
		  		if($row['filename'])
		  			echo '<td><a href="javascript:EmotionsDialog.insert(\''.$row['filename'].'\',\''.$row['bedeutung'].'\');"><img src="../images/smilies/'.$row['filename'].'" border="0" alt="'.$row['bedeutung'].'" title="'.$row['bedeutung'].'" /></a></td>';
		  		else 
		  			echo '<td></td>';
		  		$row = $db->fetch_assoc();
		  		if($row['filename'])
		  			echo '<td><a href="javascript:EmotionsDialog.insert(\''.$row['filename'].'\',\''.$row['bedeutung'].'\');"><img src="../images/smilies/'.$row['filename'].'" border="0" alt="'.$row['bedeutung'].'" title="'.$row['bedeutung'].'" /></a></td>';
		  		else 
		  			echo '<td></td>';		  					  			
		  		echo '</tr>';
		  	}		  	
		  	?>			
		  </tr>
		</table>
	</div>
</body>
</html>
