<?php
session_start();
include('inc/include.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#ecp.user_desc}</title>
	<script type="text/javascript">
	<?php echo 'DESIGN = "'.DESIGN.'";'; ?>
	</script>
	<link href="templates/Standard/images/javascripts/autocompleter/autocompleter.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="inc/javascript/tinymce/tiny_mce_popup.js"></script>
	<script type="text/javascript" src="inc/javascript/lang/<?php echo LANGUAGE ?>.js"></script>
    <script type="text/javascript" src="inc/javascript/mootools.php"></script>
    <script type="text/javascript">
	function insert_user() {
		new Request({
			url: 'ajax_checks.php?func=make_user_tiny', 	
			method: 'post', 
			data: $('user_form').toQueryString(), 
			onRequest: function() {
				$('insert').disabled = true;
				$('user_waiter').style.visibility = '';
			},
			onSuccess: function(r) {
				$('insert').disabled = false;
				$('user_waiter').style.visibility = 'hidden';				
				if(r != '') {
					tinyMCE.activeEditor.execCommand('mceInsertContent', false, r);	
				}
				tinyMCEPopup.close();
			}
		}).post();			
	}
	window.addEvent('load', function(e) {
		new Autocompleter.Ajax.Json($('username'), 'ajax_checks.php?func=search_member', { postVar: 'username', onRequest: function() { $('user_waiter').style.visibility = '';}, onComplete: function() { $('user_waiter').style.visibility = 'hidden'}, multi: true, zIndex: 999999 });
	});
	</script>
	<base target="_self" />
</head>
<body style="display: none">
    <form onsubmit="insert_user();return false;" id="user_form" action="#">
    	<input id="username" name="username" style="width:80%" class="mceFocus" /> <img src="templates/<?php echo DESIGN; ?>/images/spinner.gif" id="user_waiter" style="visibility: hidden" />
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
     <!-- www.easy-clanpage.de -->
</body>
</html>
