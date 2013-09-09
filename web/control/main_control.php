<?php
require_once ROOT_PATH .'web/mod/protocol_bin.php';
require_once ROOT_PATH .'web/mod/protocol_xml.php';
require_once ROOT_PATH .'web/mod/protocol_so.php';
require_once ROOT_PATH .'web/mod/protocol_cpp.php';
require_once ROOT_PATH .'web/mod/protocol_simulate.php';
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
function main_proto_c_control()
{
	$build_path = ROOT_PATH .'build/demo/';
	$clent_xml = array(
		ROOT_PATH .'protocol/xml/client_server',
		ROOT_PATH .'protocol/xml/client_php',
		ROOT_PATH .'protocol/xml/passport',
		ROOT_PATH .'protocol/xml/static' => true
	);
	tool_bin_protocol_client( $build_path, $clent_xml );
	tool_build_all( $build_path );
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