<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '128M');

//AngryCurl directory
define('AC_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR .'class'.DIRECTORY_SEPARATOR.'AngryCurl'.DIRECTORY_SEPARATOR);
//proxy_list.txt location directory
define('PROXY_LIST_DIR', AC_DIR.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR);
//useragent_list.txt location directory
define('USER_AGENT_LIST_DIR', AC_DIR.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR);
require_once AC_DIR.'classes'.DIRECTORY_SEPARATOR.'RollingCurl.class.php';
require_once AC_DIR.'classes'.DIRECTORY_SEPARATOR.'AngryCurl.class.php';
require_once 'class'.DIRECTORY_SEPARATOR.'phpQuery'.DIRECTORY_SEPARATOR.'phpQuery.php';
require_once 'class'.DIRECTORY_SEPARATOR.'AngryQueryParser.php';