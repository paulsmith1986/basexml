<?php
//错误显示
ini_set( 'display_errors', 1 );
define( 'ROOT_PATH', dirname( dirname( __FILE__ ) ). DIRECTORY_SEPARATOR );
require ROOT_PATH .'web/include/smarty/libs/Smarty.class.php';
require ROOT_PATH .'web/lib/lib_core.php';
require ROOT_PATH .'web/etc/config.php';
$control_name = isset ( $_GET[ 'c' ] ) ? $_GET[ 'c' ] : 'main';
$action_name = isset ( $_GET[ 'a' ] ) ? $_GET[ 'a' ] : 'index';
ob_start();
try
{
	action_dispatch( $control_name, $action_name );
}
catch( Exception $excep_obj )
{
	do_with_excp( $excep_obj );
}
page_end();