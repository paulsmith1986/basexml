<?php
require_once ROOT_PATH .'web/mod/protocol_bin.php';
require_once ROOT_PATH .'web/mod/protocol_xml.php';
require_once ROOT_PATH .'web/mod/protocol_so.php';
require_once ROOT_PATH .'web/mod/protocol_cpp.php';
require_once ROOT_PATH .'web/mod/protocol_php.php';
require_once ROOT_PATH .'web/mod/protocol_common.php';
/**
 * 主入口
 */
function main_index_control()
{
	$data = array( 'result' => 1 );
	view_html( 'main', $data );
}

/**
 * 生成client协议
 */
function main_proto_control()
{
	$build_path = ROOT_PATH .'build/so/';
	$client_xml = array(
		ROOT_PATH .'protocol/xml/php_server',
		ROOT_PATH .'protocol/xml/php_php',
	);
	tool_so_protocol( $build_path, $client_xml );
	tool_bin_protocol_size_def( $build_path );
	tool_so_protocol_encode_switch( $client_xml, $build_path .'so_encode.h' );
	tool_so_protocol_decode_switch( $client_xml, $build_path .'so_decode.h' );
	$client_xml = array(
		ROOT_PATH .'protocol/xml/php_server/c.xml',
	);
	tool_bin_protocol_client( $build_path, $client_xml );
	$data = array(
		'code_type'			=> 'no',
	);
	view_html( 'main', $data );
}

/**
 * 主入口
 */
function main_test_control()
{
	$data = array( 'result' => 1 );
	$clent_xml = array( ROOT_PATH .'protocol/xml/demo' );
	tool_protocol_xml( $clent_xml, 'all' );
	tool_protocol_cpp( ROOT_PATH .'build/build/' );

}

/**
 * 运行代码
 */
function main_run_code_control( )
{
	if( !empty( $_POST['code'] ) )
	{
		$code = $_POST[ 'code' ];
		eval( $code );
	}
	else
	{
		$code = '';
	}
	view_html( 'main', array( 'run_code' => $code ) );
}