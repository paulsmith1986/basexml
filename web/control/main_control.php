<?php
require_once ROOT_PATH .'web/YGP/protocol_bin.php';
require_once ROOT_PATH .'web/YGP/protocol_xml.php';
require_once ROOT_PATH .'web/YGP/protocol_so.php';
require_once ROOT_PATH .'web/YGP/protocol_cpp.php';
require_once ROOT_PATH .'web/YGP/protocol_php.php';
require_once ROOT_PATH .'web/YGP/protocol_common.php';
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
		ROOT_PATH .'web/YGP/basexml/fpm.xml',
	);
	tool_so_protocol( $build_path, $client_xml );
	tool_bin_protocol_size_def( $build_path );
	tool_so_protocol_encode_switch( $client_xml, $build_path .'so_encode.h' );
	tool_so_protocol_decode_switch( $client_xml, $build_path .'so_decode.h' );
	$client_xml = array(
		ROOT_PATH .'web/YGP/basexml/proxy.xml',
	);
	tool_bin_protocol_client( $build_path, $client_xml );
	$data = array(
		'code_type'			=> 'no',
	);

	$build_path = ROOT_PATH .'build/tlog/';
	$server_xml = array(
		ROOT_PATH .'web/YGP/basexml/proxy.xml',
	);
	tool_bin_protocol_server( $build_path, $server_xml );
	tool_bin_protocol_size_def( $build_path );

	$build_path = ROOT_PATH .'build/php/';
	if ( !is_dir( $build_path ) )
	{
		show_excp( 'Build path:'. $build_path .' not found' );
	}
	tool_protocol_xml( $server_xml, 'all' );
	$build_arg = array( 'file_name' => 'proto_server', 'is_simulate' => false, 'unpack_mod' => 1, 'pack_mod' => 2 );
	tool_protocol_php_build( $build_path, $build_arg );
	$build_arg = array( 'file_name' => 'proto_client', 'is_simulate' => false, 'pack_mod' => 1, 'unpack_mod' => 2, 'id_name_type' => 2 );
	tool_protocol_php_build( $build_path, $build_arg );
	$client_map_file = $build_path .'proto_map_client.php';
	$server_map_file = $build_path .'proto_map_server.php';
	view_html( 'main', $data );
}

/**
 * 生成进程协议
 */
function main_proto_process_control()
{
	$build_path = ROOT_PATH .'build/process/';
	$server_xml = array(
		ROOT_PATH .'web/YGP/basexml/process.xml',
	);
	tool_bin_protocol_server( $build_path, $server_xml );
	tool_bin_protocol_encode_def( 'client', $build_path );
	tool_bin_protocol_size_def( $build_path );
	$arg = array(
		'register_func_name'		=> 'yile_process_init_protocol',
		'register_api'				=> 'yile_process_register_protocol',
		'handle_prefix'				=> 'yile_process',
		'handle_callback_prefix'	=> 'yile_process',
		'header'					=> 'yile_process.h',
	);
	tool_bin_protocol_register( $arg, $build_path, 'server' );
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