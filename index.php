<?php
// Fehlerausgabe festlegen
error_reporting(E_ALL);
    if(file_exists('install.php') OR file_exists('update.php')) {
    	if(!file_exists('inc/db.daten.php')) {
    	    header('Location: install.php');
    	} else {
        echo 'Sie müssen die "install.php" und die "update.php" aus dem Verzeichnis löschen!';
        die();
    	}
    }
// Zeitmessung starten
$startzeit = explode(' ',substr(microtime(),1));
$startzeit = $startzeit[1]+$startzeit[0];
// SESSION Lebenszeit auf 30 min. setzen
session_cache_expire(30);


// SESSION starten
session_start();

// Ausgabenspeicher starten
//ob_start("ob_gzhandler");
// Helper function to detect if GZip is supported by client!
// If not supported the tricks are pointless
function acceptsGZip(){
    $accept = str_replace(" ","",
        strtolower($_SERVER['HTTP_ACCEPT_ENCODING'])
    );
    $accept = explode(",",$accept);
    return in_array("gzip",$accept);
}
// -------------------------------------------------------------------------------------
function playWithHtml($OutputHtml){
    // This will mess up HTML code like my site has done!
    // View the source to understand! All ENTERs are removed.
    // If your site has PREformated code this will break it!
    // Use regexp to find it and save it and place it back ...
    // or just uncomment the next line to keep enters
    // return $OutputHtml;
    return preg_replace("/\s+/"," ",$OutputHtml);
}
// -------------------------------------------------------------------------------------
function obOutputHandler($OutputHtml){
    global $EnableGZipEncoding;
    //-- Play with HTML before output
    //$OutputHtml = playWithHtml($OutputHtml);
    //-- If GZIP not supported compression is pointless.
    // If headers were sent we can not signal GZIP encoding as
    // we will mess it all up so better drop it here!
    // If you disable GZip encoding to use plain output buffering we stop here too!
    if(!acceptsGZip() || headers_sent() || !$EnableGZipEncoding) return $OutputHtml;
    //-- We signal GZIP compression and dump encoded data
    $OutputHtml .= '<!-- Gzip compressed | old-size: '.goodsize(strlen($OutputHtml)).' new-size: '.goodsize(strlen(gzencode($OutputHtml))).' -->';
    header("Content-Encoding: gzip");
    return gzencode($OutputHtml);
}
// This code has to be before any output from your site!
// If output exists uncompressed HTML will be delivered!
ob_start("obOutputHandler");
// -------------------------------------------------------------------------------------

// Datei einbinden die notwendige Files lï¿½d
include('inc/include.php');

// Datei einbinden die Prï¿½fungen und Updates durchfï¿½hrt
include('inc/checks.php');
// Index Datei laden und Platzhalter ersetzen
$index = file_get_contents('templates/'.DESIGN.'/index.html');
$search = array(
	'{title}', 
	'{leftmenu}', 
	'{rightmenu}', 
	'{content}', 
	'{javascript}', 
	'{footer}', 
	'{DESIGN}',
	'style.css',
	'{langchanger}');
$replace = array(
	SITE_TITLE, 
	'<?php echo $leftmenu; ?>', 
	'<?php echo $rightmenu; ?>', 
	'<?php show_content(); ?>', 
	'<?php javascripts(); ?>', 
	'<?php footer(); ?>', 
	DESIGN,
	'templates/'.DESIGN.'/style.css',
	'<?php lang_changer(); ?>');
	
$index = str_replace($search, $replace, $index);
eval('?>'.$index);
$db->query('UPDATE '.DB_PRE.'ecp_online SET SIDDATA = \''.strsave(serialize($_SESSION)).'\' WHERE SID = \''.session_id().'\' LIMIT 1');

print_r($_SESSION);
/*
echo '<br /><br />';
print_r($_COOKIE);
print_r($_SERVER);
/*$contentalt = ob_get_length();
$content = ob_gzhandler(ob_get_contents(), 1);
echo goodsize(strlen($content)).' ALT: '.goodsize($contentalt);*/
//print_r($_COOKIE);
// -------------------------------------------------------------------------------------
$EnableGZipEncoding = true;
// -------------------------------------------------------------------------------------
$db->close();
?>