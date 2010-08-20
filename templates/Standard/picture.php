<?php
    $image = imagecreatefromjpeg('images/site/menu_empty.jpg');
    $anfang = imagecolorallocate($image, 171, 155, 35);
    $red = imagecolorallocate($image, 255, 255, 255);
    ImageTTFText($image,10,0,10,16,$anfang,'images/verdanai.ttf',substr(base64_decode($_GET['text']),1,1));
    ImageTTFText($image,10,0,18,16,$red,'images/verdanai.ttf',substr(base64_decode($_GET['text']),2));
    header("Content-type: image/jpeg");
    imagejpeg($image,'',100);
    imagedestroy($image);
?>