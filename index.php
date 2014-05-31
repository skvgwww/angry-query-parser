<?php

require_once '/lib/AQP-init.php';


/* * I initialize AngryQueryParser * */
$images_download_dir = $_SERVER["DOCUMENT_ROOT"] . "/images";
$files_download_dir = $_SERVER["DOCUMENT_ROOT"] . "/files";

$go = new AngryQueryParser( false, $images_download_dir, $files_download_dir );

/* II Parse page or pages */
$meta = true;
$page = $go->getPage(
        "http://www.site.domain/", 
        array(
            "logo" => array("#logo-container img", "src"),
            "content" => "div.feed-item-main-content"
        ), 
        $meta
);


var_dump("<pre>", $page);
exit();