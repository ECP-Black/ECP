<?php
// ---------> Funktionen ohne Rückgabewert <------------ //
function show_content() {	
	global $db;
	if(isset($_GET['section'])) {
		preg_match('/\w+/', $_GET['section'], $erg);
		if(file_exists('module/'.$erg[0].'/index.php')) {
			include('module/'.$erg[0].'/index.php');
		} elseif ($_GET['section'] == 'admin') {
			include('admin/index.php');
		} else {
			table(ERROR, SITE_NOT_EXSISTS);
		}
	} else {
		$start = explode('|', STARTSEITE);
		if($start[0] == 'modul') {
			if(file_exists('module/'.$start[1].'/index.php')) {
				include('module/'.$start[1].'/index.php');
			} else {
				include('module/news/index.php');
			}
		} else {
			header1('?section=cms&id='.$start[1]);
		}
	}
}
$leftmenu = '';
$rightmenu ='';
function make_menus() {
	global $db, $leftmenu, $rightmenu;
	$db->query('SELECT suche, ersetze FROM '.DB_PRE.'ecp_menu_links WHERE sprache = \''.LANGUAGE.'\'');
	$search = array();
	$replace = array();
	while($row = $db->fetch_assoc()) {
		$search[] = $row['suche'];
		$replace[] = $row['ersetze'];
	}
	$result = $db->query('SELECT `menuID`, `name`, `headline`, `modul`, `inhalt`, `hposi`, `vposi`, `usetpl`, `design` FROM '.DB_PRE.'ecp_menu WHERE design = \''.DESIGN.'\' AND (access = "" OR '.$_SESSION['access_search'].') AND (lang = "" OR lang LIKE \'%'.LANGUAGE.'%\') ORDER BY vposi ASC');
	while($row= mysql_fetch_assoc($result)) {
		$content = '';
		$row['inhalt'] = str_replace($search, $replace, $row['inhalt']);
		if($row['modul'] != '') {
			ob_start();
			if(file_exists('inc/module/'.$row['modul'])) {
				include('inc/module/'.$row['modul']);
				$content = ob_get_contents();
				ob_end_clean();				
			}

		} elseif ($row['usetpl']) {
			$links = explode('<br />', $row['inhalt']);
			$tpl = new smarty;
			$tpl->assign('links', $links);
			ob_start();
			$tpl->display(DESIGN.'/tpl/menu_links.html');
			$content = ob_get_contents();
			ob_end_clean();
		} else {
			$content = $row['inhalt'];
		}
		if($content) {
			ob_start();
			menu($row['headline'], $content);
			if($row['hposi'] == 'l') {
				$leftmenu .= ob_get_contents();
			} else {
				$rightmenu .= ob_get_contents();
			}
			ob_end_clean();
		}
	}
	
}
function footer() {
	global $db, $startzeit;
	$endzeit = explode(' ',substr(microtime(),1));
	$endzeit = $endzeit[1]+$endzeit[0];
	$laufzeit = $endzeit-$startzeit;
	echo '<center>© 2005-'.date('Y').' <a href="http://www.easy-clanpage.de" target="_blank">ECP '.VERSION.'</a> :: '.SQL_QUERIES.': '.$db->number_querys().' :: '.PHP_RUN_TIME.': '.number_format($laufzeit,3,'.','').' Sec.</center>';
}
function javascripts() {
	echo '
<script type="text/javascript">
   DESIGN = \''.DESIGN.'\';
   LANG = \''.LANGUAGE.'\';
</script>	
<script src="inc/javascript/lang/'.LANGUAGE.'.js?v30" type="text/javascript"></script>
<script src="inc/javascript/mootools.php?v30" type="text/javascript"></script>
<script src="inc/javascript/functions.js?v30" type="text/javascript"></script>
<script src="inc/javascript/admin_functions.js?v30" type="text/javascript"></script>
<script src="inc/javascript/tinymce/tiny_mce_gzip.js" type="text/javascript"></script>
<script type="text/javascript" src="inc/javascript/calender/calendar.js"></script>
<script type="text/javascript" src="inc/javascript/calender/lang/calendar-'.LANGUAGE.'.js"></script>
<script type="text/javascript" src="inc/javascript/calender/calendar-setup.js"></script>
<script type="text/javascript">
tinyMCE_GZ.init({
	plugins : \'ecp,inlinepopups,style,layer,table,save,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras\',
	themes : \'advanced\',
	languages : \''.((LANGUAGE == 'gb' OR LANGUAGE == 'us') ? 'en' : LANGUAGE).'\',
	disk_cache : true,
	debug : false
});
</script>
<script type="text/javascript">
	';
	if(isset($_SESSION['rights']['admin']) OR isset($_SESSION['rights']['superadmin'])) echo'
	tinyMCE.init({
		theme : "advanced",
		skin: "o2k7",
		editor_selector : "admininput",
		mode : "textareas",
		language: "'.((LANGUAGE == 'gb' OR LANGUAGE == 'us') ? 'en' : LANGUAGE).'",
		plugins : "ecp,inlinepopups,style,layer,table,save,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "copy,paste,cut,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor",
		theme_advanced_buttons3 : "tablecontrols,|,cleanup,code",
		theme_advanced_buttons4 : "hr,removeformat,visualaid,|,sub,sup,|,image,charmap,emotions,iespell,media,|,print,|,fullscreen",
		theme_advanced_buttons5 : "insertlayer,moveforward,movebackward,absolute,attribs,|,styleprops,|,visualchars,nonbreaking,|,forecolor,backcolor,|,ecpphp,ecpquote,ecpflags,ecpuser,ecppages",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_path_location : "bottom",
		plugin_insertdate_dateFormat : "%Y-%m-%d",
		plugin_insertdate_timeFormat : "%H:%M:%S",
		valid_elements : ""
		+"a[accesskey|charset|class|coords|dir<ltr?rtl|href|hreflang|id|lang|name"
		  +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|rel|rev"
		  +"|shape<circle?default?poly?rect|style|tabindex|title|target|type],"
		+"abbr[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"acronym[class|dir<ltr?rtl|id|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"address[class|align|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"applet[align<bottom?left?middle?right?top|alt|archive|class|code|codebase"
		  +"|height|hspace|id|name|object|style|title|vspace|width],"
		+"area[accesskey|alt|class|coords|dir<ltr?rtl|href|id|lang|nohref<nohref"
		  +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup"
		  +"|shape<circle?default?poly?rect|style|tabindex|title|target],"
		+"base[href|target],"
		+"basefont[color|face|id|size],"
		+"bdo[class|dir<ltr?rtl|id|lang|style|title],"
		+"big[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"blockquote[cite|class|dir<ltr?rtl|id|lang|onclick|ondblclick"
		  +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
		  +"|onmouseover|onmouseup|style|title],"
		+"body[alink|background|bgcolor|class|dir<ltr?rtl|id|lang|link|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onload|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|onunload|style|title|text|vlink],"
		+"br[class|clear<all?left?none?right|id|style|title],"
		+"button[accesskey|class|dir<ltr?rtl|disabled<disabled|id|lang|name|onblur"
		  +"|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|style|tabindex|title|type"
		  +"|value],"
		+"caption[align<bottom?left?right?top|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"center[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"cite[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"code[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"col[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
		  +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|span|style|title"
		  +"|valign<baseline?bottom?middle?top|width],"
		+"colgroup[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl"
		  +"|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|span|style|title"
		  +"|valign<baseline?bottom?middle?top|width],"
		+"dd[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
		+"del[cite|class|datetime|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"dfn[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"dir[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"div[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"dl[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"dt[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
		+"em/i[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"fieldset[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"font[class|color|dir<ltr?rtl|face|id|lang|size|style|title],"
		+"form[accept|accept-charset|action|class|dir<ltr?rtl|enctype|id|lang"
		  +"|method<get?post|name|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onreset|onsubmit"
		  +"|style|title|target],"
		+"frame[class|frameborder|id|longdesc|marginheight|marginwidth|name"
		  +"|noresize<noresize|scrolling<auto?no?yes|src|style|title],"
		+"frameset[class|cols|id|onload|onunload|rows|style|title],"
		+"h1[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"h2[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"h3[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"h4[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"h5[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"h6[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"head[dir<ltr?rtl|lang|profile],"
		+"hr[align<center?left?right|class|dir<ltr?rtl|id|lang|noshade<noshade|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|size|style|title|width],"
		+"html[dir<ltr?rtl|lang|version],"
		+"iframe[align<bottom?left?middle?right?top|class|frameborder|height|id"
		  +"|longdesc|marginheight|marginwidth|name|scrolling<auto?no?yes|src|style"
		  +"|title|width],"
		+"img[align<bottom?left?middle?right?top|alt|border|class|dir<ltr?rtl|height"
		  +"|hspace|id|ismap<ismap|lang|longdesc|name|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|src|style|title|usemap|vspace|width],"
		+"input[accept|accesskey|align<bottom?left?middle?right?top|alt"
		  +"|checked<checked|class|dir<ltr?rtl|disabled<disabled|id|ismap<ismap|lang"
		  +"|maxlength|name|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onselect"
		  +"|readonly<readonly|size|src|style|tabindex|title"
		  +"|type<button?checkbox?file?hidden?image?password?radio?reset?submit?text"
		  +"|usemap|value],"
		+"ins[cite|class|datetime|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"isindex[class|dir<ltr?rtl|id|lang|prompt|style|title],"
		+"kbd[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"label[accesskey|class|dir<ltr?rtl|for|id|lang|onblur|onclick|ondblclick"
		  +"|onfocus|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
		  +"|onmouseover|onmouseup|style|title],"
		+"legend[align<bottom?left?right?top|accesskey|class|dir<ltr?rtl|id|lang"
		  +"|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"li[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title|type"
		  +"|value],"
		+"link[charset|class|dir<ltr?rtl|href|hreflang|id|lang|media|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|rel|rev|style|title|target|type],"
		+"map[class|dir<ltr?rtl|id|lang|name|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"menu[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"meta[content|dir<ltr?rtl|http-equiv|lang|name|scheme],"
		+"noframes[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"noscript[class|dir<ltr?rtl|id|lang|style|title],"
		+"object[align<bottom?left?middle?right?top|archive|border|class|classid"
		  +"|codebase|codetype|data|declare|dir<ltr?rtl|height|hspace|id|lang|name"
		  +"|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|standby|style|tabindex|title|type|usemap"
		  +"|vspace|width],"
		+"ol[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|start|style|title|type],"
		+"optgroup[class|dir<ltr?rtl|disabled<disabled|id|label|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"option[class|dir<ltr?rtl|disabled<disabled|id|label|lang|onclick|ondblclick"
		  +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
		  +"|onmouseover|onmouseup|selected<selected|style|title|value],"
		+"p[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|style|title],"
		+"param[id|name|type|value|valuetype<DATA?OBJECT?REF],"
		+"pre/listing/plaintext/xmp[align|class|dir<ltr?rtl|id|lang|onclick|ondblclick"
		  +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
		  +"|onmouseover|onmouseup|style|title|width],"
		+"q[cite|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"s[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
		+"samp[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"script[charset|defer|language|src|type],"
		+"select[class|dir<ltr?rtl|disabled<disabled|id|lang|multiple<multiple|name"
		  +"|onblur|onchange|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|size|style"
		  +"|tabindex|title],"
		+"small[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"span[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"strike[class|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title],"
		+"strong/b[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"style[dir<ltr?rtl|lang|media|title|type],"
		+"sub[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"sup[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title],"
		+"table[align<center?left?right|bgcolor|border|cellpadding|cellspacing|class"
		  +"|dir<ltr?rtl|frame|height|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|rules"
		  +"|style|summary|title|width],"
		+"tbody[align<center?char?justify?left?right|char|class|charoff|dir<ltr?rtl|id"
		  +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
		  +"|valign<baseline?bottom?middle?top],"
		+"td[abbr|align<center?char?justify?left?right|axis|bgcolor|char|charoff|class"
		  +"|colspan|dir<ltr?rtl|headers|height|id|lang|nowrap<nowrap|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|rowspan|scope<col?colgroup?row?rowgroup"
		  +"|style|title|valign<baseline?bottom?middle?top|width],"
		+"textarea[accesskey|class|cols|dir<ltr?rtl|disabled<disabled|id|lang|name"
		  +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onselect"
		  +"|readonly<readonly|rows|style|tabindex|title],"
		+"tfoot[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
		  +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
		  +"|valign<baseline?bottom?middle?top],"
		+"th[abbr|align<center?char?justify?left?right|axis|bgcolor|char|charoff|class"
		  +"|colspan|dir<ltr?rtl|headers|height|id|lang|nowrap<nowrap|onclick"
		  +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
		  +"|onmouseout|onmouseover|onmouseup|rowspan|scope<col?colgroup?row?rowgroup"
		  +"|style|title|valign<baseline?bottom?middle?top|width],"
		+"thead[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
		  +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
		  +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
		  +"|valign<baseline?bottom?middle?top],"
		+"title[dir<ltr?rtl|lang],"
		+"tr[abbr|align<center?char?justify?left?right|bgcolor|char|charoff|class"
		  +"|rowspan|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title|valign<baseline?bottom?middle?top],"
		+"tt[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
		+"u[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
		  +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
		+"ul[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
		  +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
		  +"|onmouseup|style|title|type],"
		+"var[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
		  +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
		  +"|title]",
		theme_advanced_resizing: true,
		theme_advanced_resize_horizontal : true,
		convert_urls : true,
	    forced_root_block : false,
	    force_br_newlines : true,
	    force_p_newlines : false,
		theme_advanced_disable: "styleselect,formatselect",
		theme_advanced_resizing_use_cookie : false

	});';
	echo '	tinyMCE.init({
		theme : "advanced",
		skin: "o2k7",
		editor_selector : "com_input",
		mode : "textareas",
		language: "'.((LANGUAGE == 'gb' OR LANGUAGE == 'us') ? 'en' : LANGUAGE).'",
		plugins : "inlinepopups,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,ecp",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "copy,paste,cut,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink",
		theme_advanced_buttons3 : "sub,sup,|,image,charmap,emotions,iespell,media,advhr,|,print,|,fullscreen,|,forecolor,backcolor,|,ecpphp,ecpquote,ecpflags,ecpuser",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_path_location : "bottom",
		plugin_insertdate_dateFormat : "%Y-%m-%d",
		plugin_insertdate_timeFormat : "%H:%M:%S",
		extended_valid_elements : "a[name|href|target|title],img[class|src|border=0|alt|title|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
		theme_advanced_resizing: true,
	    forced_root_block : false,
	    force_br_newlines : true,
	    force_p_newlines : false,
		convert_urls : true,	
		theme_advanced_resize_horizontal : false,
		theme_advanced_disable: "styleselect,formatselect",
		theme_advanced_resizing_use_cookie : false
	});
</script>';
}
function cache(&$tpl, $zeit = 1440) {
	$tpl->cache_dir = 'templates_c/';
	$tpl->compile_check = false;
	$tpl->caching = 2;
	$tpl->cache_lifetime = 1440;
}
// STRING Funktionen
function get_form_rights($sets = array()) {
	if(!is_array($sets)) $sets = array();
	global $db, $groups;
	$db->query('SELECT groupID, name FROM '.DB_PRE.'ecp_groups ORDER by name ASC');
	$rights = '<option value="all" '.(count($sets) < 3 ? 'selected="selected"' :'' ).'>'.ALL.'</option>';
	while($row = $db->fetch_assoc()) {
		(in_array($row['groupID'], $sets)) ? $sub = 'selected="selected"' : $sub = '';
		if(isset($groups[$row['name']])) $row['name'] = $groups[$row['name']];
		$rights .= '<option '.$sub.' value="'.$row['groupID'].'">'.$row['name'].'</option>'."\n";		
	}	
	return $rights;
}
function replace_umlaute($str) {
	$search = array('ä','ö','ü','Ä','Ö','Ü','ß');
	$replace = array('&auml;','&ouml;','&uuml;', '&Auml;','&Ouml;','&Uuml;', '&szlig;');
	return str_replace($search, $replace, $str);
}
function strsave($str) {
	return mysql_real_escape_string($str);
}
function comment_save($str) {
	$str = htmLawed($str, array('safe'=>1));
	$str = preg_replace_callback('/<.*?img.*?src="(.+?)".*?>/Ui', 'check_img_size', $str);	
	return $str;
}
function bb_code($text) {
	$text = preg_replace_callback ("~\[CODE\](.*)\[/CODE\]~Uism","code",$text);
	$text = preg_replace_callback ("~\[PHP\](.*)\[/PHP\]~Uism","highlight",$text);	
	preg_match_all('{\[(/?.*)(=.*)?\]}Usmi',$text,$hits,PREG_OFFSET_CAPTURE);
	$tags = array('QUOTE');  
	foreach ($hits[1] as $key => $tag){ 
	    if (in_array($tag[0],$tags)){ 
	        $openers[] = $tag; 
	        $additionals[] = isset($hits[2][$key][0]) ? $hits[2][$key][0] : '';  
	    } elseif (isset($tag[0][0]) AND $tag[0][0] == '/' AND in_array(substr($tag[0],1),$tags)){ 
	        $last = array_pop($openers); 
	        if ($last[0] == substr($tag[0],1)){ 
	            $pairs[] = array('opentag' => $last[0], 'offset' => $last[1], 'additional' => array_pop($additionals)); 
	            $pairs[] = array('closetag' => $tag[0], 'offset' => $tag[1]); 
	        } 
	        else array_push($openers,$last); 
	         
	    } 
	} 
	if(isset($pairs)) {	
		uasort($pairs,"sortByOffset"); 
		foreach ($pairs as $pair){ 
		    if (isset($pair['opentag'])){ 
		        if ($pair['opentag'] == 'QUOTE') 
		            $text = substr_replace($text,'<div class="codetitle">'.(strlen($pair['additional']) ? QUOTE_FROM : QUOTE).' '.substr($pair['additional'],1).'</div><div class="coderahmen"><div class="qoute">',$pair['offset']-1,strlen($pair['opentag'].$pair['additional'])+2); 
		        if ($pair['opentag'] == 'PHP') 
		            $text = substr_replace($text,"<b>",$pair['offset']-1,strlen($pair['opentag'])+2); 
		    } 
		    else{ 
		        if ($pair['closetag'] == '/QUOTE') 
		            $text = substr_replace($text,"</div></div>",$pair['offset']-1,strlen($pair['closetag'])+2); 
		        if ($pair['closetag'] == '/PHP') 
		            $text = substr_replace($text,"</b>",$pair['offset']-1,strlen($pair['closetag'])+2); 
		    } 
		} 	
	}
  return $text;
}
function sortByOffset($a,$b){ 
    if ($a['offset'] == $b['offset']) { 
        return 0; 
    } 
    return ($a['offset'] > $b['offset']) ? -1 : 1; 
}  
function check_img_size($arg) {
	preg_match('/src="(.+?)"(.*?)>/i', $arg[0], $array);
	$img = getimagesize($array[1]);
	if(isset($img[0])) {
		if($img[0] > MAX_IMG_WIDTH) {
			$verh = $img[0]/MAX_IMG_WIDTH;
			$hoehe = round($img[1]/$verh);			
			if(strpos($arg[0], 'width')) {
				$array[0] = preg_replace('/height="(\d+)"/i', 'height="'.$hoehe.'"', $array[0]);
				$array[0] = preg_replace('/width="(\d+)"/i', 'width="'.MAX_IMG_WIDTH.'" ', $array[0]);
				return str_replace('<img src="'.$arg[1].'" />', '<a href="'.$array[1].'" rel="lightbox"><img '.$array[0].'</a>', $arg[0]);
			} else {
				return '<a href="'.$array[1].'" rel="lightbox">'.substr($arg[0], 0, strpos($arg[0],'src')-1).' height="'.$hoehe.'" width="'.MAX_IMG_WIDTH.'" '.substr($arg[0], strpos($arg[0], 'src')).'</a>';
			}
		} else {
			return $arg[0];
		}
	} else {
		return '';
	}
}
   function code($string) {
       $string[1] = html_entity_decode($string[1]);
       $string[1] = str_replace("<br />", "\n", $string[1]);
       $Line = explode("\n",$string[1]);
       $anzahl = count($Line);
       for($i=1;$i<=$anzahl;$i++) {
           @$line .= "&nbsp;".$i."&nbsp;<br>";
       }
       srand((double)microtime()*1000000);
       for($i=1;$i<=7;$i++) {
           @$id .= rand(0,9);
       }
       $after_replace = htmlspecialchars($string[1]);
       // Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
       $after_replace = str_replace("  ", "&nbsp; ", $after_replace);
       // now Replace 2 spaces with " &nbsp;" to catch odd #s of spaces.
       $after_replace = str_replace("  ", " &nbsp;", $after_replace);
       // Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
       $after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);
       // now Replace space occurring at the beginning of a line
       $after_replace = preg_replace("/^ {1}/m", '&nbsp;', $after_replace);
       $after_replace = str_replace("\n", "<br />", $after_replace);
       IF($anzahl>6) {
           $height = 'style="height:80px;"';
           $size = '&#155;&#139; <span id="ex'.$id.'"><a onclick="code_extend(\''.$id.'\',\'1\');return false;">'.SHOW_ALL.'</a></span>';
       }
       $header = '<div class="codetitle">'.CODE.': '.@$size.'</div><div id="'.$id.'" '.@$height.' class="coderahmen">';
       $header .= '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td width="3%" valign="top" class="codezeile"><code>'.$line.'</code></td><td width="97%" valign="top"><div id="q'.$id.'" class="code"><code>';
       $footer=$after_replace.'</div></code></td></tr></table></div>';
       return $header.$footer;
   }
   function highlight($string){
       $string[1] = html_entity_decode($string[1]);
       $string[1] = str_replace("<br />", "\n", $string[1]);
       $Line = explode("\n",$string[1]);
       if(strpos($string[1],'<?php') == false) {
       		$string[1] = '<?php '.$string[1];
       		$add = true;
       }       
       $anzahl = count($Line);
       for($i=1;$i<=$anzahl;$i++) {
           @$line .= "&nbsp;".$i."&nbsp;<br>";
       }
       srand((double)microtime()*1000000);
       for($i=1;$i<=7;$i++) @$id .= rand(0,9);
       ob_start();
       highlight_string($string[1]);
       $Code=ob_get_contents();
       ob_end_clean();
       if(isset($add)) $Code = str_replace('&lt;?php&nbsp;', '', $Code);
       IF($anzahl>6) {
           $height = 'style="height:80px;"';
           $size = '&#155;&#139; <span id="ex'.$id.'"><a onclick="code_extend(\''.$id.'\',\'1\');return false;">'.SHOW_ALL.'</a></span>';
       }
       $header = '<div class="codetitle">'.PHP_CODE.': '.@$size.'</div><div id="'.$id.'" '.@$height.' class="coderahmen">';
       $header .= '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td width="3%" valign="top" class="codezeile"><code>'.$line.'</code></td><td width="97%" valign="top"><div id="q'.$id.'" class="php"><code>';
       $footer=$Code.'</div></code></td></tr></table></div>';
       return $header.$footer;
   }
function make_forum_pre($str) {
	return strsave($str);
}
function check_url($url) {
	IF (trim($url) != "") {
		if(!(substr($url, 0, 7) == "http://"))  {
			$url = "http://".$url;
		}
	}
	return $url;
}
function check_irc($irc) {
	IF (trim($irc) != "") {
		IF ((substr($irc, 0, 1) == "#")) {
			$irc = substr($irc, 1);
		}
	}
	return $irc;
}
function check_email($email) {
	IF (preg_match('/^[a-z0-9._-]+@([a-z0-9._-]+\.)+([a-z]){2,4}$/i', $email)) {
		return true;
	} else {
		return false;
	}
}
function form_country($wert = 'de') {
	global $countries;
	$str = '';
	foreach($countries AS $key => $value) {
		$sub = ($key == $wert) ? ' selected="selected"' : '';
		$str .= '<option value="'.$key.'"'.$sub.'>'.htmlentities($value).'</option>'."\n";
	}
	return $str;
}
function get_random_string($lenght, $mode=1) {
	// Mode 1 Zufallszahlen
	// Mode 2 Zufallszeichen
	switch($mode) {
		case 1:
			for($i=1; $i<=$lenght;$i++) @$zahl .= '9';
			return rand(0, $zahl);
			break;
		case 2:
			$codearray = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "1", "2", "3", "4", "5", "6", "7", "8", "9");
			$anzahl = count($codearray);
			$code = "";
			FOR($i=1;$i<=$lenght;$i++) {
				$posi = rand("0", $anzahl - 1);
				$code .= $codearray[$posi];
			}
			return $code;
			break;
	}
}
function news_links($links) {
	$links = preg_replace('#\[URL=(.*)\](.*)\[/URL\]#Uis', '<img src="templates/'.DESIGN.'/images/pfeil_o.gif" alt="dot" /> <a href="\1" target="_blank">\2</a><br />', $links);
	return $links;
}
// ARRAY Funktionen
function array_stripslashes(&$var) {
	IF(is_string($var)) {
		$var = stripslashes($var);
	} else {
		IF(is_array($var)) {
			foreach($var AS $key => $value) {
				array_stripslashes($var[$key]);
			}
		}
	}
}
function trim_array(&$var) {
	IF(is_string($var)) {
		$var = trim($var);
	} else {
		IF(is_array($var)) {
			foreach($var AS $key => $value) {
				trim_array($var[$key]);
			}
		}
	}
}
trim_array($_POST);
trim_array($_GET);
IF(get_magic_quotes_gpc()) {
	array_stripslashes($_GET);
	array_stripslashes($_POST);
	array_stripslashes($_COOKIE);
}
	function ajax_convert_array(&$var) {
		IF(is_string($var)) {
			$var = ajax_html_convert($var);
		} else {
			IF(is_array($var)) {
				foreach($var AS $key => $value) {
					ajax_convert_array($var[$key]);
				}
			}
		}
	}	
	function html_convert_array(&$var) {
		IF(is_string($var)) {
			$var = html_ajax_convert($var);
		} else {
			IF(is_array($var)) {
				foreach($var AS $key => $value) {
					html_convert_array($var[$key]);
				}
			}
		}
	}		
function get_languages($inhalt = array()) {
	global $language_array;
	$files = scan_dir('inc/language/', true);
	$lang = array();
	foreach($files AS $value) {
		if(strpos($value, '.php')) {
			$array = array();
			$array['content'] = @$inhalt[substr($value, 0, strpos($value, '.'))];
			$array['lang'] = substr($value, 0, strpos($value, '.'));
			$array['name'] = $language_array[@$array['lang']];
			$lang[] = $array;
		}
	}
	return $lang;
}
function admin_make_rights($array) {
	if(in_array('all', $array))
		$rights = '';
	else {
		$rights = ',';
		foreach($array AS $key) {
			$rights .= (int)$key.',';
		}
	}
	return $rights;
}
// Sonstige Funktionen
function get_mircotime() {
	$time = explode(' ',substr(microtime(),1));
	$time = $time[1]+$time[0];	
	return $time;
}
function format_nr($nr, $dez =0) {
	return number_format($nr, $dez, ',', '.');
}
function comments_get($bereich, $id, $conditions, $ajax = 0, $border =1, $session='') {
	global $db, $countries;
	$id = (int)$id;
	$bereich = strsave($bereich);
	if(@$_SESSION['rights']['public'][($session ? $session : $bereich)]['com_view'] OR @$_SESSION['rights']['superadmin']) {
		$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich = \''.$bereich.'\' AND subID = '.$id);
		if($anzahl) {
			$seiten = get_sql_limit($anzahl, $conditions['LIMIT']);
			$db->query('SELECT
		                     a.username, a.registerdate, a.rID, rankname, iconname, author, c.homepage, c.email, comID, c.userID, a.country, a.sex, beitrag, datum, editdatum, editby, edits, lastklick, a.avatar, a.signatur, comments, b.username as editfrom, uID as online
		                 FROM
		                     '.DB_PRE.'ecp_comments as c
						LEFT JOIN '.DB_PRE.'ecp_user as a ON (c.userID = a.ID)
						LEFT JOIN '.DB_PRE.'ecp_user as b ON (c.editby = b.ID)
						LEFT JOIN '.DB_PRE.'ecp_user_stats ON (c.userID = '.DB_PRE.'ecp_user_stats.userID)
						LEFT JOIN '.DB_PRE.'ecp_ranks ON (a.rID = rankID)
						LEFT JOIN '.DB_PRE.'ecp_online ON (uID = c.userID AND lastklick > '.(time()-SHOW_USER_ONLINE).')
		                 WHERE
		                     subID = '.$id.' AND bereich = "'.$bereich.'"
		                 GROUP BY comID
		                 ORDER BY
		                     datum '.$conditions['ORDER'].'
		                 LIMIT '.$seiten[1].','.$conditions['LIMIT']);
			$comments = array();
			while($row = $db->fetch_assoc()) {
				$row['nr'] = ++$seiten[1];
				$row['comments'] = format_nr($row['comments']);
				$row['countryname'] = @$countries[$row['country']];
				($row['sex'] == 'male')? $row['sextext'] = MALE : $row['sextext'] = FEMALE;			
				if($row['edits']) {
					$row['edit'] = str_replace(array('{anzahl}', '{von}', '{last}'), array($row['edits'], '<a href="?section=user&id='.$row['editby'].'">'.$row['editfrom'].'</a>', date(LONG_DATE, $row['editdatum'])), COMMENT_EDIT_TXT);
				}			
				$row['datum'] = date(LONG_DATE, $row['datum']);
				$row['quote'] = $row['beitrag'];
				$row['beitrag'] = bb_code($row['beitrag']);
				$comments[] = $row;
			}
			$tpl = new smarty;
			$tpl->assign('id', $id);
			$tpl->assign('link', $conditions['link']);
			$tpl->assign('section', $conditions['section']);
			if($seiten[0] > 1)
				$tpl->assign('seiten', makepagelink_ajax($conditions['link'], 'return load_com_page(\''.$bereich.'\', '.$id.', {nr});', @$_GET['page'], $seiten[0]));
			$tpl->assign('comments', $comments);
			$tpl->assign('bereich', $bereich);
			$tpl->assign('bereich2', ($session ? $session : $bereich));
			$tpl->assign('ajax', $ajax);
			if($ajax) {
				ob_start();
				$tpl->display(DESIGN.'/tpl/comments.html');
				$db->close();
				$content = ob_get_contents();
				ob_end_clean();
				echo html_ajax_convert($content);
				die();
			}
			if($border) {
				ob_start();
				$tpl->display(DESIGN.'/tpl/comments.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(COMMENTS, $content, '',1);
			} else {
				$tpl->display(DESIGN.'/tpl/comments.html');	
			}
		} else {
			if(!$ajax)
				table(COMMENTS, '<div id="comments_bereich">'.NO_ENTRIES.'</div>');
			else 
				echo html_ajax_convert(NO_ENTRIES);
		}
		if((@$_SESSION['rights']['public'][($session ? $session : $bereich)]['com_add'] OR @$_SESSION['rights']['superadmin']) AND !$ajax) {
			$tplc = new smarty;
			$tplc->assign('section', ($conditions['section'] ? $conditions['section'] : $bereich));
			$tplc->assign('action', $conditions['action']);
			$tplc->assign('id', $id);
			if($border) {
				ob_start();
				$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(ADD_COMMENT, $content, '',1);
			} else {
				$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
			}
		} elseif (!$ajax) {
			table(ACCESS_DENIED, NO_RIGHTS_ADD_COMMENT);
		}
	} else {
		table(ACCESS_DENIED, NO_RIGHTS_READ_COMMENT);
	}
}
function comments_add($bereich, $id, $conditions, $session = false) {
	global $db;
	if(@$_SESSION['rights']['public'][($session ? $session : $bereich)]['com_add'] OR @$_SESSION['rights']['superadmin']) {
		if(isset($_POST['submit'])) {
			if($_POST['commentstext'] == '') {
				table(ERROR, NO_INPUT);
				$tplc = new smarty;
				$tplc->assign('section', $conditions['section']);
				$tplc->assign('action', $conditions['action']);
				$tplc->assign('id', $id);
				foreach($_POST AS $key => $value) $tplc->assign($key,$value);
				ob_start();
				$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(ADD_COMMENT, $content, '',1);
			} else {		
				if(!isset($_SESSION['userID'])) {
					if($zeit = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'bereich = "'.$bereich.'" AND subID = '.$id.' AND IP = "'.$_SERVER['REMOTE_ADDR'].'" AND datum > '.(time()-$conditions['SPAM']))) {
						table(SPAM_PROTECTION, str_replace(array('{sek}', '{zeit}'), array($conditions['SPAM'], ($zeit+$conditions['SPAM']-time())), SPAM_PROTECTION_MSG));
						$tplc = new smarty;
						$tplc->assign('section', $conditions['section']);
						$tplc->assign('action', $conditions['action']);
						$tplc->assign('id', $id);
						foreach($_POST AS $key => $value) $tplc->assign($key,$value);
						ob_start();
						$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
						$content = ob_get_contents();
						ob_end_clean();
						main_content(ADD_COMMENT, $content, '',1);
					} else {
						if($_POST['author'] == '' OR $_POST['captcha'] == '') {
							table(ERROR, NOT_NEED_ALL_INPUTS);
							$tplc = new smarty;
							$tplc->assign('section', $conditions['section']);
							$tplc->assign('action', $conditions['action']);
							$tplc->assign('id', $id);
							foreach($_POST AS $key => $value) $tplc->assign($key,$value);
							ob_start();
							$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
							$content = ob_get_contents();
							ob_end_clean();
							main_content(ADD_COMMENT, $content, '',1);							
						} elseif (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha'])) {
							table(ERROR, CAPTCHA_WRONG);
							$tplc = new smarty;
							$tplc->assign('section', $conditions['section']);
							$tplc->assign('action', $conditions['action']);
							$tplc->assign('id', $id);
							foreach($_POST AS $key => $value) $tplc->assign($key,$value);
							ob_start();
							$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
							$content = ob_get_contents();
							ob_end_clean();
							main_content(ADD_COMMENT, $content, '',1);									
						} else {
							$sql = sprintf('INSERT INTO 
												'.DB_PRE.'ecp_comments (`subID`, `bereich`, `author`, `beitrag`, `email`, `homepage`, `datum`, `IP`) 
											VALUES 
												(%d,"%s", "%s", "%s", "%s", "%s", %d, "%s")', 
											$id, $bereich, strsave(htmlspecialchars($_POST['author'])), strsave(comment_save($_POST['commentstext'])), strsave(htmlspecialchars($_POST['email'])), strsave(htmlspecialchars(check_url($_POST['homepage']))), time(), $_SERVER['REMOTE_ADDR']);
							if($db->query($sql)) {
								$lastid = $db->last_id();
								if($conditions['ORDER'] == 'ASC') {
									$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'subID = '.$id.' AND bereich = "'.$bereich.'"');
									$seiten = ceil($anzahl/$conditions['LIMIT']);
									header1($conditions['link'].'&page='.$seiten.'#com_'.$lastid);
								} else {
									header1($conditions['link'].'#com_'.$lastid);
								}		
							}
						}
					}
				} else {
					if($zeit = $db->result(DB_PRE.'ecp_comments', 'datum', 'bereich = "'.$bereich.'" AND subID = '.$id.' AND userID = "'.$_SESSION['userID'].'" AND datum > '.(time()-$conditions['SPAM']))) {
						table(SPAM_PROTECTION, str_replace(array('{sek}', '{zeit}'), array($conditions['SPAM'], ($zeit+$conditions['SPAM']-time())), SPAM_PROTECTION_MSG));
						$tplc = new smarty;
						$tplc->assign('section', $conditions['section']);
						$tplc->assign('action', $conditions['action']);
						$tplc->assign('id', $id);
						foreach($_POST AS $key => $value) $tplc->assign($key,$value);
						ob_start();
						$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
						$content = ob_get_contents();
						ob_end_clean();
						main_content(ADD_COMMENT, $content, '',1);
					} else {					
						$sql = sprintf('INSERT INTO 
											'.DB_PRE.'ecp_comments (`subID`, `bereich`, `userID`, `beitrag`, `datum`, `IP`) 
										VALUES 
											(%d,"%s", %d, "%s", %d, "%s")', 
										$id, $bereich, $_SESSION['userID'], strsave(comment_save($_POST['commentstext'])), time(), $_SERVER['REMOTE_ADDR']);
						if($db->query($sql)) {
							$lastid = $db->last_id();
							if(@$_SESSION['userID']) {
								$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET comments = comments + '.POSTS_PER_COMMENTS.', money = money + '.MONEY_PER_COMMENT.' WHERE userID = '.$_SESSION['userID']);
								update_rank($_SESSION['userID']);
							}
							if($conditions['ORDER'] == 'ASC') {
								$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'subID = '.$id.' AND bereich = "'.$bereich.'"');
								$seiten = ceil($anzahl/$conditions['LIMIT']);
								header1($conditions['link'].'&page='.$seiten.'#com_'.$lastid);
							} else {
								header1($conditions['link'].'#com_'.$lastid);
							}	
						}					
					}
				}
			}
		} else {
			table(ERROR, NO_INPUT);
			$tplc = new smarty;
			$tplc->assign('section', ($conditions['section'] ? $conditions['section'] : $bereich));
			$tplc->assign('action', $conditions['action']);
			$tplc->assign('id', $id);
			ob_start();
			$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
			$content = ob_get_contents();
			ob_end_clean();
			main_content(ADD_COMMENT, $content, '',1);
		}
	} else {
		table(ACCESS_DENIED, NO_RIGHTS_ADD_COMMENT);
	}
}
function comments_edit($bereich, $subid, $id, $conditions, $session = '', $admin = '') {
	global $db;
	$com = $db->fetch_assoc('SELECT `subID`, `bereich`, `userID`, `author`, `beitrag`, `email`, `homepage`, `datum`, `editdatum`, `editby`, `edits`, `IP` FROM '.DB_PRE.'ecp_comments WHERE subID = '.$subid.' AND bereich = "'.strsave($bereich).'" AND comID = '.$id);
	if(isset($com['subID'])) {
		if(isset($_SESSION['userID']) AND ((@$_SESSION['rights']['public'][($session ? $session : $bereich)]['com_edit'] AND $_SESSION['userID'] == $com['userID'] AND $com['userID'] != 0) OR @$_SESSION['rights']['admin'][$bereich][($admin ? $admin : 'com_edit')] OR @$_SESSION['rights']['superadmin'])) {
			if(isset($_POST['submit'])) {
				if($_POST['commentstext'] == '') {
					table(ERROR, NO_INPUT);
					$tplc = new smarty;
					$tplc->assign('section', ($conditions['section'] ? $conditions['section'] : $bereich));
					$tplc->assign('action', $conditions['action']);
					$tplc->assign('id', $id);
					$tplc->assign('edit', 1);
					$tplc->assign('userID', $com['userID']);
					$tplc->assign('sub', '&subid='.$subid);
					foreach($_POST AS $key => $value) $tplc->assign($key,$value);
					ob_start();
					$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
					$content = ob_get_contents();
					ob_end_clean();
					main_content(EDIT_COMMENT, $content, '',1);
				} else {		
					if($com['userID'] == 0) {
						if($_POST['author'] == '') {
							table(ERROR, NOT_NEED_ALL_INPUTS);
							$tplc = new smarty;
							$tplc->assign('section', ($conditions['section'] ? $conditions['section'] : $bereich));
							$tplc->assign('action', $conditions['action']);
							$tplc->assign('id', $id);
							$tplc->assign('edit', 1);
							$tplc->assign('userID', $com['userID']);
							$tplc->assign('sub', '&subid='.$subid);
							foreach($_POST AS $key => $value) $tplc->assign($key,$value);
							ob_start();
							$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
							$content = ob_get_contents();
							ob_end_clean();
							main_content(EDIT_COMMENT, $content, '',1);							
						} else {
							$sql = sprintf('UPDATE
												'.DB_PRE.'ecp_comments SET
												`author` = \'%s\', `beitrag` = \'%s\', `email` = \'%s\', `homepage` = \'%s\', `editdatum` = %d, `editby` = %d, `edits` = edits + 1 
											WHERE comID = %d',
											strsave(htmlspecialchars($_POST['author'])), strsave(comment_save($_POST['commentstext'])), strsave(htmlspecialchars($_POST['email'])), strsave(htmlspecialchars(check_url($_POST['homepage']))), time(), $_SESSION['userID'], $id);
							if($db->query($sql)) {
								$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'subID = '.$subid.' AND bereich = "'.$bereich.'" AND comID '.($conditions['ORDER'] == "ASC" ? '<' : '>').' '.$id);
								$seiten = ceil($anzahl/$conditions['LIMIT']);
								header1($conditions['link'].'&page='.$seiten.'#com_'.$id);	
							}
						}
					} else {				
						$sql = sprintf('UPDATE 
											'.DB_PRE.'ecp_comments SET 
											`beitrag` = \'%s\', `editdatum` = %d, `editby` = %d, `edits` = edits + 1  
										WHERE comID = %d', 
										strsave(comment_save($_POST['commentstext'])), time(), $_SESSION['userID'], $id);
						if($db->query($sql)) {
							$anzahl = $db->result(DB_PRE.'ecp_comments', 'COUNT(comID)', 'subID = '.$subid.' AND bereich = "'.$bereich.'" AND comID '.($conditions['ORDER'] == "ASC" ? '<=' : '>=').' '.$id);
							$seiten = ceil($anzahl/$conditions['LIMIT']);
							header1($conditions['link'].'&page='.$seiten.'#com_'.$id);
						}					
					}
				}
			} else {
				$tplc = new Smarty();
				$tplc->assign('section', ($conditions['section'] ? $conditions['section'] : $bereich));
				$tplc->assign('action', $conditions['action']);
				$tplc->assign('id', $id);
				$tplc->assign('edit', 1);
				$tplc->assign('sub', '&subid='.$subid);
				$tplc->assign('commentstext', htmlentities($com['beitrag']));
				$tplc->assign('userID', $com['userID']);
				$tplc->assign('author', $com['author']);
				$tplc->assign('homepage', $com['homepage']);
				$tplc->assign('email', $com['email']);
				ob_start();
				$tplc->display(DESIGN.'/tpl/comment_add_edit.html');
				$content = ob_get_contents();
				ob_end_clean();
				main_content(EDIT_COMMENT, $content, '',1);
			}
		} else {
			table(ACCESS_DENIED, NO_ACCESS_RIGHTS);
		}
	} else {
		table(ERROR, NO_ENTRIES_ID);
	}
}
function send_email($to, $title, $content, $html = 0, $from = '', $fromname = '') {
	if(!defined('DISPLAY_XPM4_ERRORS')) define('DISPLAY_XPM4_ERRORS', false); // display XPM4 errors
	error_reporting(E_ALL);
	$m = new XMail();
	// set from address and name
	if($from) {
		$m->From($from,  $fromname);
	} else {
		$m->From(SITE_EMAIL, SITE_EMAIL_NAME);
	}		
	// add to address and name
	$m->AddTo($to);
	// set subject
	$m->Subject($title);
	if($html)
		$m->Html($content);
	else 
		$m->Text($content);		
	if(SMTP_AKTIV) {
		$c = $m->Connect(SMTP_HOST, (int)SMTP_PORT, SMTP_USER, SMTP_PASS, 'tls', 10, 'localhost', null, 'plain'); //or die(print_r($m->Result));
	}		
	
	return $m->Send(SMTP_AKTIV ? $c : null);
}
function header1($location) {
	if(isset($_GET[session_name()])) {
		header('Location: '.SITE_URL.$location.'&'.session_name().'='.session_id());
	} else {
		header('Location: '.SITE_URL.$location);
	}
}
function update_rights() {
	global $db;
	unset($_SESSION['rights']);
	unset($_SESSION['groups1']);
	unset($_SESSION['access_search']);
	if(isset($_SESSION['userID'])) {
		$db->query('SELECT groupID, admin, public FROM '.DB_PRE.'ecp_user_groups JOIN '.DB_PRE.'ecp_groups ON (gID=groupID) WHERE userID = '.$_SESSION['userID'].' ORDER BY groupID ASC');
		$_SESSION['access_search'] = '';
		while($row = $db->fetch_assoc()) {
			if($row['groupID'] == 1) {
				$_SESSION['rights']['superadmin'] = 1;
			}
			@$_SESSION['groups1'][$row['groupID']] = true;
			@$_SESSION['access_search'] .= ' OR access LIKE "%,'.$row['groupID'].',%"';
			foreach(explode(']', $row['admin']) AS $value) {
				$bereich = substr($value, 0, strpos($value, ':'));
				$speicher = explode(',', substr($value, strpos($value, ':')+1));
				//print_r($speicher);
				foreach($speicher AS $value1) {
					$key = substr($value1, 0, strpos($value1, '='));
					$value2 = substr($value1, strpos($value1,'=')+1);
					if(@$_SESSION['rights']['admin'][$bereich][$key] < $value2)
					$_SESSION['rights']['admin'][$bereich][$key] = $value2;

				}
			}
			foreach(explode(']', $row['public']) AS $value) {
				$bereich = substr($value, 0, strpos($value, ':'));
				$speicher = explode(',', substr($value, strpos($value, ':')+1));
				//print_r($speicher);
				foreach($speicher AS $value1) {
					$key = substr($value1, 0, strpos($value1, '='));
					$value2 = substr($value1, strpos($value1,'=')+1);
					if(@$_SESSION['rights']['public'][$bereich][$key] < $value2)
					$_SESSION['rights']['public'][$bereich][$key] = $value2;

				}
			}
		}
		$_SESSION['access_search'] = substr($_SESSION['access_search'], 4);
		$db->query('UPDATE '.DB_PRE.'ecp_user SET update_rights = 0 WHERE ID = '.$_SESSION['userID']);
	} else {
		$row = $db->fetch_assoc('SELECT public FROM '.DB_PRE.'ecp_groups WHERE groupID = 4');
		$_SESSION['access_search'] = '';
		@$_SESSION['groups1'][4] = true;
		@$_SESSION['access_search'] .= ' OR access LIKE "%,4,%"';
		foreach(explode(']', $row['public']) AS $value) {
			$bereich = substr($value, 0, strpos($value, ':'));
			$speicher = explode(',', substr($value, strpos($value, ':')+1));
			//print_r($speicher);
			foreach($speicher AS $value1) {
				$key = substr($value1, 0, strpos($value1, '='));
				$value2 = substr($value1, strpos($value1,'=')+1);
				if(@$_SESSION['rights']['public'][$bereich][$key] < $value2)
				$_SESSION['rights']['public'][$bereich][$key] = $value2;
			}
		}
		$_SESSION['access_search'] = substr($_SESSION['access_search'], 4);
	}
}
function get_sql_limit($anzahl, $limit) {
	$start = isset($_GET['page'])?(int)$_GET['page']:1;
	$num_pages = ceil($anzahl/$limit);
	if(!$num_pages) {
		$num_pages = 1;
	}
	if($start < 1) {
		$start = 1;
	}
	if($start > $num_pages) {
		$start = $num_pages;
	}
	$offset = ($start - 1) * $limit;
	return array($num_pages, $offset);
}
function scan_dir($dir, $no_dots=FALSE) {
	$files = array();
	$dh  = @opendir($dir);
	if ($dh!=FALSE) {
		while (false !== ($filename = readdir($dh))) {
			$files[] = $filename;
		}
		if ($no_dots) {
			while(($ix = array_search('.',$files)) > -1)
			unset($files[$ix]);
			while(($ix = array_search('..',$files)) > -1)
			unset($files[$ix]);
		}
		sort($files);
	}
	return $files;
}


  function json_encode_string($in_str)
  {
    mb_internal_encoding("UTF-8");
    $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
    $str = "";
    for($i=mb_strlen($in_str)-1; $i>=0; $i--)
    {
      $mb_char = mb_substr($in_str, $i, 1);
      if(mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match))
      {
        $str = sprintf("\\u%04x", $match[1]) . $str;
      }
      else
      {
        $str = $mb_char . $str;
      }
    }
    return $str;
  }
  function goodsize($size) {
        IF ($size > 1099511627776) {
            return round($size / 1099511627776,2)." TB";
        } elseif ($size > 1073741824) {
            return round($size / 1073741824,2)." GB";
        } elseif ($size > 1048576) {
            return round($size / 1048576,2)." MB";
        } elseif ($size > 1024) {
            return round($size / 1024,2)." kB";
        } else {
            return round($size,2)." B";
        }
}
  function get_ordner_inhalt($ordner, $pfad) {
  	$files = scan_dir($ordner.'/'.$pfad, true);
  	$tpl = new smarty;
  	$tpl->assign('ordner', $pfad);  
  	$tpl->assign('sid', session_name().'='.session_id());	
  	if($pfad != '') {
  		$folders = array(array('name' => '..', 'back'=> substr($pfad,0,strrpos($pfad, '/'))));
  		$pfad .= '/';
  		$array = explode('/', $pfad);
	  	foreach($array as $value) {
	  		if($value != '') {
	  			@$spe .= '/<a href="#" onclick="load_dir(\''.@$spe2.'/'.$value.'\'); return false;">'.$value.'</a>';
	  			@$spe2 .= '/'.$value;
	  		}
	  	}
	  	$tpl->assign('navi', @$spe);   	  		
  	} else {
  		$folders = array();
  	}
  	$dateien = array();
  	if(count($files)) {
  		foreach($files AS $value) {
  			$sub = array();
  			if(is_dir($ordner.'/'.$pfad.$value)) {
  				$sub['name'] = $value;
  				$folders[] = $sub;
  			} else {
  				$sub['name'] = $value;
  				$sub['size'] = filesize($ordner.'/'.$pfad.$value);
  				$sub['filesize'] = goodsize($sub['size']);
  				$icon = substr($value, strrpos($value, '.')+1);
  				(file_exists('images/file_icons/'.$icon.'.png')) ? $sub['icon'] = $icon.'.png' : $sub['icon'] =  'file.png';
  				$dateien[] = $sub;
 			}
  		}
  	}
  	$tpl->assign('folders', $folders);
  	$tpl->assign('files', $dateien);
	ob_start();
	$tpl->display(DESIGN.'/tpl/admin/browser'.(UPLOAD_METHOD == 'old' ? '_old' : '').'.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
  }
 function get_games_form($game = '', $fightus=1) {
 	global $db;
 	if($fightus) {
 		$db->query('SELECT gamename, gameID FROM '.DB_PRE.'ecp_wars_games ORDER BY gamename');
 	} else {
 		$db->query('SELECT gamename, gameID FROM '.DB_PRE.'ecp_wars_games WHERE fightus = 1 ORDER BY gamename');
 	}
 	$games = '<option value="0">'.CHOOSE.'</option>';
 	while($row = $db->fetch_assoc()) {
 		($game == $row['gameID']) ? $sub = 'selected="selected"' : $sub = '';
 		$games .= '<option '.$sub.' value="'.$row['gameID'].'">'.htmlspecialchars($row['gamename']).'</option>';
 	}
 	return $games;
}
function get_teams_form($team = '') {
 	global $db;
 	$db->query('SELECT tname, tID FROM '.DB_PRE.'ecp_teams WHERE cw = 1 ORDER BY tname');
 	$teams = '<option value="0">'.CHOOSE.'</option>';
 	while($row = $db->fetch_assoc()) {
 		($team == $row['tID']) ? $sub = 'selected="selected"' : $sub = '';
 		$teams .= '<option '.$sub.' value="'.$row['tID'].'">'.htmlspecialchars($row['tname']).'</option>';
 	}
 	return $teams;
}
function get_teams_form_joinus($team = '') {
 	global $db;
 	$db->query('SELECT tname, tID FROM '.DB_PRE.'ecp_teams WHERE joinus = 1 ORDER BY tname');
 	$teams = '<option value="0">'.CHOOSE.'</option>';
 	while($row = $db->fetch_assoc()) {
 		($team == $row['tID']) ? $sub = 'selected="selected"' : $sub = '';
 		$teams .= '<option '.$sub.' value="'.$row['tID'].'">'.htmlspecialchars($row['tname']).'</option>';
 	}
 	return $teams;
}
function get_matchtype_form($match = '', $fightus=1) {
 	global $db;
 	if($fightus) {
 		$db->query('SELECT matchtypename, matchtypeID FROM '.DB_PRE.'ecp_wars_matchtype ORDER BY matchtypename');
 	} else {
 		$db->query('SELECT matchtypename, matchtypeID FROM '.DB_PRE.'ecp_wars_matchtype WHERE fightus = 1 ORDER BY matchtypename');
 	}
 	$matchtype = '<option value="0">'.CHOOSE.'</option>';
 	while($row = $db->fetch_assoc()) {
 		($match == $row['matchtypeID']) ? $sub = 'selected="selected"' : $sub = '';
 		$matchtype .= '<option '.$sub.' value="'.$row['matchtypeID'].'">'.htmlspecialchars($row['matchtypename']).'</option>';
 	}
 	return $matchtype;
}
function get_xonx_form($xonx = '') {
 	global $db;
 	$db->query('SELECT distinct(xonx) FROM '.DB_PRE.'ecp_wars ORDER BY xonx DESC');
 	$str = '<option value="0">'.CHOOSE.'</option>';
 	while($row = $db->fetch_assoc()) {
 		($xonx == $row['xonx']) ? $sub = 'selected="selected"' : $sub = '';
 		$str .= '<option '.$sub.' value="'.$row['xonx'].'">'.htmlspecialchars($row['xonx']).'</option>';
 	}
 	return $str;
}
function resize_picture($bild, $breite, $thumbname, $quali = 100, $mode= 1) {
	@chmod($bild, 0777);
	$image_array = getimagesize($bild);
	if($mode == 0 AND $image_array[0]<$image_array[1]) {
		$verh = $image_array[1]/$breite;
		$hoehe = $image_array[0]/$verh;
		$breite = $hoehe * $image_array[0] / $image_array[1];
	} else {
		$verh = $image_array[0]/$breite;
		$hoehe = $image_array[1]/$verh;
	}
	$original = ImageCreateFromJPEG ($bild);
	@unlink($path.$thumbname);
	$thumbnail = imagecreatetruecolor ($breite,$hoehe); // bei GD version 2.0.1 oder höher
	imagecopyresized ($thumbnail,$original,0,0,0,0,$breite,$hoehe,$image_array[0],$image_array[1]);
	Imagejpeg($thumbnail,$path.$thumbname,$quali); // absolute Pfadangabe des Uploadverzeichnisses
	umask(0);
	chmod($thumbnail,$path.$thumbname, CHMOD);
	ImageDestroy ($thumbnail);
}

function watermark($image, $watermark, $save_as) {
	chmod($image, 0777);
	$Grafik = ImageCreateFromJPEG($image);
	$Wasserzeichen = ImageCreateFromPNG($watermark);
	ImageCopy($Grafik, $Wasserzeichen, imagesx($Grafik)-imagesx($Wasserzeichen), imagesy($Grafik)-imagesy($Wasserzeichen), 0, 0, imagesx($Wasserzeichen), imagesy($Wasserzeichen));
	IF(imagejpeg($Grafik, $save_as,70)) {
		ImageDestroy ($Grafik);
		return true;
	} else
	return false;
}
function clanwar_delete($id) {
	global $db;
	$id = (int)$id;
	if($db->query('DELETE FROM '.DB_PRE.'ecp_wars WHERE warID = '.$id)) {
		$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE bereich = "clanwars" AND subID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_wars_scores WHERE wID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE warID = '.$id);
		$db->query('SELECT filename FROM '.DB_PRE.'ecp_wars_screens WHERE wID = '.$id);
		while($row = $db->fetch_assoc()) 
			@unlink('images/screens/'.$row['filename']);
		$db->query('DELETE FROM '.DB_PRE.'ecp_wars_screens WHERE wID = '.$id);
		return true;
	} else {
		return false;
	}
}
function forum_make_date($zeit) {
	if($zeit == 0) return 0;
	if(date('d.m.Y') == date('d.m.Y', $zeit)) {
		return '<strong>'.TODAY.'</strong>, '.date('H:i', $zeit);
	} elseif (date('d.m.Y', time()-86400) == date('d.m.Y', $zeit)) {
		return '<strong>'.YESTERDAY.'</strong>, '.date('H:i', $zeit);
	} else {
		return date(SHORT_DATE, $zeit);
	}
}
function find_access($felder) {
	if($felder == '') return true;
	$felder = explode(',',$felder);
	foreach($felder AS $val) {
		if(array_key_exists((int)$val, $_SESSION['groups1'])) {
			return true;
		}
	}
	return false;
}
function getMimeType($filename, $filename2) 
{ 
    if (extension_loaded('Fileinfo')) 
    { 
        $finfo = finfo_open(FILEINFO_MIME); 
        $mimetype = finfo_file($finfo, $filename); 
        finfo_close($finfo); 
        return $mimetype; 
    } 
    else 
    { 
        $filename = $filename2;
    	$filetype = strtolower(strrchr($filename, ".")); 

        switch ($filetype) 
        { 
   case ".zip": $mime="application/zip"; break; 
   case ".rar": $mime="application/x-rar-compressed"; break; 
   case ".ez":  $mime="application/andrew-inset"; break; 
   case ".hqx": $mime="application/mac-binhex40"; break; 
   case ".cpt": $mime="application/mac-compactpro"; break; 
   case ".doc": $mime="application/msword"; break; 
   case ".bin": $mime="application/octet-stream"; break; 
   case ".dms": $mime="application/octet-stream"; break; 
   case ".lha": $mime="application/octet-stream"; break; 
   case ".lzh": $mime="application/octet-stream"; break; 
   case ".exe": $mime="application/octet-stream"; break; 
   case ".class": $mime="application/octet-stream"; break; 
   case ".so":  $mime="application/octet-stream"; break; 
   case ".dll": $mime="application/octet-stream"; break; 
   case ".oda": $mime="application/oda"; break; 
   case ".pdf": $mime="application/pdf"; break; 
   case ".ai":  $mime="application/postscript"; break; 
   case ".eps": $mime="application/postscript"; break; 
   case ".ps":  $mime="application/postscript"; break; 
   case ".smi": $mime="application/smil"; break; 
   case ".smil": $mime="application/smil"; break; 
   case ".xls": $mime="application/vnd.ms-excel"; break; 
   case ".ppt": $mime="application/vnd.ms-powerpoint"; break; 
   case ".wbxml": $mime="application/vnd.wap.wbxml"; break; 
   case ".wmlc": $mime="application/vnd.wap.wmlc"; break; 
   case ".wmlsc": $mime="application/vnd.wap.wmlscriptc"; break; 
   case ".bcpio": $mime="application/x-bcpio"; break; 
   case ".vcd": $mime="application/x-cdlink"; break; 
   case ".pgn": $mime="application/x-chess-pgn"; break; 
   case ".cpio": $mime="application/x-cpio"; break; 
   case ".csh": $mime="application/x-csh"; break; 
   case ".dcr": $mime="application/x-director"; break; 
   case ".dir": $mime="application/x-director"; break; 
   case ".dxr": $mime="application/x-director"; break; 
   case ".dvi": $mime="application/x-dvi"; break; 
   case ".spl": $mime="application/x-futuresplash"; break; 
   case ".gtar": $mime="application/x-gtar"; break; 
   case ".hdf": $mime="application/x-hdf"; break; 
   case ".js":  $mime="application/x-javascript"; break; 
   case ".skp": $mime="application/x-koan"; break; 
   case ".skd": $mime="application/x-koan"; break; 
   case ".skt": $mime="application/x-koan"; break; 
   case ".skm": $mime="application/x-koan"; break; 
   case ".latex": $mime="application/x-latex"; break; 
   case ".nc":  $mime="application/x-netcdf"; break; 
   case ".cdf": $mime="application/x-netcdf"; break; 
   case ".sh":  $mime="application/x-sh"; break; 
   case ".shar": $mime="application/x-shar"; break; 
   case ".swf": $mime="application/x-shockwave-flash"; break; 
   case ".sit": $mime="application/x-stuffit"; break; 
   case ".sv4cpio": $mime="application/x-sv4cpio"; break; 
   case ".sv4crc": $mime="application/x-sv4crc"; break; 
   case ".tar": $mime="application/x-tar"; break; 
   case ".tcl": $mime="application/x-tcl"; break; 
   case ".tex": $mime="application/x-tex"; break; 
   case ".texinfo": $mime="application/x-texinfo"; break; 
   case ".texi": $mime="application/x-texinfo"; break; 
   case ".t":   $mime="application/x-troff"; break; 
   case ".tr":  $mime="application/x-troff"; break; 
   case ".roff": $mime="application/x-troff"; break; 
   case ".man": $mime="application/x-troff-man"; break; 
   case ".me":  $mime="application/x-troff-me"; break; 
   case ".ms":  $mime="application/x-troff-ms"; break; 
   case ".ustar": $mime="application/x-ustar"; break; 
   case ".src": $mime="application/x-wais-source"; break; 
   case ".xhtml": $mime="application/xhtml+xml"; break; 
   case ".xht": $mime="application/xhtml+xml"; break; 
   case ".zip": $mime="application/zip"; break; 
   case ".au":  $mime="audio/basic"; break; 
   case ".snd": $mime="audio/basic"; break; 
   case ".mid": $mime="audio/midi"; break; 
   case ".midi": $mime="audio/midi"; break; 
   case ".kar": $mime="audio/midi"; break; 
   case ".mpga": $mime="audio/mpeg"; break; 
   case ".mp2": $mime="audio/mpeg"; break; 
   case ".mp3": $mime="audio/mpeg"; break; 
   case ".aif": $mime="audio/x-aiff"; break; 
   case ".aiff": $mime="audio/x-aiff"; break; 
   case ".aifc": $mime="audio/x-aiff"; break; 
   case ".m3u": $mime="audio/x-mpegurl"; break; 
   case ".ram": $mime="audio/x-pn-realaudio"; break; 
   case ".rm":  $mime="audio/x-pn-realaudio"; break; 
   case ".rpm": $mime="audio/x-pn-realaudio-plugin"; break; 
   case ".ra":  $mime="audio/x-realaudio"; break; 
   case ".wav": $mime="audio/x-wav"; break; 
   case ".pdb": $mime="chemical/x-pdb"; break; 
   case ".xyz": $mime="chemical/x-xyz"; break; 
   case ".bmp": $mime="image/bmp"; break; 
   case ".gif": $mime="image/gif"; break; 
   case ".ief": $mime="image/ief"; break; 
   case ".jpeg": $mime="image/jpeg"; break; 
   case ".jpg": $mime="image/jpeg"; break; 
   case ".jpe": $mime="image/jpeg"; break; 
   case ".png": $mime="image/png"; break; 
   case ".tiff": $mime="image/tiff"; break; 
   case ".tif": $mime="image/tiff"; break; 
   case ".djvu": $mime="image/vnd.djvu"; break; 
   case ".djv": $mime="image/vnd.djvu"; break; 
   case ".wbmp": $mime="image/vnd.wap.wbmp"; break; 
   case ".ras": $mime="image/x-cmu-raster"; break; 
   case ".pnm": $mime="image/x-portable-anymap"; break; 
   case ".pbm": $mime="image/x-portable-bitmap"; break; 
   case ".pgm": $mime="image/x-portable-graymap"; break; 
   case ".ppm": $mime="image/x-portable-pixmap"; break; 
   case ".rgb": $mime="image/x-rgb"; break; 
   case ".xbm": $mime="image/x-xbitmap"; break; 
   case ".xpm": $mime="image/x-xpixmap"; break; 
   case ".xwd": $mime="image/x-xwindowdump"; break; 
   case ".igs": $mime="model/iges"; break; 
   case ".iges": $mime="model/iges"; break; 
   case ".msh": $mime="model/mesh"; break; 
   case ".mesh": $mime="model/mesh"; break; 
   case ".silo": $mime="model/mesh"; break; 
   case ".wrl": $mime="model/vrml"; break; 
   case ".vrml": $mime="model/vrml"; break; 
   case ".css": $mime="text/css"; break; 
   case ".html": $mime="text/html"; break; 
   case ".htm": $mime="text/html"; break; 
   case ".asc": $mime="text/plain"; break; 
   case ".txt": $mime="text/plain"; break; 
   case ".rtx": $mime="text/richtext"; break; 
   case ".rtf": $mime="text/rtf"; break; 
   case ".sgml": $mime="text/sgml"; break; 
   case ".sgm": $mime="text/sgml"; break; 
   case ".tsv": $mime="text/tab-separated-values"; break; 
   case ".wml": $mime="text/vnd.wap.wml"; break; 
   case ".wmls": $mime="text/vnd.wap.wmlscript"; break; 
   case ".etx": $mime="text/x-setext"; break; 
   case ".xml": $mime="text/xml"; break; 
   case ".xsl": $mime="text/xml"; break; 
   case ".mpeg": $mime="video/mpeg"; break; 
   case ".mpg": $mime="video/mpeg"; break; 
   case ".mpe": $mime="video/mpeg"; break; 
   case ".qt":  $mime="video/quicktime"; break; 
   case ".mov": $mime="video/quicktime"; break; 
   case ".mxu": $mime="video/vnd.mpegurl"; break; 
   case ".avi": $mime="video/x-msvideo"; break; 
   case ".movie": $mime="video/x-sgi-movie"; break; 
   case ".asf": $mime="video/x-ms-asf"; break; 
   case ".asx": $mime="video/x-ms-asf"; break; 
   case ".wm":  $mime="video/x-ms-wm"; break; 
   case ".wmv": $mime="video/x-ms-wmv"; break; 
   case ".wvx": $mime="video/x-ms-wvx"; break; 
   case ".ice": $mime="x-conference/x-cooltalk"; break; 
        } 

        return @$mime; 
    } 
} 
function html_ajax_convert($str) {
	return iconv("ISO-8859-1","utf-8//IGNORE",$str);
}
function ajax_html_convert($str) {
	return iconv("utf-8","ISO-8859-1//IGNORE",$str);
}
function message_send($to, $from, $title, $msg, $save = 1, $system = 0) {
	global  $db;
	if($system) {
		if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_messages (`touser`, `title`, `msg`, `fromdel`, `datum`) VALUES (%d, \'%s\', \'%s\', 1, %d)', (int)$to, strsave($title), strsave($msg), time()))) {
			$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET msg_r = msg_r + 1 WHERE userID = '.(int)$to);
			return true;
		} else {
			return false;
		}
	} else {
		if($db->query(sprintf('INSERT INTO '.DB_PRE.'ecp_messages (`touser`, `title`, `msg`, `fromdel`, `datum`, fromuser) VALUES (%d, \'%s\', \'%s\', 0, %d, %d)', (int)$to, strsave(htmlspecialchars($title)), strsave(comment_save($msg)), time(), (int)$from))) {
			$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET msg_r = msg_r + 1 WHERE userID = '.(int)$to);
			$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET msg_s = msg_s + 1 WHERE userID = '.(int)$from);
			return true;
		} else {
			return false;
		}	
	}
}
function makepagelink($link, $page, $pages) {
	     $page_link = "";
	     if($page!=1) $page_link .= "&nbsp;<a href=\"$link&page=1\">&laquo;</a>&nbsp;&nbsp;<a href=\"$link&page=".($page-1)."\">‹</a>";
	     if($page>=5) $page_link .= "&nbsp;<a href=\"$link&page=".($page-4)."\">...</a>";
	     if($page+3>=$pages) $pagex=$pages;
	     else $pagex=$page+3;
	     for($i=$page-3 ; $i<=$pagex ; $i++) {
		 if($i<=0) $i=1;
		 if($i==$page) $page_link .= " ($i)";
		 else $page_link .= "&nbsp;<a href=\"$link&page=$i"."\">$i</a>";
	     }
	     if(($pages-$page)>=4) $page_link .= "&nbsp;<a href=\"$link&page=".($page+4)."\">...</a>";
	     if($page!=$pages) $page_link .= "&nbsp;<a href=\"$link&page=".($page+1)."\">›</a>&nbsp;<a href=\"$link&page=".$pages."\">&raquo;</a>";
	     return $page_link;
}
function get_forum_rating($wert) {
	$array = explode('.', $wert);
	if(isset($array[1])) {
		if((float)('0.'.$array[1]) < 0.25) {
		    return (int)($array[0]);
		} elseif ((float)('0.'.$array[1]) >= 0.75) {
			return (int)(++$array[0]);
		} else {
			return (float)($array[0].'.5');
		}
	} else {
		return (int)$wert;
	}
}
function update_server_cache($ignoretime = false) {
	global $db;
	if($ignoretime) {
		$result = $db->query('SELECT serverID, gametype, ip, port, queryport, sport FROM '.DB_PRE.'ecp_server WHERE aktiv = 1 AND stat = 1');
	} else {
		$result = $db->query('SELECT serverID, gametype, ip, port, queryport, sport FROM '.DB_PRE.'ecp_server WHERE aktiv = 1 AND datum < '.(time()-SERVER_CACHE_REFRESH));		
	}
	while($row = mysql_fetch_assoc($result)) {
		$response = lgsl_query_live($row['gametype'], $row['ip'], $row['port'], $row['queryport'], $row['sport'], 'spe');
		if($response['s']['game'] == "Call of Duty: World at War") $response['s']['game'] = $row['gametype'];
		$db->query('UPDATE '.DB_PRE.'ecp_server SET response = \''.strsave(serialize($response)).'\', datum = '.time().' WHERE serverID = '.$row['serverID']);
	}
}
function check_str_length($str, $maxlength) {
   $length = strlen($str);
   IF($length >= $maxlength) {
        return substr($str, 0, floor($maxlength/1.5)).'...'.substr($str, ceil($length-($maxlength/3)));
    } else {
       return $str;
    }
}
function lgsl_server_html($server)
{
	if (isset($server['s']))
	{
		foreach ($server['s'] as $key => $value)
		{
			$server['s'][$key] = lgsl_string_html($value);
		}
	}

	if (isset($server['e']))
	{
		foreach ($server['e'] as $key => $value)
		{
			$value = wordwrap($value, 90, "\x00\x01", TRUE);    // \x00\x01 PLACEHOLDER FOR <BR /> TO PREVENT IT BEING ENTITIED
			$value = lgsl_string_html($value);
			$value = str_replace("\x00\x01", "<br />", $value); // CHANGE PLACEHOLDER INTO ACTUALY <BR />

			$server['e'][$key] = $value;
		}
	}

	if (isset($server['p']))
	{
		foreach ($server['p'] as $key => $player)
		{
			@$server['p'][$key]['name'] = lgsl_string_html($player['name']);
		}
	}

	return $server;
}
function lgsl_string_html($string)
{
	if (function_exists("mb_convert_encoding")) // REQUIRES http://php.net/mbstring
	{
		$string = htmlspecialchars($string, ENT_QUOTES);
		$string = @mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");
	}
	else
	{
		$string = htmlentities($string, ENT_QUOTES);
	}

	return $string;
}
function lang_changer() {
	$langs = get_languages();
	foreach($langs AS $value) {
		echo '<a href="?'.$_SERVER['QUERY_STRING'].'&amp;changelang='.$value['lang'].'"><img src="images/flaggen/'.$value['lang'].'.gif" alt="'.$value['name'].'" title="'.$value['name'].'" /></a> ';
	}
}
function get_random_pic() {
	global $db;
	if(@$_SESSION['rights']['public']['gallery']['view'] OR @$_SESSION['rights']['superadmin']) {
		$tpls =new smarty();
		$pic = $db->fetch_assoc('SELECT imageID, gID, filename,	a.beschreibung, folder FROM '.DB_PRE.'ecp_gallery_images as a LEFT JOIN '.DB_PRE.'ecp_gallery as b ON (gID= galleryID) LEFT JOIN '.DB_PRE.'ecp_gallery_kate as c ON (cID = kateID) WHERE (b.access ="" OR '.str_replace('access', 'b.access', $_SESSION['access_search']).') AND (c.access = "" OR '.str_replace('access', 'c.access', $_SESSION['access_search']).') ORDER BY rand() LIMIT 1');
		if(isset($pic['imageID'])) {
			foreach($pic AS $key=>$value) $tpls->assign($key, $value);
			if(!isset($_GET['rand_ajax'])) {
				ob_start();
				$tpls->display(DESIGN.'/tpl/modul/randpic.html');
				$content = ob_get_contents();
				ob_end_clean();
				echo '<div id="random_pic">'.$content.'</div>';
			}
			else 
				$tpls->display(DESIGN.'/tpl/modul/randpic.html');
		} else {
			echo NO_ENTRIES;
		}	
	} else {
		echo NO_ACCESS_RIGHTS;
	}	
}
function alter($gebd,$gebm,$geby){
   return checkdate($gebm,$gebd,$geby) ? (((date("m")-$gebm) < 0) || ((date("d")-$gebd == 0) && (date("d")-$gebd < 0)) ? date("Y")-$geby-1 : date("Y")-$geby): false;
}    
function goodtime($time, $art = 3) {
	$tage = floor($time/86400);
	$time -= $tage*86400;
	$stunden = floor($time/3600);
	$time -= $stunden*3600;
	$minuten = floor($time/60);
	$time -= $minuten*60;
	IF($stunden < 10) { $stunden = '0'.$stunden;}
	IF($minuten < 10) { $minuten = '0'.$minuten;}
	IF($time < 10) { $time = '0'.$time;}
	IF ($art == 3) {
		return $stunden.':'.$minuten.':'.$time;
	} elseif ($art == 4) {
		return $tage.'d '.$stunden.'h '.$minuten.'m';
	} elseif ($art == 1) {
		return $tage.'d '.$stunden.'h '.$minuten.'m '.$time.' s';
	} else {
		return $minuten.':'.$time;
	}
}
function check_url_length($url) {
	$url = check_url($url);
	$length = strlen($url);
	$maxlength = 30;
	IF($length >= $maxlength) {
		$url = '<a href="'.$url.'" target="_blank">'.substr($url, 0, floor($maxlength/1.5)).'...'.substr($url, ceil($length-($maxlength/3))).'</a>';
	} else {
		$url = '<a href="'.$url.'" target="_blank">'.str_replace('http://', '', $url).'</a>';
	}
	return $url;
}
function update_all_ranks() {
	global $db;
	$result = $db->query('SELECT ID FROM '.DB_PRE.'ecp_user');
	while($row = mysql_fetch_assoc($result)) {
		update_rank($row['ID']);
	}
}
function update_rank($id) {
	global $db;
	$rank = $db->fetch_assoc('SELECT rID, fest, rankID, comments FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_ranks ON rankID = rID LEFT JOIN '.DB_PRE.'ecp_user_stats ON userID = ID WHERE ID = '.$id);
	if($rank['fest'] != 1) {
		$newid = @$db->result(DB_PRE.'ecp_ranks', 'rankID', 'fest = 0 AND abposts <= '.$rank['comments'].' ORDER BY abposts DESC LIMIT 1');
		if((int)$newid) {
			$db->query('UPDATE '.DB_PRE.'ecp_user SET rID= '.$newid.' WHERE ID = '.$id);
		}
	}
}
function lotto_get_next_time() {
	global $db;
	$db->query('SELECT wochentag, uhrzeit FROM '.DB_PRE.'ecp_lotto_zeiten');
    IF($db->num_rows() > 1) {
        while($row = $db->fetch_assoc()) {
             @$zeiten[] = strtotime(switch_wday($row['wochentag']).' '.$row['uhrzeit']);
        }
        sort($zeiten);
        return $zeiten[1];
    } else {
        $row = $db->fetch_assoc();
        $time = strtotime('next '.switch_wday($row['wochentag']).' '.$row['uhrzeit']);
        if(date('d.m.Y') == date('d.m.Y', $time)) $time += 7*86400;
        return $time;
    }
}
function switch_wday($wkday) {
    switch($wkday) {
        case "1":
            return 'Monday';
        break;
        case "2":
            return 'Tuesday';
        break;
        case "3":
            return 'Wednesday';
        break;
        case "4":
            return 'Thursday';
        break;
        case "5":
            return 'Friday';
        break;
        case "6":
            return 'Saturday';
        break;
        case "0":
        	return 'Sunday';
		break;
	}
}
function switch_wday_lang($wkday) {
    switch($wkday) {
        case "1":
            return MONDAY;
        break;
        case "2":
            return TUESDAY;
        break;
        case "3":
            return WEDNESDAY;
        break;
        case "4":
            return THURSDAY;
        break;
        case "5":
            return FRIDAY;
        break;
        case "6":
            return SATURDAY;
        break;
        case "0":
        	return SUNDAY;
		break;
	}
}
function lotto_runde_ende() {
	global $db;
	$runde = $db->result(DB_PRE.'ecp_lotto_runden', 'rundenID', '1 ORDER BY ende DESC LIMIT 1');
	if($runde) {
		$zahlen = array();
		while(count($zahlen) < 4) {
			$zahl = rand(1,24);
			if(in_array($zahl, $zahlen)) continue;
			$zahlen[] = $zahl;
		}
		sort($zahlen);
		$config = $db->fetch_assoc('SELECT jackpot, pro4er,	pro3er,	pro2er,	jackpotraise FROM '.DB_PRE.'ecp_lotto');
		$result = $db->query('SELECT `scheinID`, `userID`, `rundenID`, `datum`, `zahl1`, `zahl2`, `zahl3`, `zahl4` FROM '.DB_PRE.'ecp_lotto_scheine WHERE (zahl1 IN ('.implode(',', $zahlen).') OR zahl2 IN ('.implode(',', $zahlen).') OR zahl3 IN ('.implode(',', $zahlen).') OR zahl4 IN ('.implode(',', $zahlen).')) AND rundenID = '.$runde);
		if($db->num_rows()) {
        	$gewinner = 0;
        	while($row = mysql_fetch_assoc($result)) {
            	$richtige = 0;
            	for($i=1; $i<=4;$i++) {
                	IF (in_array($row['zahl'.$i],$zahlen)) {
                 	   $richtige++;
	                }
            	}
            	switch($richtige) {
                	case 2:
                    	@$zweier[] = $row;
                    	$gewinner++;
                	break;
                	case 3:
                    	@$dreier[] = $row;
                    	$gewinner++;
               	 	break;
                	case 4:
                    	@$vierer[] = $row;
                    	$gewinner++;
               	 	break;
            	}
        	}
	        $abzug = 0;
	        $anzahl2er = 0;
	        $anzahl3er = 0;
	        $anzahl4er = 0;
	        $geld2er = $config['jackpot']/100 * $config['pro2er'];
	        $geld3er = $config['jackpot']/100 * $config['pro3er'];
	        $geld4er = $config['jackpot']/100 * $config['pro4er'];
	        IF ($gewinner) {
	            IF(isset($zweier)) {
	                $anzahl2er = count($zweier);
	                for($i=0;$i<=$anzahl2er-1;$i++) {
	                    //User Gewinn gutschreiben und in DB eintragen
	                    lotto_result($zweier[$i]['userID'], $geld2er/$anzahl2er, $zweier[$i]['scheinID'], $runde, 2, $zweier[$i]['datum'], $zweier[$i]['zahl1'],  $zweier[$i]['zahl2'],  $zweier[$i]['zahl3'],  $zweier[$i]['zahl4'], $zahlen);
	                }
	                $abzug += $geld2er;
	            }
	            IF (isset($dreier)) {
	                $anzahl3er = count($dreier);
	                for($i=0;$i<=$anzahl3er-1;$i++) {
	                    //User Gewinn gutschreiben und in DB eintragen
	                    lotto_result($dreier[$i]['userID'], $geld3er/$anzahl3er, $dreier[$i]['scheinID'], $runde, 3, $dreier[$i]['datum'], $dreier[$i]['zahl1'],  $dreier[$i]['zahl2'],  $dreier[$i]['zahl3'],  $dreier[$i]['zahl4'], $zahlen);
	                }
	                $abzug += $geld3er;
	            }
	            IF (isset($vierer)) {
	                $anzahl4er = count($vierer);
	                for($i=0;$i<=$anzahl4er-1;$i++) {
	                    //User Gewinn gutschreiben und in DB eintragen
	                    lotto_result($vierer[$i]['userID'], $geld4er/$anzahl4er, $vierer[$i]['scheinID'], $runde, 4, $vierer[$i]['datum'], $vierer[$i]['zahl1'],  $vierer[$i]['zahl2'],  $vierer[$i]['zahl3'],  $vierer[$i]['zahl4'], $zahlen);
	                }
	                $abzug += $geld4er;
	            }
	        }
		} 
		$db->query('UPDATE '.DB_PRE.'ecp_lotto SET jackpot = jackpot - '.(float)@$abzug.' + '.(float)@$config['jackpotraise']);		
		$db->query(sprintf('UPDATE '.DB_PRE.'ecp_lotto_runden SET rundenjackpot = %f, auszahlung = %f, 
															zahl1 = %d, zahl2 = %d, zahl3 = %d, zahl4 = %d,
	                     									4er = %d, 3er = %d, 2er = %d, 
	                     									geld4er = %f, geld3er = %f, geld2er = %f, ende = %d 
	                WHERE rundenID = %d', $config['jackpot'], @$abzug, $zahlen[0],$zahlen[1],$zahlen[2],$zahlen[3], @$anzahl4er, @$anzahl3er, @$anzahl2er, @$geld4er,@$geld3er,@$geld2er, time()-1, $runde));		
	}
}
    function lotto_result($userID, $gewinn, $scheinID, $rundenID, $art, $datum, $zahl1, $zahl2, $zahl3, $zahl4, $zahlen) {
        global $db;
        IF(in_array($zahl1, $zahlen)) {$zahl1 = '<strong>'.$zahl1.'</strong>';}
        IF(in_array($zahl2, $zahlen)) {$zahl2 = '<strong>'.$zahl2.'</strong>';}
        IF(in_array($zahl3, $zahlen)) {$zahl3 = '<strong>'.$zahl3.'</strong>';}
        IF(in_array($zahl4, $zahlen)) {$zahl4 = '<strong>'.$zahl4.'</strong>';}
		$db->query('SELECT * FROM '.DB_PRE.'ecp_texte WHERE name = "LOTTO_WIN"');
		$text = array();
		while($row = $db->fetch_assoc()) {
			$text[$row['lang']] = $row;								
		}
		$row = $db->fetch_assoc('SELECT country FROM '.DB_PRE.'ecp_user WHERE ID = '.$userID);
		$search = array('{datum}', '{richtige}','{zahlen}','{tippzahlen}','{gewinn}','{rundenid}');
		$replace = array(date(LONG_DATE, $datum), $art, implode(',', $zahlen), "$zahl1, $zahl2, $zahl3, $zahl4", format_nr($gewinn, 2).' '.VIRTUELL_MONEY_UNIT, $rundenID);
		if(!isset($text[$row['country']]))	$row['country'] = DEFAULT_LANG;
		message_send($userID, 0, $text[$row['country']]['content2'], str_replace($search, $replace, $text[$row['country']]['content']), 0, 1);							                        
        $db->query('INSERT INTO '.DB_PRE.'ecp_lotto_gewinner (`userID`, `rID`, `sID`, `gewinn`, `art`)
                     VALUES ('.$userID.','.$rundenID.', '.$scheinID.','.$gewinn.', '.$art.')');
        $db->query('UPDATE '.DB_PRE.'ecp_user_stats SET money = money + '.$gewinn.', '.$art.'er = '.$art.'er + 1 WHERE userID = '.$userID);
    }
function lotto_runde_start() {
	global $db;
	$db->query('INSERT INTO '.DB_PRE.'ecp_lotto_runden (`anfang`, `ende`) VALUES ('.time().', '.lotto_get_next_time().')');
}
function delete_user($id) {
	global $db;
	if($db->result(DB_PRE.'ecp_user', 'COUNT(ID)', 'ID = '.$id)) {
		$data = $db->fetch_assoc('SELECT username, avatar, user_pic FROM '.DB_PRE.'ecp_user WHERE ID = '.$id);
		if($data['avatar'] != '') @unlink('images/avatar/'.$id.'_'.$data['avatar']);
		if($data['user_pic'] != '') @unlink('images/avatar/'.$id.'_'.$data['user_pic']);
		$username = $data['username'];
		$newid = $db->result(DB_PRE.'ecp_user_groups', 'userID', 'gID = 1 AND userID != '.$id.' ORDER BY userID ASC');
		if(!$newid) $newid = 1;
		$db->query('DELETE FROM '.DB_PRE.'ecp_buddy WHERE userID = '.$id.' OR buddyID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_clankasse_member WHERE userID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_comments WHERE userID = '.$id.' OR (bereich = "user" AND subID = '.$id.')');
		$db->query('DELETE FROM '.DB_PRE.'ecp_forum_abo WHERE userID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_forum_attachments SET userID = 0 WHERE userID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_forum_boards SET lastpostuser = \''.strsave($username).'\', lastpostuserID = 0 WHERE lastpostuserID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET vonname = \''.strsave($username).'\', vonID = 0 WHERE vonID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_forum_comments SET postname = \''.strsave($username).'\', userID = 0 WHERE userID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_forum_threads SET lastusername = \''.strsave($username).'\', lastuserID = 0 WHERE lastuserID = '.$id); 
		$db->query('DELETE FROM '.DB_PRE.'ecp_lotto_scheine WHERE userID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_members WHERE userID = '.$id);
		$db->query('UPDATE '.DB_PRE.'ecp_news SET userID = '.$newid.' WHERE userID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_online WHERE uID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_user WHERE ID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_bans WHERE userID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_codes WHERE userID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_config WHERE userID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_groups WHERE userID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_lastvisits WHERE userID = '.$id.' OR visitID = '.$id);		
		$db->query('DELETE FROM '.DB_PRE.'ecp_user_stats WHERE userID = '.$id);
		$db->query('DELETE FROM '.DB_PRE.'ecp_wars_teilnehmer WHERE userID = '.$id);
		if($db->errorNum()) {
			return false;
		} else {
			return true;
		}
	} 
	return true;
}
function nulluhr() {
	global $db;
	update_all_ranks();
	$db->query('DELETE FROM '.DB_PRE.'ecp_messages WHERE fromdel = 1 AND del = 1');
	$result = $db->query('SELECT ID FROM '.DB_PRE.'ecp_user WHERE (ondelete < '.time().' AND ondelete != 0) OR (status = 0 AND registerdate < '.(time()-DELETE_UNAKTIV*86400).')');
	while($row = mysql_fetch_assoc($result)) {
		delete_user($row['ID']);
	}
	$result = $db->query('SELECT ID, money FROM '.DB_PRE.'ecp_user LEFT JOIN '.DB_PRE.'ecp_ranks ON (rID = rankID)');
	while($row = mysql_fetch_assoc($result)) {
		if($row['money'] != '')
		$db->query('UPDATE '.DB_PRE.'ecp_user_stats SET money = money + '.$row['money'].' WHERE userID = '.$row['ID']);
	}
	if(BACKUP_AKTIV) {
		$last = $db->result(DB_PRE.'ecp_stats', 'lastdbbackup', '1');
		if(BACKUP_CYCLE == 'day' OR ($last + (BACKUP_CYCLE == 'month' ? 2592000 : 604800) < time())) {
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
			$string = get_random_string(8, 2);
			if ($backup_obj->Execute(MSB_SAVE, 'uploads/forum/'.$string.'.sql.gz', true)) {
				$m = new XMail();
				// set from address and name
				$m->From(SITE_EMAIL);
				// add to address and name
				$m->AddTo(BACKUP_EMAIL);
				// set subject
				$m->Subject(BACKUP_AUTO);
				// set text/plain version of message
				$m->Text(DATE.': '.date('d.m.Y H:i:s'));
				// add attachment ('text/plain' file)
				$m->Attach(date('Y_m_d').'.sql.gz', 'application/x-gzip');
				$f = 'uploads/forum/'.$string.'.sql.gz';
				$id = MIME::unique();
				// add inline attachment '$f' file with ID '$id'
				$m->Attach(file_get_contents($f), FUNC::mime_type($f), null, null, null, 'attachment', $id);			
				if(SMTP_AKTIV) {
					$c = $m->Connect(SMTP_HOST, (int)SMTP_PORT, SMTP_USER, SMTP_PASS, 'tls', 10, 'localhost', null, 'plain'); //or die(print_r($m->Result));
				}		
			  if($m->Send(SMTP_AKTIV ? $c : null)) {
			  	$db->query('UPDATE '.DB_PRE.'ecp_stats SET lastdbbackup = '.strtotime('today 00:00:00'));
			  }
			  unlink('uploads/forum/'.$string.'.sql.gz');
			}
		}
	}

	$result = $db->query('SELECT attachID, strname FROM '.DB_PRE.'ecp_forum_attachments WHERE (tID = 0 OR bID = 0) AND uploadzeit < '.(time()-1000));
	while($row = $db->fetch_assoc()) {
		@unlink('upload/forum/'.$row['attachID'].'_'.$row['strname']);
	}
	$db->query('DELETE FROM '.DB_PRE.'ecp_forum_attachments WHERE (tID = 0 OR bID = 0) AND uploadzeit < '.(time()-1000));
    // Buchungen durchführen
    $buchresult = $db->query('SELECT `ID`, `verwendung`, `intervall`, `betrag`, `nextbuch`, `tagmonat` FROM '.DB_PRE.'ecp_clankasse_auto WHERE nextbuch <= \''.time().'\'');
    while($row = mysql_fetch_assoc($buchresult)) {
         $db->query('INSERT INTO '.DB_PRE.'ecp_clankasse_transaktion (`geld`, `verwendung`, `datum`, `userID`) VALUES
                 (-'.$row['betrag'].', \''.mysql_real_escape_string($row['verwendung']).'\', '.time().', 0)');
         $db->query('UPDATE '.DB_PRE.'ecp_clankasse SET kontostand = kontostand - '.$row['betrag']);
         switch($row['tagmonat']) {
             case 1:
                 $nextdate = strtotime('+ '.(int)$row['intervall'].' month');
             break;
             case 15:
                 $nextdate = strtotime('+ '.(int)$row['intervall'].' month');
             break;
             case 28:
                 $nextdate = strtotime('+ '.(int)$row['intervall'].' month');
         }
         $db->query('UPDATE '.DB_PRE.'ecp_clankasse_auto SET `nextbuch` = \''.$nextdate.'\'');
    }	
    $db->query('DELETE FROM '.DB_PRE.'ecp_forum_search WHERE datum < '.(time()-86400));
    $result = $db->query('SELECT COUNT(sID) as anzahl, sID FROM '.DB_PRE.'ecp_server_stats GROUP BY sID');
    while($row = mysql_fetch_assoc($result)) {
    	if($row['anzahl'] > SERVER_MAX_LOG)
    		$db->query('DELETE FROM '.DB_PRE.'ecp_server_stats WHERE sID = '.$row['sID'].' ORDER BY datum ASC LIMIT '.($row['anzahl'] - SERVER_MAX_LOG));
    }
}
function calendar_mini() {
global $db, $monatsnamen, $countries;
	if(!isset($_GET['month'])) $monat = date('m'); else $monat = (int)$_GET['month'];
	if(!isset($_GET['year'])) $jahr = date('Y'); else $jahr = (int)$_GET['year'];
	if($monat > 12) {
		$monat = 1;
		$jahr++;
	}
	if($monat <= 0) {
		$monat = 12;
		$jahr--;
	}
	if($jahr > 2034 OR $jahr < 1970) $jahr = date('Y');
	$tpl = new smarty;
	$wochentag = date('w', mktime(0, 0, 0, $monat, 1, $jahr));
	$woche = (int)date('W',  mktime(0, 0, 0, $monat, 1, $jahr));
	$tagemonat = date("t", mktime(0, 0, 0, $monat, 1, $jahr));
	$wochen = array();
	$wochen[] = array('woche' => $woche, 'akt' => '-1',  'events' => array());
	$start = mktime(0,0,0,$monat, 1, $jahr);
	$ende = mktime(23,59,59,$monat+1, 0, $jahr);
	// Kalander Anlegen //
	if($wochentag==0) {
		if($woche >= 52) $woche = date('W',  mktime(0, 0, 0, $monat, 2, $jahr))-1;
		$wochen[] = array('woche' => ++$woche, 'akt' => '-1', 'events' => array());
		next($wochen);
	}
	
	for($i=1;$i<=$tagemonat;$i++) {
		if($wochentag == 0) {
			$wochen[key($wochen)-1]['tage'][$wochentag] = $i;
			if($i === (int)date('d') AND $monat == date('m') AND $jahr == date('Y'))
			$wochen[key($wochen)-1]['akt'] = date('w');
		} else
		$wochen[key($wochen)]['tage'][$wochentag] = $i;
		$wochentag++;
		if($i === (int)date('d') AND $monat == date('m') AND $jahr == date('Y') AND date('w') != 0)
		$wochen[key($wochen)]['akt'] = date('w');
		if($wochentag > 6 AND $i<$tagemonat) {
			$woche++;
			if($woche >= 52) $woche = date('W',  mktime(0, 0, 0, $monat, $i+2, $jahr));
			$wochen[] = array('woche' => $woche, 'akt' => '-1', 'events' => array());
			$wochentag = 0;
			next($wochen);
		}
	}
	reset($wochen);
	// Kalender anlegen ende
	if(count($wochen[key($wochen)]['tage']) == 0) array_splice($wochen, key($wochen));
	$db->query('SELECT `warID`, '.DB_PRE.'ecp_wars.datum, `result`, `resultscore`, `tname`, `oppname`, `country`, '.DB_PRE.'ecp_wars_opp.homepage, `icon`, `gamename`, `matchtypename`, COUNT(comID) as comments, status 
				FROM '.DB_PRE.'ecp_wars 
				LEFT JOIN '.DB_PRE.'ecp_teams ON '.DB_PRE.'ecp_teams.tID = '.DB_PRE.'ecp_wars.tID 
				LEFT JOIN '.DB_PRE.'ecp_wars_games ON gID = gameID 
				LEFT JOIN '.DB_PRE.'ecp_wars_opp ON oID = oppID 
				LEFT JOIN '.DB_PRE.'ecp_wars_matchtype ON mID = matchtypeID 
				LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = warID AND bereich = "clanwars") 
				WHERE '.DB_PRE.'ecp_wars.datum BETWEEN '.$start.' AND '.$ende.'
				GROUP BY warID
				ORDER BY '.DB_PRE.'ecp_wars.datum ASC');
	$clanwars = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		($row['resultscore'] == '')  ? $row['resultscore'] = CLANWARS_OPEN : '';
		$row['countryname'] = $countries[$row['country']];
		if($lastday == date('d', $datum)) {
			$clanwars[] = $row;	
		} else {
			if(count($clanwars)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						$wochen[$key]['events'][date('w', $lastdatum)] =  make_cal_event($clanwars, 'wars');	
						break;
					}
				}
				$clanwars = array();	
			}
			$clanwars[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($clanwars)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				$wochen[$key]['events'][date('w', $datum)] = make_cal_event($clanwars, 'wars');	
				break;
			}
		}
	}
	//Geburtstage
	$db->query('SELECT username, country, ID, geburtstag, date_format(geburtstag, \'%Y\') AS jahr, date_format(geburtstag, \'%d\') AS tag
                    					FROM 
                    					    '.DB_PRE.'ecp_user
                    					WHERE 
                    					 	geburtstag != "00-00-0000" AND date_format(geburtstag, \'%m\') = '.$monat.' ORDER BY date_format(geburtstag, \'%d\') ASC');
	$birth = array();
	$lastday = 0;
	while($row = $db->fetch_assoc()) {
		$row['alter'] = $jahr - $row['jahr'];
		if($lastday == $row['tag']) {
			$birth[] = $row;	
		} else {
			if(count($birth)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', mktime(0,0,0,$monat, $lastday, $jahr))) {
						@$wochen[$key]['events'][date('w', mktime(0,0,0,$monat, $lastday, $jahr))] .= make_cal_event($birth, 'birth');	
						break;
					}
				}
				$birth = array();	
			}
			$birth[] = $row;
		}
		$lastday = $row['tag'];
	}
	if(count($birth)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', mktime(0,0,0,$monat, $lastday, $jahr))) {
				@$wochen[$key]['events'][date('w', mktime(0,0,0,$monat, $lastday, $jahr))] .=  make_cal_event($birth, 'birth');
				break;	
			}
		}
	}
	// News einfügen
	$db->query('SELECT `newsID`, a.`userID`, `topicID`, a.`datum`, `headline`,
						`username`, `topicname`, COUNT(comID) AS comments, country
						FROM '.DB_PRE.'ecp_news as a
						LEFT JOIN '.DB_PRE.'ecp_user ON (a.userID = ID)  
						LEFT JOIN '.DB_PRE.'ecp_topics ON (topicID = tID) 
						LEFT JOIN '.DB_PRE.'ecp_comments ON (subID = newsID AND bereich = "news")
						WHERE (lang = "" OR lang LIKE ",%'.LANGUAGE.'%,") AND a.datum < '.time().' AND a.datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') GROUP BY newsID ORDER BY datum ASC');		
	$news = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = $countries[$row['country']];
		if($lastday == date('d', $datum)) {
			$news[] = $row;	
		} else {
			if(count($news)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						@$wochen[$key]['events'][date('w', $lastdatum)] .=  make_cal_event($news, 'news');
						break;	
					}
				}
				$news = array();	
			}
			$news[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($news)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				@$wochen[$key]['events'][date('w', $datum)] .= make_cal_event($news, 'news');
				break;	
			}
		}
	}
	//Kalender Einträge hinzufügen
	$db->query('SELECT `calID`, `eventname`, `datum`, `inhalt`, `userID`, `username`, `country` FROM `'.DB_PRE.'ecp_calendar`
						LEFT JOIN '.DB_PRE.'ecp_user ON (userID = ID)  
						WHERE datum BETWEEN '.$start.' AND '.$ende.' AND (access = "" OR '.$_SESSION['access_search'].') ORDER BY datum ASC');		
	$events = array();
	$lastday = 0;
	$lastdatum = 0;
	while($row = $db->fetch_assoc()) {
		$datum = $row['datum'];
		$row['datum'] = date('H:i', $row['datum']);
		$row['countryname'] = @$countries[$row['country']];
		$row['inhalt'] = json_decode($row['inhalt'], true);
		(isset($row['inhalt'][LANGUAGE])) ? $row['inhalt'] = $row['inhalt'][LANGUAGE] :  $row['inhalt'] = $row['inhalt'][DEFAULT_LANG];
		if($lastday == date('d', $datum)) {
			$events[] = $row;	
		} else {
			if(count($events)) {
				foreach($wochen AS $key =>$value) {
					if($value['woche'] == date('W', $lastdatum)) {
						@$wochen[$key]['events'][date('w', $lastdatum)] .= make_cal_event($events, 'events');
						break;	
					}
				}
				$events = array();	
			}
			$events[] = $row;
		}
		$lastday = date('d', $datum);
		$lastdatum = $datum;
	}	
	if(count($events)) {
		foreach($wochen AS $key =>$value) {
			if($value['woche'] == date('W', $datum)) {
				@$wochen[$key]['events'][date('w', $datum)] .= make_cal_event($events, 'events');
				break;	
			}
		}
	}
	$tpl->assign('year', $jahr);
	$tpl->assign('monthz', $monat);
	$tpl->assign('month', $monatsnamen[(int)$monat]);
	$tpl->assign('kalender', $wochen);
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_mini.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
function make_cal_event($data, $datei) {
	$tpl = new smarty;
	$tpl->assign('data', $data);
	ob_start();
	$tpl->display(DESIGN.'/tpl/calendar/calendar_'.$datei.'_mini.html');
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
/* Alternative zu file_get_contents (fuer lokale Dateien und HTTP-Resourcen) 051017 */ 
function get_contents($file) { 
 // Leerer Inhalt als Default 
 $content=''; 
 // Erster Versuch: Datei "normal" oeffnen 
 $fh=fopen($file,'rb'); 
 if($fh) { 
  // Funktioniert: Datei einlesen 
  if(!is_url($file)) { 
   // Lokal ... 
   $content=fread($fh,filesize($file)); 
  } else { 
   // ... bzw. uebers Netz 
   while(!feof($fh)) { $content.=fread($fh,2048); } 
  } 
  fclose($fh); 
 } elseif(is_url($file,'http')) { 
  // Funktioniert nicht und Datei ist eine HTTP-Resource (externer Zugriff wurde ggf. verhindert) 
  // User & Passwort extrahieren 
  $userpass=get_url_userpass($file); 
  if($userpass) { $file=str_replace($userpass.'@','',$file); } 
  if(function_exists('curl_init')) { 
   // Alternativversuch mittels cURL-Lib (PHP >= 4.0.2) 
   $ch=curl_init($file); 
   if($ch) { 
    curl_setopt($ch,CURLOPT_HEADER,FALSE); 
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE); 
    curl_setopt($ch,CURLOPT_FORBID_REUSE,TRUE); 
    curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE); 
    curl_setopt($ch,CURLOPT_FAILONERROR,TRUE); 
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE); 
    curl_setopt($ch,CURLOPT_BINARYTRANSFER,TRUE); 
    if($userpass) { 
     curl_setopt($ch,CURLOPT_USERPWD,$userpass); 
     if(substr($file,0,4)=='http') { 
      curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY); 
     } 
    } 
    $content=curl_exec($ch); 
    curl_close($ch); 
   } 
  } else { 
   // Alternativversuch mittels socket connection (PHP < 4.0.2) 
   $host=get_url_hostname($file); 
   $port=get_url_port($file); 
   $sh=fsockopen($host,$port); 
   if($sh) { 
    $request ='GET '.get_url_filename($file,TRUE).' HTTP/1.0'."\r\n"; 
    $request.='Accept: */*'."\r\n"; 
    $request.='Host: '.$host.':'.$port."\r\n"; 
    if($userpass) { $request.='Authorization: Basic '.base64_encode($userpass)."\r\n"; } 
    $request.='Connection: Close'."\r\n"; 
    $request.="\r\n"; 
    fputs($sh,$request); 
    while(!feof($sh)) { $content.=fread($sh,2048); } 
    fclose($sh); 
    $header=substr($content,0,strpos($content,"\r\n\r\n")); 
    $redirect=strpos($header,'Location:'); 
    if($redirect!==FALSE) { 
     // Seitenweiterleitung, also neuer URL 
     $redirect=trim(substr($header,$redirect+9,strpos($header,"\r\n",$redirect)+2-($redirect+9))); 
     $content=(is_url($redirect))?get_contents($redirect):''; 
    } else { 
     // Header abschneiden 
     $content=substr($content,strpos($content,"\r\n\r\n")+4); 
    } 
   } 
  } 
 } 
 return $content; 
} 

// --------------------------------------------------------------------------- 

/* Ist String ein URI (mit erlaubtem Protokoll)? 051005 */ 
function is_url($url,$protocol_check=FALSE) { 
 $result=FALSE; 
 // URL zerlegen 
 $parts=parse_url($url); 
 // Protokoll und Servernamen extrahieren 
 $protocol=(empty($parts['scheme']))?FALSE:$parts['scheme']; 
 $server=(empty($parts['host']))?FALSE:$parts['host']; 
 // Sowohl Protokoll als auch Servername sind Pflicht 
 if($protocol && $server) { 
  // Wenn Protokoll nicht auf Gueltigkeit geprueft werden soll 
  if(!$protocol_check) { 
   // Protokoll zurueckgeben 
   $result=$protocol; 
  } else { 
   // Ansonsten Protokoll ueberpruefen 
   if($protocol_check===TRUE) { 
    // Erlaubte Default-Protokolle 
    $allowed_protocols=array('http','https'); 
   } elseif(is_string($protocol_check)) { 
    // Erlaubte Protokolle wurden als String uebergeben: 
    if(strpos($protocol_check,'|')===FALSE) { 
     // Als einzelnes Protokoll 
     $allowed_protocols=array($protocol_check); 
    } else { 
     // Als Pipe-separierte Liste 
     $allowed_protocols=explode('|',$protocol_check); 
    } 
   } elseif(is_array($protocol_check)) { 
    // Erlaubte Protokolle wurden als Array uebergeben 
    $allowed_protocols=$protocol_check; 
   } 
   $result=(in_array($protocol,$allowed_protocols))?$protocol:FALSE; 
  } 
 } 
 return $result; 
} 

// --------------------------------------------------------------------------- 

/* Port ermitteln (ggf. aus Liste einiger well-known ports) 051007 */ 
function get_url_port($url,$default=80) { 
 // URL zerlegen und ... 
 $parts=parse_url($url); 
 // ... Port extrahieren 
 $port=(!empty($parts['port']))?$parts['port']:FALSE; 
 // Das kann (wird i.d.R.) auch fehlschlagen 
 if($port===FALSE) { 
  // Ersatzweise Protokoll extrahieren ... 
  $protocol=(!empty($parts['scheme']))?$parts['scheme']:''; 
  // .. und den dafuer ueblichen Port verwenden 
  switch($protocol) { 
   case 'ftp'   : $port=21;  break; 
   case 'ssh'   : $port=22;  break; 
   case 'sftp'  : $port=22;  break; 
   case 'telnet': $port=23;  break; 
   case 'http'  : $port=80;  break; 
   case 'https' : $port=443; break; 
   default      : $port=$default; 
  } 
 } else { 
  $port=intval($port); 
 } 
 return $port; 
} 

// --------------------------------------------------------------------------- 

/* Servernamen aus URL holen 051007 */ 
function get_url_hostname($url,$set_protocol=FALSE) { 
 $hostname=''; 
 // Nur starten, wenn Parameter auch URL ist 
 if(is_url($url)) { 
  $parts=parse_url($url); 
  // Servernamen holen 
  $hostname=(isset($parts['host']))?$parts['host']:''; 
  // Und ggf. auch noch das Protokoll vorsetzen ("http://" als Default) 
  if($hostname && $set_protocol) { 
   $hostname=((!empty($parts['scheme']))?$parts['scheme']:'http').'://'.$hostname; 
  } 
 } 
 return $hostname; 
} 

// --------------------------------------------------------------------------- 

/* Dateinamen aus URL holen 051007 */ 
function get_url_filename($url,$append_query=FALSE) { 
 $filename=''; 
 // Nur starten, wenn Parameter auch URL ist 
 if(is_url($url)) { 
  $parts=parse_url($url); 
  // Dateipfad & -namen holen 
  $filename=(isset($parts['path']))?$parts['path']:'/'; 
  // Und ggf. auch noch den QUERY-String anhaengen 
  if($filename && $append_query) { 
   $filename.=(!empty($parts['query']))?('?'.$parts['query']):''; 
  } 
 } 
 return $filename; 
} 


// --------------------------------------------------------------------------- 

/* User/Passwort aus URL holen 051006 */ 
function get_url_userpass($url,$type='USER:PASS') { 
 $result=''; 
 $type=strtoupper($type); 
 $parts=parse_url($url); 
 // User holen 
 $user=(isset($parts['user']))?$parts['user']:''; 
 $pass=(isset($parts['pass']))?$parts['pass']:''; 
 // Rueckgabe zusammenstellen 
 switch($type) { 
  case 'USER': 
   $result=$user; 
   break; 
  case 'PASS': 
   $result=$pass; 
   break; 
  default: 
   $result=$user.(($user!=='' || $pass!=='')?':':'').$pass; 
 } 
 return $result; 
} 
 function makepagelink_ajax($link, $ajax, $page, $pages, $fontclass = 'pageLink', $aktivclass='aktivPageLink',  $sub = '') {
 	if(!$page) $page = 1;
	$page_link = "";
	if($page!=1) 
		$page_link .= " <a href=\"$link&page=1$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', 1,$ajax).'"' : '')." class=\"$fontclass\">&laquo;</a> <a href=\"$link&page=".($page-1)."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', ($page-1),$ajax).'"' : '')." class=\"$fontclass\" href=\"$link&page=".($page-1)."$sub\">&lsaquo;</a>";
	if($page>=5) 
		$page_link .= " <a href=\"$link&page=".($page-4)."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', ($page-4),$ajax).'"' : '')." class=\"$fontclass\">...</a>";
	if($page+3>=$pages) 
		$pagex=$pages;
	else 
		$pagex=$page+3;
	for($i=$page-3 ; $i<=$pagex ; $i++) {
		if($i<=0) $i=1;
		if($i==$page) $page_link .= " <a href=\"$link&page=$i"."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', $i,$ajax).'"' : '')." class=\"$aktivclass\">$i</a>";
		else $page_link .= " <a href=\"$link&page=$i"."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', $i,$ajax).'"' : '')." class=\"$fontclass\">$i</a>";
	}
	if(($pages-$page)>=4) 
		$page_link .= "&nbsp;<a href=\"$link&page=".($page+4)."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', ($page+4),$ajax).'"' : '')." class=\"$fontclass\">...</a>";
	if($page!=$pages) 
		$page_link .= "&nbsp;<a href=\"$link&page=".($page+1)."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', ($page+1),$ajax).'"' : '')." class=\"$fontclass\">&rsaquo;</a> <a href=\"$link&page=".$pages."$sub\" ".($ajax ? 'onclick="'.str_replace('{nr}', $pages,$ajax).'"' : '')." class=\"$fontclass\">&raquo;</a>";
	return $page_link;
}

/*
htmLawed 1.1.5, 31 January 2009
Copyright Santosh Patnaik
GPL v3 license
A PHP Labware internal utility; www.bioinformatics.org/phplabware/internal_utilities/htmLawed

See htmLawed_README.txt/.htm
*/

function htmLawed($t, $C=1, $spec=array()){
$C = is_array($C) ? $C : array();
if(!empty($C['valid_xhtml'])){
 $C['elements'] = empty($C['elements']) ? '*-center-dir-font-isindex-menu-s-strike-u' : $C['elements'];
 $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 2;
 $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 2;
}
// config eles
$e = array('a'=>1, 'abbr'=>1, 'acronym'=>1, 'address'=>1, 'applet'=>1, 'area'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'blockquote'=>1, 'br'=>1, 'button'=>1, 'caption'=>1, 'center'=>1, 'cite'=>1, 'code'=>1, 'col'=>1, 'colgroup'=>1, 'dd'=>1, 'del'=>1, 'dfn'=>1, 'dir'=>1, 'div'=>1, 'dl'=>1, 'dt'=>1, 'em'=>1, 'embed'=>1, 'fieldset'=>1, 'font'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'isindex'=>1, 'kbd'=>1, 'label'=>1, 'legend'=>1, 'li'=>1, 'map'=>1, 'menu'=>1, 'noscript'=>1, 'object'=>1, 'ol'=>1, 'optgroup'=>1, 'option'=>1, 'p'=>1, 'param'=>1, 'pre'=>1, 'q'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'script'=>1, 'select'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'table'=>1, 'tbody'=>1, 'td'=>1, 'textarea'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1, 'tt'=>1, 'u'=>1, 'ul'=>1, 'var'=>1); // 86/deprecated+embed+ruby
if(!empty($C['safe'])){
	unset($e['applet'], $e['embed'], $e['iframe'], $e['script']);	
 //unset($e['applet'], $e['embed'], $e['iframe'], $e['object'], $e['script']);
}
$x = !empty($C['elements']) ? str_replace(array("\n", "\r", "\t", ' '), '', $C['elements']) : '*';
if($x == '-*'){$e = array();}
elseif(strpos($x, '*') === false){$e = array_flip(explode(',', $x));}
else{
 if(isset($x[1])){
  preg_match_all('`(?:^|-|\+)[^\-+]+?(?=-|\+|$)`', $x, $m, PREG_SET_ORDER);
  for($i=count($m); --$i>=0;){$m[$i] = $m[$i][0];}
  foreach($m as $v){
   if($v[0] == '+'){$e[substr($v, 1)] = 1;}
   if($v[0] == '-' && isset($e[($v = substr($v, 1))]) && !in_array('+'. $v, $m)){unset($e[$v]);}
  }
 }
}
$C['elements'] =& $e;
// config denied attrs
$C['deny_attribute'] = !empty($C['deny_attribute']) ? array_flip(explode(',', str_replace(array("\n", "\r", "\t", ' '), '', $C['deny_attribute']. (!empty($C['safe']) ? ',on*' : '')))) : (!empty($C['safe']) ? array('on*'=>1) : array());
if(isset($C['deny_attribute']['on*'])){
 unset($C['deny_attribute']['on*']);
 $C['deny_attribute'] += array('onblur'=>1, 'onchange'=>1, 'onclick'=>1, 'ondblclick'=>1, 'onfocus'=>1, 'onkeydown'=>1, 'onkeypress'=>1, 'onkeyup'=>1, 'onmousedown'=>1, 'onmousemove'=>1, 'onmouseout'=>1, 'onmouseover'=>1, 'onmouseup'=>1, 'onreset'=>1, 'onselect'=>1, 'onsubmit'=>1);
}
// config URL
$x = (isset($C['schemes'][2]) && strpos($C['schemes'], ':')) ? strtolower($C['schemes']) : 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https';
$C['schemes'] = array();
foreach(explode(';', str_replace(array(' ', "\t", "\r", "\n"), '', $x)) as $v){
 $x = $x2 = null; list($x, $x2) = explode(':', $v, 2);
 if($x2){$C['schemes'][$x] = array_flip(explode(',', $x2));}
}
if(!isset($C['schemes']['*'])){$C['schemes']['*'] = array('file'=>1, 'http'=>1, 'https'=>1,);}
if(!empty($C['safe']) && empty($C['schemes']['style'])){$C['schemes']['style'] = array('nil'=>1);}
$C['abs_url'] = isset($C['abs_url']) ? $C['abs_url'] : 0;
if(!isset($C['base_url']) or !preg_match('`^[a-zA-Z\d.+\-]+://[^/]+/(.+?/)?$`', $C['base_url'])){
 $C['base_url'] = $C['abs_url'] = 0;
}
// config rest
$C['and_mark'] = empty($C['and_mark']) ? 0 : 1;
$C['anti_link_spam'] = (isset($C['anti_link_spam']) && is_array($C['anti_link_spam']) && count($C['anti_link_spam']) == 2 && (empty($C['anti_link_spam'][0]) or hl_regex($C['anti_link_spam'][0])) && (empty($C['anti_link_spam'][1]) or hl_regex($C['anti_link_spam'][1]))) ? $C['anti_link_spam'] : 0;
$C['anti_mail_spam'] = isset($C['anti_mail_spam']) ? $C['anti_mail_spam'] : 0;
$C['balance'] = isset($C['balance']) ? (bool)$C['balance'] : 1;
$C['cdata'] = isset($C['cdata']) ? $C['cdata'] : (empty($C['safe']) ? 3 : 0);
$C['clean_ms_char'] = empty($C['clean_ms_char']) ? 0 : $C['clean_ms_char'];
$C['comment'] = isset($C['comment']) ? $C['comment'] : (empty($C['safe']) ? 3 : 0);
$C['css_expression'] = empty($C['css_expression']) ? 0 : 1;
$C['hexdec_entity'] = isset($C['hexdec_entity']) ? $C['hexdec_entity'] : 1;
$C['hook'] = (!empty($C['hook']) && function_exists($C['hook'])) ? $C['hook'] : 0;
$C['hook_tag'] = (!empty($C['hook_tag']) && function_exists($C['hook_tag'])) ? $C['hook_tag'] : 0;
$C['keep_bad'] = isset($C['keep_bad']) ? $C['keep_bad'] : 6;
$C['lc_std_val'] = isset($C['lc_std_val']) ? (bool)$C['lc_std_val'] : 1;
$C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 1;
$C['named_entity'] = isset($C['named_entity']) ? (bool)$C['named_entity'] : 1;
$C['no_deprecated_attr'] = isset($C['no_deprecated_attr']) ? $C['no_deprecated_attr'] : 1;
$C['parent'] = isset($C['parent'][0]) ? strtolower($C['parent']) : 'body';
$C['show_setting'] = !empty($C['show_setting']) ? $C['show_setting'] : 0;
$C['tidy'] = empty($C['tidy']) ? 0 : $C['tidy'];
$C['unique_ids'] = isset($C['unique_ids']) ? $C['unique_ids'] : 1;
$C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 0;

if(isset($GLOBALS['C'])){$reC = $GLOBALS['C'];}
$GLOBALS['C'] = $C;
$spec = is_array($spec) ? $spec : hl_spec($spec);
if(isset($GLOBALS['spec'])){$reSpec = $GLOBALS['spec'];}
$GLOBALS['spec'] = $spec;

$t = preg_replace('`[\x00-\x08\x0b-\x0c\x0e-\x1f]`', '', $t);
if($C['clean_ms_char']){
 $x = array("\x7f"=>'', "\x80"=>'&#8364;', "\x81"=>'', "\x83"=>'&#402;', "\x85"=>'&#8230;', "\x86"=>'&#8224;', "\x87"=>'&#8225;', "\x88"=>'&#710;', "\x89"=>'&#8240;', "\x8a"=>'&#352;', "\x8b"=>'&#8249;', "\x8c"=>'&#338;', "\x8d"=>'', "\x8e"=>'&#381;', "\x8f"=>'', "\x90"=>'', "\x95"=>'&#8226;', "\x96"=>'&#8211;', "\x97"=>'&#8212;', "\x98"=>'&#732;', "\x99"=>'&#8482;', "\x9a"=>'&#353;', "\x9b"=>'&#8250;', "\x9c"=>'&#339;', "\x9d"=>'', "\x9e"=>'&#382;', "\x9f"=>'&#376;');
 $x = $x + ($C['clean_ms_char'] == 1 ? array("\x82"=>'&#8218;', "\x84"=>'&#8222;', "\x91"=>'&#8216;', "\x92"=>'&#8217;', "\x93"=>'&#8220;', "\x94"=>'&#8221;') : array("\x82"=>'\'', "\x84"=>'"', "\x91"=>'\'', "\x92"=>'\'', "\x93"=>'"', "\x94"=>'"'));
 $t = strtr($t, $x);
}
if($C['cdata'] or $C['comment']){$t = preg_replace_callback('`<!(?:(?:--.*?--)|(?:\[CDATA\[.*?\]\]))>`sm', 'hl_cmtcd', $t);}
$t = preg_replace_callback('`&amp;([A-Za-z][A-Za-z0-9]{1,30}|#(?:[0-9]{1,8}|[Xx][0-9A-Fa-f]{1,7}));`', 'hl_ent', str_replace('&', '&amp;', $t));
if($C['unique_ids'] && !isset($GLOBALS['hl_Ids'])){$GLOBALS['hl_Ids'] = array();}
if($C['hook']){$t = $C['hook']($t, $C, $spec);}
if($C['show_setting'] && preg_match('`^[a-z][a-z0-9_]*$`i', $C['show_setting'])){
 $GLOBALS[$C['show_setting']] = array('config'=>$C, 'spec'=>$spec, 'time'=>microtime());
}
// main
$t = preg_replace_callback('`<(?:(?:\s|$)|(?:[^>]*(?:>|$)))|>`m', 'hl_tag', $t);
$t = $C['balance'] ? hl_bal($t, $C['keep_bad'], $C['parent']) : $t;
$t = (($C['cdata'] or $C['comment']) && strpos($t, "\x01") !== false) ? str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05"), array('', '', '&', '<', '>'), $t) : $t;
$t = $C['tidy'] ? hl_tidy($t, $C['tidy'], $C['parent']) : $t;
unset($C, $e);
if(isset($reC)){$GLOBALS['C'] = $reC;}
if(isset($reSpec)){$GLOBALS['spec'] = $reSpec;}
return $t;
// eof
}

function hl_attrval($t, $p){
// check attr val against user spec
$o = 1; $l = strlen($t);
foreach($p as $k=>$v){
 switch($k){
  case 'maxlen':if($l > $v){$o = 0;}
  break; case 'minlen': if($l < $v){$o = 0;}
  break; case 'maxval': if((float)($t) > $v){$o = 0;}
  break; case 'minval': if((float)($t) < $v){$o = 0;}
  break; case 'match': if(!preg_match($v, $t)){$o = 0;}
  break; case 'nomatch': if(preg_match($v, $t)){$o = 0;}
  break; case 'oneof':
   $m = 0;
   foreach(explode('|', $v) as $n){if($t == $n){$m = 1; break;}}
   $o = $m;
  break; case 'noneof':
   $m = 1;
   foreach(explode('|', $v) as $n){if($t == $n){$m = 0; break;}}
   $o = $m;
  break; default:
  break;
 }
 if(!$o){break;}
}
return ($o ? $t : (isset($p['default']) ? $p['default'] : 0));
// eof
}

function hl_bal($t, $do=1, $in='div'){
// balance tags
// eles by content
$cB = array('blockquote'=>1, 'form'=>1, 'map'=>1, 'noscript'=>1); // Block
$cE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1); // Empty
$cF = array('button'=>1, 'del'=>1, 'div'=>1, 'dd'=>1, 'fieldset'=>1, 'iframe'=>1, 'ins'=>1, 'li'=>1, 'noscript'=>1, 'object'=>1, 'td'=>1, 'th'=>1); // Flow; later context-wise dynamic move of ins & del to $cI
$cI = array('a'=>1, 'abbr'=>1, 'acronym'=>1, 'address'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'caption'=>1, 'cite'=>1, 'code'=>1, 'dfn'=>1, 'dt'=>1, 'em'=>1, 'font'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'i'=>1, 'kbd'=>1, 'label'=>1, 'legend'=>1, 'p'=>1, 'pre'=>1, 'q'=>1, 'rb'=>1, 'rt'=>1, 's'=>1, 'samp'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'tt'=>1, 'u'=>1, 'var'=>1); // Inline
$cN = array('a'=>array('a'=>1), 'button'=>array('a'=>1, 'button'=>1, 'fieldset'=>1, 'form'=>1, 'iframe'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'fieldset'=>array('fieldset'=>1), 'form'=>array('form'=>1), 'label'=>array('label'=>1), 'noscript'=>array('script'=>1), 'pre'=>array('big'=>1, 'font'=>1, 'img'=>1, 'object'=>1, 'script'=>1, 'small'=>1, 'sub'=>1, 'sup'=>1), 'rb'=>array('ruby'=>1), 'rt'=>array('ruby'=>1)); // Illegal
$cN2 = array_keys($cN);
$cR = array('blockquote'=>1, 'dir'=>1, 'dl'=>1, 'form'=>1, 'map'=>1, 'menu'=>1, 'noscript'=>1, 'ol'=>1, 'optgroup'=>1, 'rbc'=>1, 'rtc'=>1, 'ruby'=>1, 'select'=>1, 'table'=>1, 'tbody'=>1, 'tfoot'=>1, 'thead'=>1, 'tr'=>1, 'ul'=>1);
$cS = array('colgroup'=>array('col'=>1), 'dir'=>array('li'), 'dl'=>array('dd'=>1, 'dt'=>1), 'menu'=>array('li'=>1), 'ol'=>array('li'=>1), 'optgroup'=>array('option'=>1), 'option'=>array('#pcdata'=>1), 'rbc'=>array('rb'=>1), 'rp'=>array('#pcdata'=>1), 'rtc'=>array('rt'=>1), 'ruby'=>array('rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1), 'select'=>array('optgroup'=>1, 'option'=>1), 'script'=>array('#pcdata'=>1), 'table'=>array('caption'=>1, 'col'=>1, 'colgroup'=>1, 'tfoot'=>1, 'tbody'=>1, 'tr'=>1, 'thead'=>1), 'tbody'=>array('tr'=>1), 'tfoot'=>array('tr'=>1), 'textarea'=>array('#pcdata'=>1), 'thead'=>array('tr'=>1), 'tr'=>array('td'=>1, 'th'=>1), 'ul'=>array('li'=>1)); // Specific (immediate parent-child)
$cO = array('address'=>array('p'=>1), 'applet'=>array('param'=>1), 'blockquote'=>array('script'=>1), 'fieldset'=>array('legend'=>1, '#pcdata'=>1), 'form'=>array('script'=>1), 'map'=>array('area'=>1), 'object'=>array('param'=>1, 'embed'=>1)); // Other
$cT = array('colgroup'=>1, 'dd'=>1, 'dt'=>1, 'li'=>1, 'option'=>1, 'p'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1); // Omitable closing
// eles by block/inline type; ins & del both type; #pcdata: plain text
$eB = array('address'=>1, 'blockquote'=>1, 'center'=>1, 'del'=>1, 'dir'=>1, 'dl'=>1, 'div'=>1, 'fieldset'=>1, 'form'=>1, 'ins'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'isindex'=>1, 'menu'=>1, 'noscript'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'table'=>1, 'ul'=>1);
$eI = array('#pcdata'=>1, 'a'=>1, 'abbr'=>1, 'acronym'=>1, 'applet'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'br'=>1, 'button'=>1, 'cite'=>1, 'code'=>1, 'del'=>1, 'dfn'=>1, 'em'=>1, 'embed'=>1, 'font'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'kbd'=>1, 'label'=>1, 'map'=>1, 'object'=>1, 'param'=>1, 'q'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'select'=>1, 'script'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'textarea'=>1, 'tt'=>1, 'u'=>1, 'var'=>1);
$eN = array('a'=>1, 'big'=>1, 'button'=>1, 'fieldset'=>1, 'font'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'label'=>1, 'object'=>1, 'ruby'=>1, 'script'=>1, 'select'=>1, 'small'=>1, 'sub'=>1, 'sup'=>1, 'textarea'=>1); // Exclude from specific ele; $cN values
$eO = array('area'=>1, 'caption'=>1, 'col'=>1, 'colgroup'=>1, 'dd'=>1, 'dt'=>1, 'legend'=>1, 'li'=>1, 'optgroup'=>1, 'option'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'script'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'thead'=>1, 'th'=>1, 'tr'=>1); // Missing in $eB & $eI
$eF = $eB + $eI;

// $in sets allowed children
$in = ((isset($eF[$in]) && $in != '#pcdata') or isset($eO[$in])) ? $in : 'div';
if(isset($cE[$in])){
 return (!$do ? '' : str_replace(array('<', '>'), array('&lt;', '&gt;'), $t));
}
if(isset($cS[$in])){$inOk = $cS[$in];}
elseif(isset($cI[$in])){$inOk = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
elseif(isset($cF[$in])){$inOk = $eF; unset($cI['del'], $cI['ins']);}
elseif(isset($cB[$in])){$inOk = $eB; unset($cI['del'], $cI['ins']);}
if(isset($cO[$in])){$inOk = $inOk + $cO[$in];}
if(isset($cN[$in])){$inOk = array_diff_assoc($inOk, $cN[$in]);}

$t = explode('<', $t);
$ok = $q = array(); // $q seq list of open non-empty ele
ob_start();

for($i=-1, $ci=count($t); ++$i<$ci;){
 // allowed $ok in parent $p
 if($ql = count($q)){
  $p = array_pop($q);
  $q[] = $p;
  if(isset($cS[$p])){$ok = $cS[$p];}
  elseif(isset($cI[$p])){$ok = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
  elseif(isset($cF[$p])){$ok = $eF; unset($cI['del'], $cI['ins']);}
  elseif(isset($cB[$p])){$ok = $eB; unset($cI['del'], $cI['ins']);}
  if(isset($cO[$p])){$ok = $ok + $cO[$p];}
  if(isset($cN[$p])){$ok = array_diff_assoc($ok, $cN[$p]);}
 }else{$ok = $inOk; unset($cI['del'], $cI['ins']);}
 // bad tags, & ele content
 if(isset($e) && ($do == 1 or (isset($ok['#pcdata']) && ($do == 3 or $do == 5)))){
  echo '&lt;', $s, $e, $a, '&gt;';
 }
 if(isset($x[0])){
  if($do < 3 or isset($ok['#pcdata'])){echo $x;}
  elseif(strpos($x, "\x02\x04")){
   foreach(preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v){
    echo (substr($v, 0, 2) == "\x01\x02" ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : ''));
   }
  }elseif($do > 4){echo preg_replace('`\S`', '', $x);}
 }
 // get markup
 if(!preg_match('`^(/?)([a-zA-Z1-6]+)([^>]*)>(.*)`sm', $t[$i], $r)){$x = $t[$i]; continue;}
 $s = null; $e = null; $a = null; $x = null; list($all, $s, $e, $a, $x) = $r;
 // close tag
 if($s){
  if(isset($cE[$e]) or !in_array($e, $q)){continue;} // Empty/unopen
  if($p == $e){array_pop($q); echo '</', $e, '>'; unset($e); continue;} // Last open
  $add = ''; // Nesting - close open tags that need to be
  for($j=-1, $cj=count($q); ++$j<$cj;){  
   if(($d = array_pop($q)) == $e){break;}
   else{$add .= "</{$d}>";}
  }
  echo $add, '</', $e, '>'; unset($e); continue;
 }
 // open tag
 // $cB ele needs $eB ele as child
 if(isset($cB[$e]) && strlen(trim($x))){
  $t[$i] = "{$e}{$a}>";
  array_splice($t, $i+1, 0, 'div>'. $x); unset($e, $x); ++$ci; --$i; continue;
 }
 if((($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql)) && !isset($eB[$e]) && !isset($ok[$e])){
  array_splice($t, $i, 0, 'div>'); unset($e, $x); ++$ci; --$i; continue;
 }
 // if no open ele, $in is parent; except for certain cases, immediate parent-child relation should hold
 if(!$ql or !isset($eN[$e]) or !array_intersect($q, $cN2)){
  if(!isset($ok[$e])){
   if($ql && isset($cT[$p])){echo '</', array_pop($q), '>'; unset($e, $x); --$i;}
   continue;
  }
  if(!isset($cE[$e])){$q[] = $e;}
  echo '<', $e, $a, '>'; unset($e); continue;
 }
 // specific parent-child
 if(isset($cS[$p][$e])){
  if(!isset($cE[$e])){$q[] = $e;}
  echo '<', $e, $a, '>'; unset($e); continue;
 }
 // nesting
 $add = '';
 $q2 = array();
 for($k=-1, $kc=count($q); ++$k<$kc;){
  $d = $q[$k];
  $ok2 = array();
  if(isset($cS[$d])){$q2[] = $d; continue;}
  $ok2 = isset($cI[$d]) ? $eI : $eF;
  if(isset($cO[$d])){$ok2 = $ok2 + $cO[$d];}
  if(isset($cN[$d])){$ok2 = array_diff_assoc($ok2, $cN[$d]);}
  if(!isset($ok2[$e])){
   if(!$k && !isset($inOk[$e])){continue 2;}
   $add = "</{$d}>";
   for(;++$k<$kc;){$add = "</{$q[$k]}>{$add}";}
   break;
  }
  else{$q2[] = $d;}
 }
 $q = $q2;
 if(!isset($cE[$e])){$q[] = $e;}
 echo $add, '<', $e, $a, '>'; unset($e); continue;
}

// end
if($ql = count($q)){
 $p = array_pop($q);
 $q[] = $p;
 if(isset($cS[$p])){$ok = $cS[$p];}
 elseif(isset($cI[$p])){$ok = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
 elseif(isset($cF[$p])){$ok = $eF; unset($cI['del'], $cI['ins']);}
 elseif(isset($cB[$p])){$ok = $eB; unset($cI['del'], $cI['ins']);}
 if(isset($cO[$p])){$ok = $ok + $cO[$p];}
 if(isset($cN[$p])){$ok = array_diff_assoc($ok, $cN[$p]);}
}else{$ok = $inOk; unset($cI['del'], $cI['ins']);}
if(isset($e) && ($do == 1 or (isset($ok['#pcdata']) && ($do == 3 or $do == 5)))){
 echo '&lt;', $s, $e, $a, '&gt;';
}
if(isset($x[0])){
 if(strlen(trim($x)) && (($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql))){
  echo '<div>', $x, '</div>';
 }
 elseif($do < 3 or isset($ok['#pcdata'])){echo $x;}
 elseif(strpos($x, "\x02\x04")){
  foreach(preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v){
   echo (substr($v, 0, 2) == "\x01\x02" ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : ''));
  }
 }elseif($do > 4){echo preg_replace('`\S`', '', $x);}
}
while(!empty($q) && ($e = array_pop($q))){echo '</', $e, '>';}
$o = ob_get_contents();
ob_end_clean();
return $o;
// eof
}

function hl_cmtcd($t){
// comment/CDATA sec handler
$t = $t[0];
global $C;
if($t[3] == '-'){
 if(!$C['comment']){return $t;}
 if($C['comment'] == 1){return '';}
 if(substr(($t = preg_replace('`--+`', '-', substr($t, 4, -3))), -1) != ' '){$t .= ' ';}
 $t = $C['comment'] == 2 ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $t) : $t;
 $t = "\x01\x02\x04!--$t--\x05\x02\x01";
}else{ // CDATA
 if(!$C['cdata']){return $t;}
 if($C['cdata'] == 1){return '';}
 $t = substr($t, 1, -1);
 $t = $C['cdata'] == 2 ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $t) : $t;
 $t = "\x01\x01\x04$t\x05\x01\x01";
}
return str_replace(array('&', '<', '>'), array("\x03", "\x04", "\x05"), $t);
// eof
}

function hl_ent($t){
// entitity handler
global $C;
$t = $t[1];
static $U = array('quot'=>1,'amp'=>1,'lt'=>1,'gt'=>1);
static $N = array('fnof'=>'402', 'Alpha'=>'913', 'Beta'=>'914', 'Gamma'=>'915', 'Delta'=>'916', 'Epsilon'=>'917', 'Zeta'=>'918', 'Eta'=>'919', 'Theta'=>'920', 'Iota'=>'921', 'Kappa'=>'922', 'Lambda'=>'923', 'Mu'=>'924', 'Nu'=>'925', 'Xi'=>'926', 'Omicron'=>'927', 'Pi'=>'928', 'Rho'=>'929', 'Sigma'=>'931', 'Tau'=>'932', 'Upsilon'=>'933', 'Phi'=>'934', 'Chi'=>'935', 'Psi'=>'936', 'Omega'=>'937', 'alpha'=>'945', 'beta'=>'946', 'gamma'=>'947', 'delta'=>'948', 'epsilon'=>'949', 'zeta'=>'950', 'eta'=>'951', 'theta'=>'952', 'iota'=>'953', 'kappa'=>'954', 'lambda'=>'955', 'mu'=>'956', 'nu'=>'957', 'xi'=>'958', 'omicron'=>'959', 'pi'=>'960', 'rho'=>'961', 'sigmaf'=>'962', 'sigma'=>'963', 'tau'=>'964', 'upsilon'=>'965', 'phi'=>'966', 'chi'=>'967', 'psi'=>'968', 'omega'=>'969', 'thetasym'=>'977', 'upsih'=>'978', 'piv'=>'982', 'bull'=>'8226', 'hellip'=>'8230', 'prime'=>'8242', 'Prime'=>'8243', 'oline'=>'8254', 'frasl'=>'8260', 'weierp'=>'8472', 'image'=>'8465', 'real'=>'8476', 'trade'=>'8482', 'alefsym'=>'8501', 'larr'=>'8592', 'uarr'=>'8593', 'rarr'=>'8594', 'darr'=>'8595', 'harr'=>'8596', 'crarr'=>'8629', 'lArr'=>'8656', 'uArr'=>'8657', 'rArr'=>'8658', 'dArr'=>'8659', 'hArr'=>'8660', 'forall'=>'8704', 'part'=>'8706', 'exist'=>'8707', 'empty'=>'8709', 'nabla'=>'8711', 'isin'=>'8712', 'notin'=>'8713', 'ni'=>'8715', 'prod'=>'8719', 'sum'=>'8721', 'minus'=>'8722', 'lowast'=>'8727', 'radic'=>'8730', 'prop'=>'8733', 'infin'=>'8734', 'ang'=>'8736', 'and'=>'8743', 'or'=>'8744', 'cap'=>'8745', 'cup'=>'8746', 'int'=>'8747', 'there4'=>'8756', 'sim'=>'8764', 'cong'=>'8773', 'asymp'=>'8776', 'ne'=>'8800', 'equiv'=>'8801', 'le'=>'8804', 'ge'=>'8805', 'sub'=>'8834', 'sup'=>'8835', 'nsub'=>'8836', 'sube'=>'8838', 'supe'=>'8839', 'oplus'=>'8853', 'otimes'=>'8855', 'perp'=>'8869', 'sdot'=>'8901', 'lceil'=>'8968', 'rceil'=>'8969', 'lfloor'=>'8970', 'rfloor'=>'8971', 'lang'=>'9001', 'rang'=>'9002', 'loz'=>'9674', 'spades'=>'9824', 'clubs'=>'9827', 'hearts'=>'9829', 'diams'=>'9830', 'apos'=>'39',  'OElig'=>'338', 'oelig'=>'339', 'Scaron'=>'352', 'scaron'=>'353', 'Yuml'=>'376', 'circ'=>'710', 'tilde'=>'732', 'ensp'=>'8194', 'emsp'=>'8195', 'thinsp'=>'8201', 'zwnj'=>'8204', 'zwj'=>'8205', 'lrm'=>'8206', 'rlm'=>'8207', 'ndash'=>'8211', 'mdash'=>'8212', 'lsquo'=>'8216', 'rsquo'=>'8217', 'sbquo'=>'8218', 'ldquo'=>'8220', 'rdquo'=>'8221', 'bdquo'=>'8222', 'dagger'=>'8224', 'Dagger'=>'8225', 'permil'=>'8240', 'lsaquo'=>'8249', 'rsaquo'=>'8250', 'euro'=>'8364', 'nbsp'=>'160', 'iexcl'=>'161', 'cent'=>'162', 'pound'=>'163', 'curren'=>'164', 'yen'=>'165', 'brvbar'=>'166', 'sect'=>'167', 'uml'=>'168', 'copy'=>'169', 'ordf'=>'170', 'laquo'=>'171', 'not'=>'172', 'shy'=>'173', 'reg'=>'174', 'macr'=>'175', 'deg'=>'176', 'plusmn'=>'177', 'sup2'=>'178', 'sup3'=>'179', 'acute'=>'180', 'micro'=>'181', 'para'=>'182', 'middot'=>'183', 'cedil'=>'184', 'sup1'=>'185', 'ordm'=>'186', 'raquo'=>'187', 'frac14'=>'188', 'frac12'=>'189', 'frac34'=>'190', 'iquest'=>'191', 'Agrave'=>'192', 'Aacute'=>'193', 'Acirc'=>'194', 'Atilde'=>'195', 'Auml'=>'196', 'Aring'=>'197', 'AElig'=>'198', 'Ccedil'=>'199', 'Egrave'=>'200', 'Eacute'=>'201', 'Ecirc'=>'202', 'Euml'=>'203', 'Igrave'=>'204', 'Iacute'=>'205', 'Icirc'=>'206', 'Iuml'=>'207', 'ETH'=>'208', 'Ntilde'=>'209', 'Ograve'=>'210', 'Oacute'=>'211', 'Ocirc'=>'212', 'Otilde'=>'213', 'Ouml'=>'214', 'times'=>'215', 'Oslash'=>'216', 'Ugrave'=>'217', 'Uacute'=>'218', 'Ucirc'=>'219', 'Uuml'=>'220', 'Yacute'=>'221', 'THORN'=>'222', 'szlig'=>'223', 'agrave'=>'224', 'aacute'=>'225', 'acirc'=>'226', 'atilde'=>'227', 'auml'=>'228', 'aring'=>'229', 'aelig'=>'230', 'ccedil'=>'231', 'egrave'=>'232', 'eacute'=>'233', 'ecirc'=>'234', 'euml'=>'235', 'igrave'=>'236', 'iacute'=>'237', 'icirc'=>'238', 'iuml'=>'239', 'eth'=>'240', 'ntilde'=>'241', 'ograve'=>'242', 'oacute'=>'243', 'ocirc'=>'244', 'otilde'=>'245', 'ouml'=>'246', 'divide'=>'247', 'oslash'=>'248', 'ugrave'=>'249', 'uacute'=>'250', 'ucirc'=>'251', 'uuml'=>'252', 'yacute'=>'253', 'thorn'=>'254', 'yuml'=>'255');
if($t[0] != '#'){
 return ($C['and_mark'] ? "\x06" : '&'). (isset($U[$t]) ? $t : (isset($N[$t]) ? (!$C['named_entity'] ? '#'. ($C['hexdec_entity'] > 1 ? 'x'. dechex($N[$t]) : $N[$t]) : $t) : 'amp;'. $t)). ';';
}
if(($n = ctype_digit($t = substr($t, 1)) ? intval($t) : hexdec(substr($t, 1))) < 9 or ($n > 13 && $n < 32) or $n == 11 or $n == 12 or ($n > 126 && $n < 160 && $n != 133) or ($n > 55295 && ($n < 57344 or ($n > 64975 && $n < 64992) or $n == 65534 or $n == 65535 or $n > 1114111))){
 return ($C['and_mark'] ? "\x06" : '&'). "amp;#{$t};";
}
return ($C['and_mark'] ? "\x06" : '&'). '#'. (((ctype_digit($t) && $C['hexdec_entity'] < 2) or !$C['hexdec_entity']) ? $n : 'x'. dechex($n)). ';';
// eof
}

function hl_prot($p, $c=null){
// check URL scheme
global $C;
$b = $a = '';
if($c == null){$c = 'style'; $b = $p[1]; $a = $p[3]; $p = trim($p[2]);}
$c = isset($C['schemes'][$c]) ? $C['schemes'][$c] : $C['schemes']['*'];
if(isset($c['*']) or !strcspn($p, '#?;')){return "{$b}{$p}{$a}";} // All ok, frag, query, param
if(preg_match('`^([a-z\d\-+.&#; ]+?)(:|&#(58|x3a);|%3a|\\\\0{0,4}3a).`i', $p, $m) && !isset($c[strtolower($m[1])])){ // Denied prot
 return "{$b}denied:{$p}{$a}";
}
if($C['abs_url']){
 if($C['abs_url'] == -1 && strpos($p, $C['base_url']) === 0){ // Make url rel
  $p = substr($p, strlen($C['base_url']));
 }elseif(empty($m[1])){ // Make URL abs
  if(substr($p, 0, 2) == '//'){$p = substr($C['base_url'], 0, strpos($C['base_url'], ':')+1). $p;}
  elseif($p[0] == '/'){$p = preg_replace('`(^.+?://[^/]+)(.*)`', '$1', $C['base_url']). $p;}
  elseif(strcspn($p, './')){$p = $C['base_url']. $p;}
  else{
   preg_match('`^([a-zA-Z\d\-+.]+://[^/]+)(.*)`', $C['base_url'], $m);
   $p = preg_replace('`(?<=/)\./`', '', $m[2]. $p);
   while(preg_match('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', $p)){
    $p = preg_replace('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', '', $p);
   }
   $p = $m[1]. $p;
  }
 }
}
return "{$b}{$p}{$a}";
// eof
}

function hl_regex($p){
// ?ok regex
if(empty($p)){return 0;}
if($t = ini_get('track_errors')){$o = isset($php_errormsg) ? $php_errormsg : null;}
else{ini_set('track_errors', 1);}
unset($php_errormsg);
if(($d = ini_get('display_errors'))){ini_set('display_errors', 0);}
preg_match($p, '');
if($d){ini_set('display_errors', 1);}
$r = isset($php_errormsg) ? 0 : 1;
if($t){$php_errormsg = isset($o) ? $o : null;}
else{ini_set('track_errors', 0);}
return $r;
// eof
}

function hl_spec($t){
// finalize $spec
$s = array();
$t = str_replace(array("\t", "\r", "\n", ' '), '', preg_replace('/"(?>(`.|[^"])*)"/sme', 'substr(str_replace(array(";", "|", "~", " ", ",", "/", "(", ")", \'`"\'), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\""), "$0"), 1, -1)', trim($t))); 
for($i = count(($t = explode(';', $t))); --$i>=0;){
 $w = $t[$i];
 if(empty($w) or ($e = strpos($w, '=')) === false or !strlen(($a =  substr($w, $e+1)))){continue;}
 $y = $n = array();
 foreach(explode(',', $a) as $v){
  if(!preg_match('`^([a-z:\-\*]+)(?:\((.*?)\))?`i', $v, $m)){continue;}
  if(($x = strtolower($m[1])) == '-*'){$n['*'] = 1; continue;}
  if($x[0] == '-'){$n[substr($x, 1)] = 1; continue;}
  if(!isset($m[2])){$y[$x] = 1; continue;}
  foreach(explode('/', $m[2]) as $m){
   if(empty($m) or ($p = strpos($m, '=')) == 0 or $p < 5){$y[$x] = 1; continue;}
   $y[$x][strtolower(substr($m, 0, $p))] = str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"), array(";", "|", "~", " ", ",", "/", "(", ")"), substr($m, $p+1));
  }
  if(isset($y[$x]['match']) && !hl_regex($y[$x]['match'])){unset($y[$x]['match']);}
  if(isset($y[$x]['nomatch']) && !hl_regex($y[$x]['nomatch'])){unset($y[$x]['nomatch']);}
 }
 if(!count($y) && !count($n)){continue;}
 if(!isset($n['*'])){
  foreach($y as $k=>$v){
   if(!is_array($v) or !count($v)){unset($y[$k]);}
  }
 }
 foreach(explode(',', substr($w, 0, $e)) as $v){
  if(!strlen(($v = strtolower($v)))){continue;}
  if(count($y)){$s[$v] = $y;}
  if(count($n)){$s[$v]['n'] = $n;}
 }
}
return $s;
// eof
}

function hl_tag($t){
// tag/attribute handler
global $C;
$t = $t[0];
// invalid < >
if($t == '< '){return '&lt; ';}
if($t == '>'){return '&gt;';}
if(!preg_match('`^<(/?)([a-zA-Z][a-zA-Z1-6]*)([^>]*?)\s?>$`m', $t, $m)){
 return str_replace(array('<', '>'), array('&lt;', '&gt;'), $t);
}elseif(!isset($C['elements'][($e = strtolower($m[2]))])){
 return (($C['keep_bad']%2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');
}
// attr string
$a = str_replace(array("\xad", "\n", "\r", "\t"), ' ', trim($m[3]));
if(strpos($a, '&') !== false){
 str_replace(array('&#xad;', '&#173;', '&shy;'), ' ', $a);
}
// tag transform
static $eD = array('applet'=>1, 'center'=>1, 'dir'=>1, 'embed'=>1, 'font'=>1, 'isindex'=>1, 'menu'=>1, 's'=>1, 'strike'=>1, 'u'=>1); // Deprecated
if($C['make_tag_strict'] && isset($eD[$e])){
 $trt = hl_tag2($e, $a, $C['make_tag_strict']);
 if(!$e){return (($C['keep_bad']%2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : '');}
}
// close tag
static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1); // Empty ele
if(!empty($m[1])){
 return (!isset($eE[$e]) ? "</$e>" : (($C['keep_bad'])%2 ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $t) : ''));
}

// open tag & attr
static $aN = array('abbr'=>array('td'=>1, 'th'=>1), 'accept-charset'=>array('form'=>1), 'accept'=>array('form'=>1, 'input'=>1), 'accesskey'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'legend'=>1, 'textarea'=>1), 'action'=>array('form'=>1), 'align'=>array('caption'=>1, 'embed'=>1, 'applet'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'legend'=>1, 'table'=>1, 'hr'=>1, 'div'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'p'=>1, 'col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'alt'=>array('applet'=>1, 'area'=>1, 'img'=>1, 'input'=>1), 'archive'=>array('applet'=>1, 'object'=>1), 'axis'=>array('td'=>1, 'th'=>1), 'bgcolor'=>array('embed'=>1, 'table'=>1, 'tr'=>1, 'td'=>1, 'th'=>1), 'border'=>array('table'=>1, 'img'=>1, 'object'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'cellpadding'=>array('table'=>1), 'cellspacing'=>array('table'=>1), 'char'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charoff'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charset'=>array('a'=>1, 'script'=>1), 'checked'=>array('input'=>1), 'cite'=>array('blockquote'=>1, 'q'=>1, 'del'=>1, 'ins'=>1), 'classid'=>array('object'=>1), 'clear'=>array('br'=>1), 'code'=>array('applet'=>1), 'codebase'=>array('object'=>1, 'applet'=>1), 'codetype'=>array('object'=>1), 'color'=>array('font'=>1), 'cols'=>array('textarea'=>1), 'colspan'=>array('td'=>1, 'th'=>1), 'compact'=>array('dir'=>1, 'dl'=>1, 'menu'=>1, 'ol'=>1, 'ul'=>1), 'coords'=>array('area'=>1, 'a'=>1), 'data'=>array('object'=>1), 'datetime'=>array('del'=>1, 'ins'=>1), 'declare'=>array('object'=>1), 'defer'=>array('script'=>1), 'dir'=>array('bdo'=>1), 'disabled'=>array('button'=>1, 'input'=>1, 'optgroup'=>1, 'option'=>1, 'select'=>1, 'textarea'=>1), 'enctype'=>array('form'=>1), 'face'=>array('font'=>1), 'for'=>array('label'=>1), 'frame'=>array('table'=>1), 'frameborder'=>array('iframe'=>1), 'headers'=>array('td'=>1, 'th'=>1), 'height'=>array('embed'=>1, 'iframe'=>1, 'td'=>1, 'th'=>1, 'img'=>1, 'object'=>1, 'applet'=>1), 'href'=>array('a'=>1, 'area'=>1), 'hreflang'=>array('a'=>1), 'hspace'=>array('applet'=>1, 'img'=>1, 'object'=>1), 'ismap'=>array('img'=>1, 'input'=>1), 'label'=>array('option'=>1, 'optgroup'=>1), 'language'=>array('script'=>1), 'longdesc'=>array('img'=>1, 'iframe'=>1), 'marginheight'=>array('iframe'=>1), 'marginwidth'=>array('iframe'=>1), 'maxlength'=>array('input'=>1), 'method'=>array('form'=>1), 'model'=>array('embed'=>1), 'multiple'=>array('select'=>1), 'name'=>array('button'=>1, 'embed'=>1, 'textarea'=>1, 'applet'=>1, 'select'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'a'=>1, 'input'=>1, 'object'=>1, 'map'=>1, 'param'=>1), 'nohref'=>array('area'=>1), 'noshade'=>array('hr'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'object'=>array('applet'=>1), 'onblur'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'onchange'=>array('input'=>1, 'select'=>1, 'textarea'=>1), 'onfocus'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'onreset'=>array('form'=>1), 'onselect'=>array('input'=>1, 'textarea'=>1), 'onsubmit'=>array('form'=>1), 'pluginspage'=>array('embed'=>1), 'pluginurl'=>array('embed'=>1), 'prompt'=>array('isindex'=>1), 'readonly'=>array('textarea'=>1, 'input'=>1), 'rel'=>array('a'=>1), 'rev'=>array('a'=>1), 'rows'=>array('textarea'=>1), 'rowspan'=>array('td'=>1, 'th'=>1), 'rules'=>array('table'=>1), 'scope'=>array('td'=>1, 'th'=>1), 'scrolling'=>array('iframe'=>1), 'selected'=>array('option'=>1), 'shape'=>array('area'=>1, 'a'=>1), 'size'=>array('hr'=>1, 'font'=>1, 'input'=>1, 'select'=>1), 'span'=>array('col'=>1, 'colgroup'=>1), 'src'=>array('embed'=>1, 'script'=>1, 'input'=>1, 'iframe'=>1, 'img'=>1), 'standby'=>array('object'=>1), 'start'=>array('ol'=>1), 'summary'=>array('table'=>1), 'tabindex'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'object'=>1, 'select'=>1, 'textarea'=>1), 'target'=>array('a'=>1, 'area'=>1, 'form'=>1), 'type'=>array('a'=>1, 'embed'=>1, 'object'=>1, 'param'=>1, 'script'=>1, 'input'=>1, 'li'=>1, 'ol'=>1, 'ul'=>1, 'button'=>1), 'usemap'=>array('img'=>1, 'input'=>1, 'object'=>1), 'valign'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'value'=>array('input'=>1, 'option'=>1, 'param'=>1, 'button'=>1, 'li'=>1), 'valuetype'=>array('param'=>1), 'vspace'=>array('applet'=>1, 'img'=>1, 'object'=>1), 'width'=>array('embed'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'object'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'applet'=>1, 'col'=>1, 'colgroup'=>1, 'pre'=>1), 'wmode'=>array('embed'=>1), 'xml:space'=>array('pre'=>1, 'script'=>1, 'style'=>1)); // Specific attrs
static $aNE = array('checked'=>1, 'compact'=>1, 'declare'=>1, 'defer'=>1, 'disabled'=>1, 'ismap'=>1, 'multiple'=>1, 'nohref'=>1, 'noresize'=>1, 'noshade'=>1, 'nowrap'=>1, 'readonly'=>1, 'selected'=>1); // Empty attrs
static $aNP = array('action'=>1, 'cite'=>1, 'classid'=>1, 'codebase'=>1, 'data'=>1, 'href'=>1, 'longdesc'=>1, 'model'=>1, 'pluginspage'=>1, 'pluginurl'=>1, 'usemap'=>1); // Attrs needing protocol check; for attrs like onmouseover & src, using: '$n[0] != 'o' && strpos($n, 'src') === false'; 'style' separately handled
static $aNU = array('class'=>array('param'=>1, 'script'=>1), 'dir'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'id'=>array('script'=>1), 'lang'=>array('applet'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'xml:lang'=>array('applet'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'onclick'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'ondblclick'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeydown'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeypress'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeyup'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmousedown'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmousemove'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseout'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseover'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseup'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'style'=>array('param'=>1, 'script'=>1), 'title'=>array('param'=>1, 'script'=>1)); // Univ attrs & exceptions

if($C['lc_std_val']){
 // predef attr vals like radio for $eAL & $aNE ele
 static $aNL = array('all'=>1, 'baseline'=>1, 'bottom'=>1, 'button'=>1, 'center'=>1, 'char'=>1, 'checkbox'=>1, 'circle'=>1, 'col'=>1, 'colgroup'=>1, 'cols'=>1, 'data'=>1, 'default'=>1, 'file'=>1, 'get'=>1, 'groups'=>1, 'hidden'=>1, 'image'=>1, 'justify'=>1, 'left'=>1, 'ltr'=>1, 'middle'=>1, 'none'=>1, 'object'=>1, 'password'=>1, 'poly'=>1, 'post'=>1, 'preserve'=>1, 'radio'=>1, 'rect'=>1, 'ref'=>1, 'reset'=>1, 'right'=>1, 'row'=>1, 'rowgroup'=>1, 'rows'=>1, 'rtl'=>1, 'submit'=>1, 'text'=>1, 'top'=>1);
 static $eAL = array('a'=>1, 'area'=>1, 'bdo'=>1, 'button'=>1, 'col'=>1, 'form'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'optgroup'=>1, 'option'=>1, 'param'=>1, 'script'=>1, 'select'=>1, 'table'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1, 'xml:space'=>1);
 $lcase = isset($eAL[$e]) ? 1 : 0;
}

$depTr = 0;
if($C['no_deprecated_attr']){
 // dep attr:applicable ele
 static $aND = array('align'=>array('caption'=>1, 'div'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'object'=>1, 'p'=>1, 'table'=>1), 'bgcolor'=>array('table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1), 'border'=>array('img'=>1, 'object'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'clear'=>array('br'=>1), 'compact'=>array('dl'=>1, 'ol'=>1, 'ul'=>1), 'height'=>array('td'=>1, 'th'=>1), 'hspace'=>array('img'=>1, 'object'=>1), 'language'=>array('script'=>1), 'name'=>array('a'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'map'=>1), 'noshade'=>array('hr'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'size'=>array('hr'=>1), 'start'=>array('ol'=>1), 'type'=>array('li'=>1, 'ol'=>1, 'ul'=>1), 'value'=>array('li'=>1), 'vspace'=>array('img'=>1, 'object'=>1), 'width'=>array('hr'=>1, 'pre'=>1, 'td'=>1, 'th'=>1));
 static $eAD = array('a'=>1, 'br'=>1, 'caption'=>1, 'div'=>1, 'dl'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'li'=>1, 'map'=>1, 'object'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'script'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1, 'ul'=>1);
 $depTr = isset($eAD[$e]) ? 1 : 0;
}

// attr name-vals
if(strpos($a, "\x01") !== false){$a = preg_replace('`\x01[^\x01]*\x01`', '', $a);} // No comment/CDATA sec
$mode = 0; $a = trim($a, ' /'); $aA = array();
while(strlen($a)){
 $w = 0;
 switch($mode){
  case 0: // Attr name
   if(preg_match('`^[a-zA-Z][\-a-zA-Z:]+`', $a, $m)){
    $nm = strtolower($m[0]);
    $w = $mode = 1; $a = ltrim(substr_replace($a, '', 0, strlen($m[0])));
   }
  break; case 1:
   if($a[0] == '='){ // =
    $w = 1; $mode = 2; $a = ltrim($a, '= ');
   }else{ // No val
    $w = 1; $mode = 0; $a = ltrim($a);
    $aA[$nm] = '';
   }
  break; case 2: // Attr val
   if(preg_match('`^"[^"]*"`', $a, $m) or preg_match("`^'[^']*'`", $a, $m) or preg_match("`^\s*[^\s\"']+`", $a, $m)){
    $m = $m[0]; $w = 1; $mode = 0; $a = ltrim(substr_replace($a, '', 0, strlen($m)));
    $aA[$nm] = trim(($m[0] == '"' or $m[0] == '\'') ? substr($m, 1, -1) : $m);
   }
  break;
 }
 if($w == 0){ // Parse errs, deal with space, " & '
  $a = preg_replace('`^(?:"[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*`', '', $a);
  $mode = 0;
 }
}
if($mode == 1){$aA[$nm] = '';}

// clean attrs - remove invalids, escape ", values for 'empty' attr, lowercase predefined values, remove ones with unfit values, anti-spam, check scheme & expressions in style props, check scheme in other attr
global $spec;
$rl = isset($spec[$e]) ? $spec[$e] : array();
$a = array(); $nfr = 0;
foreach($aA as $k=>$v){
 if(!isset($C['deny_attribute'][$k]) && ((!isset($rl['n'][$k]) && !isset($rl['n']['*'])) or isset($rl[$k])) && (isset($aN[$k][$e]) or (isset($aNU[$k]) && !isset($aNU[$k][$e])))){
  if(isset($aNE[$k])){$v = $k;}
  elseif(!empty($lcase) && (($e != 'button' or $e != 'input') or $k == 'type')){ // Rather loose but ?not cause issues
   $v = (isset($aNL[($v2 = strtolower($v))])) ? $v2 : $v;
  }
  if($k == 'style'){
   $v = preg_replace_callback('`((?:u|&#(?:x75|117);)(?:r|&#(?:x72|114);)(?:l|&#(?:x6c|108);)(?:\(|&#(?:x28|40);)(?: |&#(?:x20|32);)*(?:\'|"|&(?:quot|apos|34|39|x22|x27);)?)(.+)((?:\'|"|&(?:quot|apos|34|39|x22|x27);)?(?: |&#(?:x20|32);)*(?:\)|&#(?:x29|41);))`iS', 'hl_prot', $v);
   $v = !$C['css_expression'] ? preg_replace('`:\s*(/\*.*\*/)*\s*e.+?n\s*(/\*.*\*/)*\s*\(.*\)`iS', '', html_entity_decode(urldecode($v))) : $v;
  }elseif(isset($aNP[$k]) or strpos($k, 'src') !== false or $k[0] == 'o'){
   $v = hl_prot($v, $k);
   if($k == 'href'){ // Anti-spam
    if($C['anti_mail_spam'] && strpos($v, 'mailto:') === 0){
     $v = str_replace('@', htmlspecialchars($C['anti_mail_spam']), $v);
    }elseif($C['anti_link_spam']){
     $r1 = $C['anti_link_spam'][1];
     if(!empty($r1) && preg_match($r1, $v)){continue;}
     $r0 = $C['anti_link_spam'][0];
     if(!empty($r0) && preg_match($r0, $v)){
      if(isset($a['rel'])){
       if(!preg_match('`\bnofollow\b`i', $a['rel'])){$a['rel'] .= ' nofollow';}
      }elseif(isset($aA['rel'])){
       if(!preg_match('`\bnofollow\b`i', $aA['rel'])){$nfr = 1;}
      }else{$a['rel'] = 'nofollow';}
     }
    }
   }
  }
  if(isset($rl[$k]) && is_array($rl[$k]) && ($v = hl_attrval($v, $rl[$k])) === 0){continue;}
  $a[$k] = str_replace('"', '&quot;', $v);
 }
}
if($nfr){$a['rel'] = isset($a['rel']) ? $a['rel']. ' nofollow' : 'nofollow';}

// rqd attr
static $eAR = array('area'=>array('alt'=>'area'), 'bdo'=>array('dir'=>'ltr'), 'form'=>array('action'=>''), 'img'=>array('src'=>'', 'alt'=>'image'), 'map'=>array('name'=>''), 'optgroup'=>array('label'=>''), 'param'=>array('name'=>''), 'script'=>array('type'=>'text/javascript'), 'textarea'=>array('rows'=>'10', 'cols'=>'50'));
if(isset($eAR[$e])){
 foreach($eAR[$e] as $k=>$v){
  if(!isset($a[$k])){$a[$k] = isset($v[0]) ? $v : $k;}
 }
}

// depr attrs
if($depTr){
 $c = array();
 foreach($a as $k=>$v){
  if($k == 'style' or !isset($aND[$k][$e])){continue;}
  if($k == 'align'){
   unset($a['align']);
   if($e == 'img' && ($v == 'left' or $v == 'right')){$c[] = 'float: '. $v;}
   elseif(($e == 'div' or $e == 'table') && $v == 'center'){$c[] = 'margin: auto';}
   else{$c[] = 'text-align: '. $v;}
  }elseif($k == 'bgcolor'){
   unset($a['bgcolor']);
   $c[] = 'background-color: '. $v;
  }elseif($k == 'border'){
   unset($a['border']); $c[] = "border: {$v}px";
  }elseif($k == 'bordercolor'){
   unset($a['bordercolor']); $c[] = 'border-color: '. $v;
  }elseif($k == 'clear'){
   unset($a['clear']); $c[] = 'clear: '. ($v != 'all' ? $v : 'both');
  }elseif($k == 'compact'){
   unset($a['compact']); $c[] = 'font-size: 85%';
  }elseif($k == 'height' or $k == 'width'){
   unset($a[$k]); $c[] = $k. ': '. ($v[0] != '*' ? $v. (ctype_digit($v) ? 'px' : '') : 'auto');
  }elseif($k == 'hspace'){
   unset($a['hspace']); $c[] = "margin-left: {$v}px; margin-right: {$v}px";
  }elseif($k == 'language' && !isset($a['type'])){
   unset($a['language']);
   $a['type'] = 'text/'. strtolower($v);
  }elseif($k == 'name'){
   if($C['no_deprecated_attr'] == 2 or ($e != 'a' && $e != 'map')){unset($a['name']);}
   if(!isset($a['id']) && preg_match('`[a-zA-Z][a-zA-Z\d.:_\-]*`', $v)){$a['id'] = $v;}
  }elseif($k == 'noshade'){
   unset($a['noshade']); $c[] = 'border-style: none; border: 0; background-color: gray; color: gray';
  }elseif($k == 'nowrap'){
   unset($a['nowrap']); $c[] = 'white-space: nowrap';
  }elseif($k == 'size'){
   unset($a['size']); $c[] = 'size: '. $v. 'px';
  }elseif($k == 'start' or $k == 'value'){
   unset($a[$k]);
  }elseif($k == 'type'){
   unset($a['type']);
   static $ol_type = array('i'=>'lower-roman', 'I'=>'upper-roman', 'a'=>'lower-latin', 'A'=>'upper-latin', '1'=>'decimal');
   $c[] = 'list-style-type: '. (isset($ol_type[$v]) ? $ol_type[$v] : 'decimal');
  }elseif($k == 'vspace'){
   unset($a['vspace']); $c[] = "margin-top: {$v}px; margin-bottom: {$v}px";
  }
 }
 if(count($c)){
  $c = implode('; ', $c);
  $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;'). '; '. $c. ';': $c. ';';
 }
}
// unique IDs
if($C['unique_ids'] && isset($a['id'])){
 if(!preg_match('`^[A-Za-z][A-Za-z0-9_\-.:]*$`', ($id = $a['id'])) or (isset($GLOBALS['hl_Ids'][$id]) && $C['unique_ids'] == 1)){unset($a['id']);
 }else{
  while(isset($GLOBALS['hl_Ids'][$id])){$id = $C['unique_ids']. $id;}
  $GLOBALS['hl_Ids'][($a['id'] = $id)] = 1;
 }
}
// xml:lang
if($C['xml:lang'] && isset($a['lang'])){
 $a['xml:lang'] = isset($a['xml:lang']) ? $a['xml:lang'] : $a['lang'];
 if($C['xml:lang'] == 2){unset($a['lang']);}
}
// for transformed tag
if(!empty($trt)){
 $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;'). '; '. $trt : $trt;
}
// return with empty ele's slash
if(empty($C['hook_tag'])){
 $aA = '';
 foreach($a as $k=>$v){$aA .= " {$k}=\"{$v}\"";}
 return "<{$e}{$aA}". (isset($eE[$e]) ? ' /' : ''). '>';
}
else{return $C['hook_tag']($e, $a);}
// eof
}

function hl_tag2(&$e, &$a, $t=1){
// transform tag
if($e == 'center'){$e = 'div'; return 'text-align: center;';}
if($e == 'dir' or $e == 'menu'){$e = 'ul'; return '';}
if($e == 's' or $e == 'strike'){$e = 'span'; return 'text-decoration: line-through;';}
if($e == 'u'){$e = 'span'; return 'text-decoration: underline;';}
static $fs = array('0'=>'xx-small', '1'=>'xx-small', '2'=>'small', '3'=>'medium', '4'=>'large', '5'=>'x-large', '6'=>'xx-large', '7'=>'300%', '-1'=>'smaller', '-2'=>'60%', '+1'=>'larger', '+2'=>'150%', '+3'=>'200%', '+4'=>'300%');
if($e == 'font'){
 $a2 = '';
 if(preg_match('`face\s*=\s*(\'|")([^=]+?)\\1`i', $a, $m) or preg_match('`face\s*=\s*([^"])(\S+)`i', $a, $m)){
  $a2 .= ' font-family: '. str_replace('"', '\'', trim($m[2])). ';';
 }
 if(preg_match('`color\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m)){
  $a2 .= ' color: '. trim($m[2]). ';';
 }
 if(preg_match('`size\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m) && isset($fs[($m = trim($m[2]))])){
  $a2 .= ' font-size: '. $fs[$m]. ';';
 }
 $e = 'span'; return ltrim($a2);
}
if($t == 2){$e = 0; return 0;}
return '';
// eof
}

function hl_tidy($t, $w, $p){
// Tidy/compact HTM
if(strpos(' pre,script,textarea', "$p,")){return $t;}
$t = str_replace(' </', '</', preg_replace(array('`(<\w[^>]*(?<!/)>)\s+`', '`\s+`', '`(<\w[^>]*(?<!/)>) `'), array(' $1', ' ', '$1'), preg_replace_callback(array('`(<(!\[CDATA\[))(.+?)(\]\]>)`sm', '`(<(!--))(.+?)(-->)`sm', '`(<(pre|script|textarea).*?>)(.+?)(</\2>)`sm'), create_function('$m', 'return $m[1]. str_replace(array("<", ">", "\n", "\r", "\t", " "), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), $m[3]). $m[4];'), $t)));
if(($w = strtolower($w)) == -1){
 return str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
}
$s = strpos(" $w", 't') ? "\t" : ' ';
$s = preg_match('`\d`', $w, $m) ? str_repeat($s, $m[0]) : str_repeat($s, ($s == "\t" ? 1 : 2));
$n = preg_match('`[ts]([1-9])`', $w, $m) ? $m[1] : 0;
$a = array('br'=>1);
$b = array('button'=>1, 'input'=>1, 'option'=>1);
$c = array('caption'=>1, 'dd'=>1, 'dt'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'isindex'=>1, 'label'=>1, 'legend'=>1, 'li'=>1, 'object'=>1, 'p'=>1, 'pre'=>1, 'td'=>1, 'textarea'=>1, 'th'=>1);
$d = array('address'=>1, 'blockquote'=>1, 'center'=>1, 'colgroup'=>1, 'dir'=>1, 'div'=>1, 'dl'=>1, 'fieldset'=>1, 'form'=>1, 'hr'=>1, 'iframe'=>1, 'map'=>1, 'menu'=>1, 'noscript'=>1, 'ol'=>1, 'optgroup'=>1, 'rbc'=>1, 'rtc'=>1, 'ruby'=>1, 'script'=>1, 'select'=>1, 'table'=>1, 'tfoot'=>1, 'thead'=>1, 'tr'=>1, 'ul'=>1);
ob_start();
if(isset($d[$p])){echo str_repeat($s, ++$n);}
$t = explode('<', $t);
echo ltrim(array_shift($t));
for($i=-1, $j=count($t); ++$i<$j;){
 $r = ''; list($e, $r) = explode('>', $t[$i]);
 $x = $e[0] == '/' ? 0 : (substr($e, -1) == '/' ? 1 : ($e[0] != '!' ? 2 : -1));
 $y = !$x ? ltrim($e, '/') : ($x > 0 ? substr($e, 0, strcspn($e, ' ')) : 0);
 $e = "<$e>"; 
 if(isset($d[$y])){
  if(!$x){echo "\n", str_repeat($s, --$n), "$e\n", str_repeat($s, $n);}
  else{echo "\n", str_repeat($s, $n), "$e\n", str_repeat($s, ($x != 1 ? ++$n : $n));}
  echo ltrim($r); continue;
 }
 $f = "\n". str_repeat($s, $n);
 if(isset($c[$y])){
  if(!$x){echo $e, $f, ltrim($r);}
  else{echo $f, $e, $r;}
 }elseif(isset($b[$y])){echo $f, $e, $r;
 }elseif(isset($a[$y])){echo $e, $f, ltrim($r);
 }elseif(!$y){echo $f, $e, $f, ltrim($r);
 }else{echo $e, $r;}
}
$t = preg_replace('`[\n]\s*?[\n]+`', "\n", ob_get_contents());
ob_end_clean();
if(($l = strpos(" $w", 'r') ? (strpos(" $w", 'n') ? "\r\n" : "\r") : 0)){
 $t = str_replace("\n", $l, $t);
}
return str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), array('<', '>', "\n", "\r", "\t", ' '), $t);
// eof
}

function hl_version(){
// version
return '1.1.5';
// eof
}

function kses($t, $h, $p=array('http', 'https', 'ftp', 'news', 'nntp', 'telnet', 'gopher', 'mailto')){
// kses compat
foreach($h as $k=>$v){
 $h[$k]['n']['*'] = 1;
}
$C['cdata'] = $C['comment'] = $C['make_tag_strict'] = $C['no_deprecated_attr'] = $C['unique_ids'] = 0;
$C['keep_bad'] = 1;
$C['elements'] = count($h) ? strtolower(implode(',', array_keys($h))) : '-*';
$C['hook'] = 'kses_hook';
$C['schemes'] = '*:'. implode(',', $p);
return htmLawed($t, $C, $h);
// eof
}

function kses_hook($t, &$C, &$spec){
// kses compat
return $t;
// eof
}
?>