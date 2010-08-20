<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#ecp.flag_desc}</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
    <script type="text/javascript">
	function insert_flagge(file, title) {
		var ed = tinyMCEPopup.editor, dom = ed.dom;
		title = title.replace(/&/g, '&amp;');
		title = title.replace(/\"/g, '&quot;');
		title = title.replace(/</g, '&lt;');
		title = title.replace(/>/g, '&gt;');
		tinyMCEPopup.execCommand('mceInsertContent', false, dom.createHTML('img', {
			src : file,
			alt : title,
			title : title,
			border : 0
		}));
		tinyMCEPopup.close();
	}
	</script>
	<base target="_self" />
</head>
<body style="display: none; background-color: #ccc; ">
<div style="text-align: center;">
<?php
include('../../../../language/de.php');
include('../../../../functions.php');
$files = scan_dir('../../../../../images/flaggen', false);
$i = 0;
foreach($files AS $key) {
	if($i == 25) {
		echo '<br />';
		$i = 0;
	}
	echo '<img src="../../../../../images/flaggen/'.$key.'" alt="" title="'.$countries[substr($key,0, strpos($key, '.'))].'" style="cursor:pointer" onclick="insert_flagge(\'images/flaggen/'.$key.'\', \''.$countries[substr($key,0, strpos($key, '.'))].'\');" /> ';
	$i++;
}
?>
</div>
</body>
</html>