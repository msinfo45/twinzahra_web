<?php

defined('TWINZAHRA') or die('Error: restricted access');
//Error_Reporting(E_ALL & ~E_NOTICE);
@ini_set('session.use_trans_sid', '0');
@ini_set('arg_separator.output', '&amp;');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
$rootpath = isset($rootpath) ? $rootpath : '../';

/*
-----------------------------------------------------------------
Автозагрузка Классов
-----------------------------------------------------------------
*/
spl_autoload_register('autoload');
function autoload($name) {
    global $rootpath;
    $file = $rootpath . 'include/classes/' . $name . '.php';
    if (file_exists($file))
        require_once($file);
}

/*
-----------------------------------------------------------------
Инициализируем Ядро системы
-----------------------------------------------------------------
*/
$core = new core() or die('Error: Core System');
unset($core);

/*
-----------------------------------------------------------------
Получаем системные переменные
-----------------------------------------------------------------
*/
$ip = core::$ip;                                          // Адрес IP
$agn = core::$user_agent;                                 // User Agent
$set = core::$system_set;                                 // Системные настройки
$lng = core::$lng;                                        // Фразы языка
$is_mobile = core::$is_mobile;                            // Определение мобильного браузера
$home = $set['homeurl'];                                  // Домашняя страница

/*
-----------------------------------------------------------------
Получаем пользовательские переменные
-----------------------------------------------------------------
*/
$user_id = core::$user_id;                                // Идентификатор пользователя
$rights = core::$user_rights;                             // Права доступа
$datauser = core::$user_data;                             // Все данные пользователя
$set_user = core::$user_set;                              // Пользовательские настройки
$ban = core::$user_ban;                                   // Бан
$login = isset($datauser['name']) ? $datauser['name'] : false;
$kmess = $set_user['kmess'] > 4 && $set_user['kmess'] < 11 ? $set_user['kmess'] : 10;

function validate_referer() {
    if($_SERVER['REQUEST_METHOD']!=='POST') return;
    if(@!empty($_SERVER['HTTP_REFERER'])) {
        $ref = parse_url(@$_SERVER['HTTP_REFERER']);
        if($_SERVER['HTTP_HOST']===$ref['host']) return;
    }
    die('Invalid request');
}
$validate_referer = isset($validate_referer) ? $validate_referer : true;
if ($rights AND $validate_referer) {
    validate_referer();
}

/*
-----------------------------------------------------------------
Получаем и фильтруем основные переменные для системы
-----------------------------------------------------------------
*/
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : false;
$user = isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : false;
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
$mod = isset($_REQUEST['mod']) ? trim($_REQUEST['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);
$headmod = isset($headmod) ? $headmod : '';
$domredirect = isset($domredirect) ? $domredirect : 'on';

$siteusername = isset($_REQUEST['siteusername']) ? trim($_REQUEST['siteusername']) : "";

if($set['blogwildcard'] == 1 && $siteusername != "") {
header("location: ".$set['homeurl']);
exit;
}
$___url1 = "http://".strtolower($_SERVER['HTTP_HOST']);
if (($domredirect == "on" AND $___url1 != strtolower($set['homeurl'])) || ($set['blogwildcard'] == 0 && $siteusername == "" && $___url1 != strtolower($set['homeurl']))) {
header("location: ".$set['homeurl']);
exit;
}
if($set['blogwildcard'] == 1 || $siteusername != "") {
if ($siteusername != "") {
$___url1 = "http://".$siteusername.".".substr($___url1,7);
}
$___site = mysql_query("SELECT * FROM `blog_sites` WHERE `url1` = '".mysql_real_escape_string($___url1)."' OR `url2` = '".mysql_real_escape_string($___url1)."'");
if (mysql_num_rows($___site) == 0 AND $___url1 != strtolower($set['homeurl'])) {
header("location: ".$set['homeurl']);
exit;
}
}
/*
-----------------------------------------------------------------
Показываем Дайджест
-----------------------------------------------------------------
*/
if ($user_id && $datauser['lastdate'] < (time() - 3600) && $set_user['digest'] && $headmod == 'mainpage')
    header('Location: ' . $set['homeurl'] . '/index.php?act=digest&last=' . $datauser['lastdate']);

/*
-----------------------------------------------------------------
Буфферизация вывода
-----------------------------------------------------------------
*/
if(!isset($set['gzip'])) {
    mysql_query("INSERT INTO `cms_settings` SET `key` = 'gzip', `val` = '1'");
    $set['gzip'] = 1;
}
if ($set['gzip'] && @extension_loaded('zlib')) {
    @ini_set('zlib.output_compression_level', 3);
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

// Blog cron job
mysql_query("UPDATE `blog_posts` SET `draft`='no' WHERE `draft`='yes' AND `time` < '".time()."'");