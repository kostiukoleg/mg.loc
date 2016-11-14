<?php 
/**
 *  mg-start.php    ,     
 *     .
 *
 *   CMS,     .
 * - DB -     ;
 * - Storage -     ;
 * - MG -    ;
 * - URL -     ;
 * - PM -     .
 * - User -      ;
 * - Mailer -    .
 *
 * @author   <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Files
 */
$GLOBALS['count'] = 1;
ini_set('session.serialize_handler', 'php');
MG::getConfigIni();
//   CMS.
DB::init();
Storage::init();
session_start();
PM::init();
MG::init();
URL::init();
User::init();
Mailer::init();
Urlrewrite::init();
//    ,   ,    .
if (MG::isDowntime()) {
    require_once 'downTime.html';
    exit;
}
//    .
MG::logReffererInfo();
//  index.php  .
PM::includePlugins();
//     .
MG::createHook('mg_start');
//  .
$moguta = new Moguta;
$moguta = $moguta->run();
//    ,     .
echo PM::doShortcode(MG::printGui($moguta));
//       .
MG::createHook('mg_end', true, $moguta);
//     .
if (DEBUG_SQL) {
    echo DB::console();
}
//   .
Storage::close();
session_write_close();