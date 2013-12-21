<?php
/**
 * 生成as文件
 */
function tool_as_amf_protocol( $build_path, $xml_path )
{
	tool_protocol_xml( $xml_path, 'all' );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => &$node_rs )
	{
		$node_rs[ 'static_mod' ] = $node_rs[ 'module' ];
		$node_rs[ 'module' ] = '';
	}
	tool_protocol_as_common( 'com.yile.tkd.model.staticTable', true, array( 1 => '', 2 => '' ) );
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$code = tool_as_amf_protocol_struct_code( $pid, $rs, $items );
		$GLOBALS[ 'all_as_file' ][ $rs[ 'name' ] ] = $code;
		$class_fix = $GLOBALS[ 'as_class_affix' ][ $rs[ 'proto_type' ] ];
		$file_name = tool_protocol_as_struct_name( $rs, $class_fix );
		$file = $build_path . $file_name .'.as';
		file_put_contents( $file, $code );
		echo '生成文件:'. $file, "\n";
	}
}

/**
 * as static类
 */
function tool_as_amf_protocol_struct_code( $pid, $rs, $items )
{
	$str = "package ". $GLOBALS[ 'main_pack' ] ."\n";
	$str .= "{\n";
	$str .= "\timport flash.utils.ByteArray;\n";
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\timport com.yile.tkd.model.data.IDataStatic;\n";
	}
	if ( !empty( $GLOBALS[ 'as_file_import' ][ $pid ] ) )
	{
		$import_arr = array_keys( $GLOBALS[ 'as_file_import' ][ $pid ] );
		$str .= "\t". join( "\t", $import_arr );
	}

	$class_fix = $GLOBALS[ 'as_class_affix' ][ $rs[ 'proto_type' ] ];
	$str .= "\tpublic class ". tool_protocol_as_struct_name( $rs, $class_fix );
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= " implements IDataStatic";
	}
	$str .= "\n\t{\n";
	$str .= join( ";\n", $GLOBALS[ 'as_property_code' ][ $pid ] ) .";\n";
	$GLOBALS[ 'list_for_var' ] = array();
	$GLOBALS[ 'list_for_var_parse' ] = array();
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$read_byte_after = array();
	$str .= "\t\tpublic function init( data:Array ):void\n";
	$str .= "\t\t{\n";
	$tab_str = "\t\t\t";
	foreach ( $items as $key => $item_rs )
	{
		$item_name = $item_rs[ 'item_name' ];
		switch ( $item_rs[ 'type' ] )
		{
			case 'list':
				$str .= tool_as_amf_protocol_list_loop_parse( $item_rs[ 'sub_id' ], "this.". $item_name, "\t\t\t", 0, 'data[ '. $key .' ]', $item_name );
			break;
			case 'struct':
				$str .= $tab_str ."this.". $item_name .".init( data[ ". $key ." ] );\n";
			break;
			case 'byte':
				$str .= $tab_str ."this.". $item_name ." = Game.tool.base64_decode( data[ ". $key ." ] );\n";
			break;
			default:
				$str .= $tab_str ."this.". $item_name ." = data[ ". $key ."];\n";
			break;
		}
	}
	$str .= "\t\t}\n";
	$str .= "\t}\n";
	$str .= "}\n";
	return $str;
}

/**
 * 解析list
 */
function tool_as_amf_protocol_list_loop_parse( $list_id, $parent_var, $tab_str, $rank, $data_var, $list_name )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$len_var = "vector_len". $rank;
	$for_var = "i". $rank;
	$value_var = "tmp_value_". $list_name . $rank;
	$str = '';
	if ( !isset( $GLOBALS[ 'list_for_var_parse' ][ $len_var ] ) )
	{
		$str .= $tab_str ."var ". $len_var .":int;\n";
		$GLOBALS[ 'list_for_var_parse' ][ $len_var ] = true;
	}
	$str .= $tab_str . $len_var ." = ( ". $data_var ." is Array ) ? ". $data_var .".length : 0;\n";
	$str .= $tab_str;
	if ( $rank > 0 )
	{
		$str .= "var ". $parent_var .":". $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ];
	}
	else
	{
		$str .= $parent_var;
	}
	$str .= " = new ". $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] ."( ". $len_var ." );\n";
	if ( !isset( $GLOBALS[ 'list_for_var_parse' ][ $for_var ] ) )
	{
		$str .= $tab_str ."var ". $for_var .":int;\n";
		$GLOBALS[ 'list_for_var_parse' ][ $for_var ] = true;
	}
	$str .= $tab_str . "for( ". $for_var ." = 0; ". $for_var ." < ". $len_var ."; ++". $for_var ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$clas_name = tool_protocol_as_struct_name( $struct );
			$str .= $tab_str ."\tvar ". $value_var .":". $clas_name ." = new ". $clas_name ."();\n";
			$str .= $tab_str ."\t". $value_var .".init( ". $data_var ."[ ". $for_var ." ] );\n";
		break;
		case 'byte':
			$str .= $tab_str ."\tvar ". $value_var .":ByteArray = Game.tool.base64_decode( ". $data_var .".[". $for_var ."] );\n";
		break;
		case 'list':
			$str .= tool_as_amf_protocol_list_loop_parse( $list_rs[ 'sub_id' ], $value_var, $tab_str ."\t", $rank + 1, $data_var .'[ '. $for_var .' ]', $list_name );
		break;
		default:
			$func_name = tool_as_protocol_byte_arr_read_func( $list_rs[ 'type' ] );
			$str .= $tab_str . "\tvar ". $value_var .":". $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ] ." = ". $data_var ."[ ". $for_var ." ];\n";
		break;
	}
	$str .= $tab_str . "\t". $parent_var ."[ ". $for_var ." ] = ". $value_var .";\n";
	$str .= $tab_str ."}\n";
	return $str;
}