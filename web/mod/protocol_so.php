<?php
/**
 * 发送的switch
 */
function tool_so_protocol_send_switch( $xml_path, $file_name, $type = 1 )
{
	$base_name = strtoupper( basename( $file_name, '.h' ) );
	$def_name = 'PROTO_'. $base_name .'_H';
	$str = "#ifndef ". $def_name ."\n";
	$str .= "#define ". $def_name ."\n";
	tool_protocol_xml( $xml_path, 'all' );
	$var_name = '_tmp_protocol_pack';
	$str .= tool_line( "#define php_send_im_is_error() 0 != ". $var_name .".error_code\n" );
	$str .= "//打包php数据并发出去\n";
	$str .= tool_tab_line( "#define php_send_im_pack( fd, pack_id, data_arr )" );
	$str .= tool_tab_line( "char *error_msg = \"No protocol data!\";", 1 );
	$str .= tool_tab_line( "protocol_result_t ". $var_name .";", 1 );
	$str .= tool_tab_line( $var_name .".pos = 0;", 1 );
	$str .= tool_tab_line( $var_name .".is_resize = 0;", 1 );
	$str .= tool_tab_line( "switch( pack_id )", 1 );
	$str .= tool_tab_line( "{", 1 );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		if ( !( $rs[ 'proto_type' ] & $type ) )
		{
			continue;
		}
		$len_name = strtoupper( "proto_size_". $rs[ 'name'] );
		$char_len = "SEND_POOL_". strtoupper( $rs[ 'name' ] );
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$str .= tool_tab_line( "case ". $rs[ 'struct_id' ] .":", 2 );
		$str .= tool_tab_line( "{", 2 );
		$str .= tool_tab_line( "char ". $char_len ."[ ". $len_name ." ];", 3 );
		$str .= tool_tab_line( $var_name .".str = ". $char_len .";", 3 );
		$str .= tool_tab_line( $var_name .".max_pos = ". $len_name .";", 3 );
		$tmp_char = "sowrite_". $rs[ 'name' ] ."( &". $var_name;
		if ( !empty( $items ) )
		{
			$tmp_char .= ", data_arr";
			$str .= tool_tab_line( "if( NULL != data_arr )", 3 );
			$str .= tool_tab_line( "{", 3 );
			$tmp_char .= " );";
			$str .= tool_tab_line( $tmp_char, 4 );
			$str .= tool_tab_line( "}", 3 );
		}
		else
		{
			$tmp_char .= " );";
			$str .= tool_tab_line( $tmp_char, 3 );
		}
		$str .= tool_tab_line( "if ( 0 == ". $var_name .".error_code )", 3 );
		$str .= tool_tab_line( "{", 3 );
		$str .= tool_tab_line( "protocol_send_pack( fd, &". $var_name ." );", 4 );
		$str .= tool_tab_line( "}", 3 );
		$str .= tool_tab_line( "}", 2 );
		$str .= tool_tab_line( "break;", 2 );
	}
	$str .= tool_tab_line( "default:", 2 );
	$str .= tool_tab_line( "_tmp_protocol_pack.error_code = PROTO_UNKOWN_PACK;", 3 );
	$str .= tool_tab_line( "zend_error( E_WARNING, \"Unkown pack_id:%d\", pack_id );", 3 );
	$str .= tool_tab_line( "break;", 2 );
	$str .= tool_tab_line( "}", 1 );
	$str .= tool_tab_line( "if( ". $var_name .".is_resize )", 1 );
	$str .= tool_tab_line( "{", 1 );
	$str .= tool_tab_line( "free( ". $var_name .".str );", 2 );
	$str .= "\t}\n";
	$str .= "#endif";
	file_put_contents( $file_name, $str );
	echo '生成so_send文件:', $file_name, "\n";
}

/**
 * encode的switch
 */
function tool_so_protocol_encode_switch( $xml_path, $file_name, $type = 1 )
{
	$base_name = strtoupper( basename( $file_name, '.h' ) );
	$def_name = 'PROTO_'. $base_name .'_H';
	tool_protocol_xml( $xml_path, 'all' );
	$pack_str = "#ifndef ". $def_name ."\n";
	$pack_str .= "#define ". $def_name ."\n";
	$pack_str .= "//打包数据并返回\n";
	$pack_str .= tool_tab_line( "#define php_pack_protocol_data( pack_id, data_arr, pack_name )" );
	$pack_str .= tool_tab_line( "char *error_msg = \"No protocol data!\";", 1 );;
	$pack_str .= tool_tab_line( "switch( pack_id )", 1 );
	$pack_str .= tool_tab_line( "{", 1 );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		if ( !( $rs[ 'proto_type' ] & $type ) )
		{
			continue;
		}
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$pack_str .= tool_tab_line( "case ". $rs[ 'struct_id' ] .":", 2 );
		$pack_str .= tool_tab_line( "{", 2 );
		$tmp_char = "sowrite_". $rs[ 'name' ] ."( &pack_name";
		if ( !empty( $items ) )
		{
			$tmp_char .= ", data_arr";
			$pack_str .= tool_tab_line( "if( NULL == data_arr )", 3 );
			$pack_str .= tool_tab_line( "{", 3 );
			$pack_str .= tool_tab_line( "pack_name.error_code = PROTO_PACK_DATA_MISS;", 4 );
			$pack_str .= tool_tab_line( "}", 3 );
			$pack_str .= tool_tab_line( "else", 3 );
			$pack_str .= tool_tab_line( "{", 3 );
			$tmp_char .= " );";
			$pack_str .= tool_tab_line( $tmp_char, 4 );
			$pack_str .= tool_tab_line( "}", 3 );
		}
		else
		{
			$tmp_char .= " );";
			$pack_str .= tool_tab_line( $tmp_char, 3 );
		}
		$pack_str .= tool_tab_line( "}", 2 );
		$pack_str .= tool_tab_line( "break;", 2 );
	}
	$pack_str .= tool_tab_line( "default:", 2 );
	$pack_str .= tool_tab_line( "pack_name.error_code = PROTO_UNKOWN_PACK;", 3 );
	$pack_str .= tool_tab_line( "zend_error( E_WARNING, \"Unkown pack_id:%d\", pack_id );", 3 );
	$pack_str .= tool_tab_line( "break;", 2 );
	$pack_str .= "\t}\n";
	$pack_str .= "#endif";
	file_put_contents( $file_name, $pack_str );
	echo '生成so encode switch文件:', $file_name, "\n";
}

/**
 * decode的switch
 */
function tool_so_protocol_decode_switch( $xml_path, $file_name, $type = 2 )
{
	$base_name = strtoupper( basename( $file_name, '.h' ) );
	$def_name = 'PROTO_'. $base_name .'_H';
	$str = "#ifndef ". $def_name ."\n";
	$str .= "#define ". $def_name ."\n";
	tool_protocol_xml( $xml_path, 'all' );
	$str .= "//解包数据成php数组\n";
	$str .= tool_tab_line( "#define php_unpack_protocol_data( pack_id, data_arr, tmp_result )" );
	$str .= tool_tab_line( "switch( pack_id )", 1 );
	$str .= tool_tab_line( "{", 1 );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		if ( !( $rs[ 'proto_type' ] & $type ) )
		{
			continue;
		}
		$str .= tool_tab_line( "case ". $rs[ 'struct_id' ] .":", 2 );
		$str .= tool_tab_line( "{", 2 );
		$str .= tool_tab_line( "soread_". $rs[ 'name' ] ."( data_arr, tmp_result );", 3 );
		$str .= tool_tab_line( "}", 2 );
		$str .= tool_tab_line( "break;", 2 );
	}
	$str .= tool_tab_line( "default:", 2 );
	$str .= tool_tab_line( "zend_error( E_WARNING, \"Unkown pack_id:%d\", pack_id );", 3 );
	$str .= tool_tab_line( "break;", 2 );
	$str .= "\t}\n";
	$str .= "#endif";
	file_put_contents( $file_name, $str );
	echo '生成so_decode文件:', $file_name, "\n";
}
/**
 * 生成h文件
 */
function tool_so_protocol_head_file()
{
	$protocol_h = "#ifndef YGP_PROTOCOL_SO_H\n#define YGP_PROTOCOL_SO_H\n";
	$protocol_h .= "#include <stdint.h>\n";
	$protocol_h .= "#include <stdlib.h>\n";
	$protocol_h .= "#include \"yile_proto.h\"\n";
	$protocol_h .= "#include \"php.h\"\n";
	$protocol_h .= "#include \"proto_size.h\"\n";
	$protocol_h .= "#pragma pack(1)\n";
	$protocol_h .= join( '', $GLOBALS[ 'typedef_all' ] );
	$protocol_h .= join( '', $GLOBALS[ 'typedef_detail' ] );
	$protocol_h .= "#pragma pack()\n";
	//打包head文件
	$protocol_h .= join( '', $GLOBALS[ 'php_struct_define' ] );
	//解析数据的head文件
	$protocol_h .= join( '', $GLOBALS[ 'parse_php_struct_define' ] );
	$protocol_h .= "#endif";
	return $protocol_h;
}

/**
 * 生成so版协议
 */
function tool_so_protocol( $build_path, $xml_path )
{
	tool_protocol_xml( $xml_path, 'all' );
	tool_bin_protocol_common( 'proto_so' );
	$GLOBALS[ 'php_struct_define' ] = array();
	$GLOBALS[ 'php_struct_code' ] = array();
	$GLOBALS[ 'parse_php_struct_define' ] = array();
	$GLOBALS[ 'parse_php_struct_code' ] = array();
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		if ( empty( $items ) )
		{
			continue;
		}
		//请求协议
		if ( $rs[ 'proto_type' ] & 1 )
		{
			$code = tool_so_protocol_struct_code( $pid, $rs, $items );
			$GLOBALS[ 'php_struct_code' ][] = $code;
		}
		//解析协议
		if ( $rs[ 'proto_type' ] & 2 )
		{
			$parse_code = tool_so_protocol_struct_parse_code( $pid, $rs, $items );
			$GLOBALS[ 'parse_php_struct_code' ][] = $parse_code;
		}
	}
	$head_str = tool_so_protocol_head_file();
	$h_file = $build_path .'proto_so.h';
	$c_file = $build_path .'proto_so.c';
	$c_code = "#include \"proto_so.h\"\n". join( '', $GLOBALS[ 'php_struct_code' ] );
	$c_code .= join( '', $GLOBALS[ 'parse_php_struct_code' ] );
	file_put_contents( $c_file, $c_code );
	file_put_contents( $h_file, $head_str );
	echo "\n生成二进制协议头文件", $h_file, "\n";
	echo "\n生成二进制生成协议文件", $c_file, "\n";
}

/**
 * 生成struct的c代码
 */
function tool_so_protocol_struct_code( $pid, $rs, $items )
{
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$read_byte_after = array();
	$tmp_int_type_arr = array();
	$head_char = "\n/**\n * 生成 ". $rs[ 'desc' ] ."\n */\nvoid sowrite_". $rs[ 'name' ] ."( protocol_result_t *all_result, HashTable *data_hash )";
	$GLOBALS[ 'php_struct_define' ][] = $head_char .";\n";
	$str = $head_char ."\n{\n";
	$str .= "\tzval **tmp_data;\n";
	$proto_name_var = "proto_so_". $rs[ 'name' ];
	//如果是主协议
	if ( !$rs[ 'is_sub' ] )
	{
		//$GLOBALS[ 'struct_define' ][] = "#define write_". $rs[ 'name' ] ."( a, b ) ". $rs[ 'name' ] ."( a, b, NULL )";
		$str .= "\tall_result->pos = 0;\n";
		$str .= "\tpacket_head_t packet_info;\n";
		$str .= "\tpacket_info.size = 0;\n";
		$str .= "\tpacket_info.pack_id = ". $rs[ 'struct_id' ] .";\n";
		$str .= "\tyile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );\n";
	}

	//如果全是定长型
	if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
	{
		$str .= "\t". $proto_name_var ."_t ". $proto_name_var .";\n";
		foreach ( $items as $item_rs )
		{
			$item_name = $item_rs[ 'item_name' ];
			if ( 'char' == $item_rs[ 'type' ] )
			{
				$str .= "\tread_fixchar_from_hash( ". $proto_name_var .", ". $item_name .", ". $proto_name_var .".". $item_name .", ". $item_rs[ 'char_len' ] ." );\n";
			}
			else
			{
				$str .= "\tread_int_from_hash( ". $proto_name_var .", ". $item_name ." );\n";
			}
		}
		$str .= "\tyile_result_push_data( all_result, &". $proto_name_var .", sizeof( ". $proto_name_var ."_t ) );\n";
	}
	else
	{
		foreach ( $items as $item_rs )
		{
			$item_name = $item_rs[ 'item_name' ];
			switch ( $item_rs[ 'type' ] )
			{
				case 'struct':
					$read_struct_after[ $item_name ] = $item_rs[ 'sub_id' ];
				break;
				case 'list':
					$read_list_after[ $item_name ] = $item_rs[ 'sub_id' ];
				break;
				case 'varchar':
					$read_string_after[] = $item_name;
				break;
				case 'byte':
					$read_byte_after[] = $item_name;
				break;
				case 'char':
					$char_name = "c_". $item_name;
					$str .= "\tchar ". $char_name ."[ ". $item_rs[ 'char_len' ] ." ];\n";
					$str .= "\tread_fixchar_from_hash( ". $proto_name_var .", ". $item_name .", ". $char_name .", ". $item_rs[ 'char_len' ] ." );\n";
					$str .= "\tyile_result_push_data( all_result, &". $char_name .", sizeof( ". $char_name ." ) );\n";
				break;
				default:
					$int_type = $GLOBALS[ 'all_type_arr' ][ $item_rs[ 'type' ] ];
					$int_var_name = 'tmp_var_'. $int_type;
					if ( !isset( $tmp_int_type_arr[ $item_rs[ 'type' ] ] ) )
					{
						$tmp_int_type_arr[ $item_rs[ 'type' ] ] = true;
						$str .= "\t". $int_type ." ". $int_var_name .";\n";
					}
					$str .= "\tread_int_from_hash_var( ". $proto_name_var .", ". $int_var_name .", ". $item_name ." );\n";
					$str .= "\tyile_result_push_data( all_result, &". $int_var_name .", sizeof( ". $int_var_name ." ) );\n";
				break;
			}
		}
	}
	//字符串处理
	if ( !empty( $read_string_after ) )
	{
		foreach ( $read_string_after as $str_name )
		{
			$str .= "\tread_string_from_hash( ". $proto_name_var .", ". $str_name ." );\n";
		}
	}
	//字节流处理
	if ( !empty( $read_byte_after ) )
	{
		foreach ( $read_byte_after as $key_name )
		{
			$str .= "\tread_bytes_from_hash( ". $proto_name_var .", ". $key_name ." );\n";
		}
	}
	//其它struct处理
	if ( !empty( $read_struct_after ) )
	{
		$str .= "\tHashTable *new_struct_hash;\n";
		foreach ( $read_struct_after as $struct_name => $struct_id )
		{
			$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
			$str .= "\tread_struct_from_hash( ". $proto_name_var .", ". $struct_name ." );\n";
			$str .= "\tsowrite_". $struct_rs[ 'name' ] ."( all_result, new_struct_hash );\n";
		}
	}
	//list处理
	if ( !empty( $read_list_after ) )
	{
		$str .= "\tHashTable *new_list_hash;\n";
		$str .= "\tzval **z_for_item0;\n";
		$str .= "\tHashPosition for_pointer0;\n";
		$str .= "\tuint16_t list_arr_len0;\n";
		foreach ( $read_list_after as $list_name => $list_id )
		{
			$str .= "\tread_list_from_hash( ". $proto_name_var .", ". $list_name ." );\n";
			$str .= tool_so_protocol_list_loop( $list_id, 'new_list_hash', "\t", 0 );
		}
	}
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\tpacket_info.size = all_result->pos - sizeof( packet_head_t );\n";
		$str .= "\tmemcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );\n";
	}
	$str .= "}\n";
	return $str;
}

/**
 * list循环体
 */
function tool_so_protocol_list_loop( $list_id, $p_list_hash, $tab_str, $rank )
{
	$pointer_var = "for_pointer". $rank;
	$len_var = "list_arr_len". $rank;
	$z_item_var = "z_for_item". $rank;
	$str = '';
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	if ( $rank > 0 )
	{
		$str .= $tab_str ."zval **". $z_item_var .";\n";
		$str .= $tab_str ."HashPosition ". $pointer_var .";\n";
		$str .= $tab_str ."uint16_t ". $len_var .";\n";
	}
	$list_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] );
	$str .= $tab_str .$len_var." = zend_hash_num_elements( ". $p_list_hash ." );\n";
	$str .= $tab_str ."yile_result_push_data( all_result, &". $len_var .", sizeof( ". $len_var ." ) );\n";
	$str .= $tab_str ."for( yile_loop_arr( ". $p_list_hash .", ". $pointer_var .", ". $z_item_var ." ) )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$tmp_struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str ."\tprotocol_list_is_array( ". $z_item_var .", \"list of struct: ". $tmp_struct[ 'name' ] ."\" );\n";
			$str .= $tab_str ."\tsowrite_". $tmp_struct[ 'name' ] ."( all_result, Z_ARRVAL_P( *". $z_item_var ." ) );\n";
		break;
		case 'list':
			$sub_id = $list_rs[ 'sub_id' ];
			$str .= $tab_str ."\tprotocol_list_is_array( ". $z_item_var .", \"list of list: ". $list_id ."\" );\n";
			$tmp_list = $GLOBALS[ 'all_list' ][ $sub_id ];
			$p_hash_var = "p_hash_arr". $rank;
			$str .= $tab_str ."\tHashTable *". $p_hash_var ." = Z_ARRVAL_P( *". $z_item_var ." );\n";
			$str .= tool_so_protocol_list_loop( $sub_id, $p_hash_var, $tab_str ."\t", $rank + 1 );
		break;
		case 'varchar':
			$str .= $tab_str ."\tread_string_from_zval( ". $z_item_var ." );\n";
		break;
		case 'byte':
			$str .= $tab_str ."\tread_bytes_from_zval( ". $z_item_var ." );\n";
		break;
		case 'char':
			$char_name = "tmp_char";
			$str .= $tab_str ."\tchar ". $char_name ."[ ". $list_rs[ 'char_len' ] ." ];\n";
			$str .= $tab_str ."\tread_fix_string_from_zval( ". $z_item_var .", ". $char_name .", sizeof( ". $char_name ." ) );\n";
		break;
		default:
			$int_type = $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ];
			$int_var = "tmp_var". $rank;
			$str .= $tab_str ."\t". $int_type ." ". $int_var .";\n";
			$str .= $tab_str ."\tread_int_from_zval( ". $z_item_var .", ". $int_var ." );\n";
			$str .= $tab_str ."\tyile_result_push_data( all_result, &". $int_var .", sizeof( ". $int_var ." ) );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	return $str;
}

/**
 * 解析 struct
 */
function tool_so_protocol_struct_parse_code( $pid, $rs, $items )
{
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$read_byte_after = array();
	$proto_name_var = "proto_so_". $rs[ 'name' ];
	$head_char = "\n/**\n * 解析 ". $rs[ 'desc' ] ."\n */\nvoid soread_". $rs[ 'name' ] ."( protocol_packet_t *byte_pack, zval *result_arr )";
	$GLOBALS[ 'parse_php_struct_define' ][] = $head_char .";\n";
	$str = $head_char;
	$str .= "\n{\n";
	//定长协议
	if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
	{
		$str .= "\t". $proto_name_var ."_t *tmp_struct;\n";
		$str .= "\tset_data_pointer( byte_pack, sizeof( ". $proto_name_var ."_t ), tmp_struct, ". $proto_name_var ."_t );\n";
		foreach ( $items as $item_rs )
		{
			$item_name = $item_rs[ 'item_name' ];
			switch ( $item_rs[ 'type' ] )
			{
				case 'char':
					$str .= "\tadd_assoc_string( result_arr, \"". $item_name ."\", tmp_struct->". $item_name .", 1 );\n";
				break;
				default:
					$str .= "\tadd_assoc_long( result_arr, \"". $item_name ."\", tmp_struct->". $item_name ." );\n";
				break;
			}
		}
	}
	else
	{
		$undef_var = false;
		foreach ( $items as $item_rs )
		{
			$item_name = $item_rs[ 'item_name' ];
			switch ( $item_rs[ 'type' ] )
			{
				case 'struct':
					$read_struct_after[ $item_name ] = $item_rs[ 'sub_id' ];
				break;
				case 'list':
					$read_list_after[ $item_name ] = $item_rs[ 'sub_id' ];
				break;
				case 'varchar':
					$read_string_after[] = $item_name;
				break;
				case 'byte':
					$read_byte_after[] = $item_name;
				break;
				case 'char':
					$var_name = "c_". $item_name;
					$str .= "\tchar *". $var_name .";\n";
					$str .= "\tadd_assoc_string( result_arr, \"". $item_name ."\", ". $var_name .", 1 );\n";
				break;
				//其它都是定长数据处理
				default:
					if ( !$undef_var )
					{
						$str .= "\t". $proto_name_var ."_t tmp_struct;\n";
						$undef_var = true;
					}
					$str .= "\tphp_result_copy( byte_pack, &tmp_struct.". $item_name .", sizeof( tmp_struct.". $item_name ." ) );\n";
					$str .= "\tadd_assoc_long( result_arr, \"". $item_name ."\", tmp_struct.". $item_name ." );\n";
				break;
			}
		}
	}

	//字符串处理
	if ( !empty( $read_string_after ) )
	{
		foreach ( $read_string_after as $str_name )
		{
			$var_name = "vc_". $str_name;
			$len_name = "len_". $str_name;
			$str .= "\tstring_len_t ". $len_name .";\n";
			$str .= "\tchar *". $var_name .";\n";
			$str .= "\tphp_result_copy( byte_pack, &". $len_name .", sizeof( string_len_t ) );\n";
			$str .= "\tset_data_pointer( byte_pack, ". $len_name .", ". $var_name .", char );\n";
			$str .= "\tadd_assoc_stringl( result_arr, \"". $str_name ."\",". $var_name .", ". $len_name .", 1 );\n";
		}
	}
	//字节流
	if ( !empty( $read_byte_after ) )
	{
		foreach ( $read_byte_after as $key_name )
		{
			$var_name = "vc_". $key_name;
			$len_name = "len_". $key_name;
			$str .= "\tbytes_len_t ". $len_name .";\n";
			$str .= "\tchar *". $var_name .";\n";
			$str .= "\tphp_result_copy( byte_pack, &". $len_name .", sizeof( bytes_len_t ) );\n";
			$str .= "\tset_data_pointer( byte_pack, ". $len_name .", ". $var_name .", char );\n";
			$str .= "\tadd_assoc_stringl( result_arr, \"". $key_name ."\",". $var_name .", ". $len_name .", 1 );\n";
		}
	}
	//其它struct处理
	if ( !empty( $read_struct_after ) )
	{
		foreach ( $read_struct_after as $struct_name => $struct_id )
		{
			$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
			$var_name = "z_". $struct_name;
			$str .= "\tzval *". $var_name .";\n";
			$str .= "\tMAKE_STD_ZVAL( ". $var_name ." );\n";
			$str .= "\tarray_init( ". $var_name ." );\n";
			$str .= "\tadd_assoc_zval( result_arr, \"". $struct_name ."\", ". $var_name ." );\n";
			$str .= "\tsoread_". $struct_rs[ 'name' ] ."( byte_pack, ". $var_name ." );\n";
		}
	}
	//list处理
	if ( !empty( $read_list_after ) )
	{
		$str .= "\tuint16_t arr_len0;\n";
		$str .= "\tint for_i0;\n";
		foreach ( $read_list_after as $list_name => $list_id )
		{
			$var_name = "z_". $list_name;
			$str .= "\tzval *". $var_name .";\n";
			$str .= "\tMAKE_STD_ZVAL( ". $var_name ." );\n";
			$str .= "\tarray_init( ". $var_name ." );\n";
			$str .= "\tadd_assoc_zval( result_arr, \"". $list_name ."\", ". $var_name ." );\n";
			$str .= tool_so_protocol_list_parse_loop( $list_id, $list_name, $var_name, "\t", 0 );
		}
	}
	$str .= "}\n";
	return $str;
}

/**
 * 解析list的循环体
 */
function tool_so_protocol_list_parse_loop( $list_id, $list_name, $parent_arr, $tab_str, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$len_name = "arr_len". $rank;
	$for_name = "for_i". $rank;
	$str = '';
	if ( $rank > 0 )
	{
		$str .= $tab_str ."uint16_t ". $len_name .";\n";
		$str .= $tab_str ."int ". $for_name .";\n";
	}
	$str .= $tab_str ."php_result_copy( byte_pack, &". $len_name .", sizeof( ". $len_name ." ) );\n";
	$str .= $tab_str ."for( ". $for_name ." = 0; ". $for_name ." < ". $len_name ."; ++". $for_name ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$tmp_struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$var_name = "z_". $list_name ."_". $rank;
			$str .= $tab_str ."\tzval *". $var_name .";\n";
			$str .= $tab_str ."\tMAKE_STD_ZVAL( ". $var_name ." );\n";
			$str .= $tab_str ."\tarray_init( ". $var_name ." );\n";
			$str .= $tab_str ."\tadd_next_index_zval( ". $parent_arr .", ". $var_name ." );\n";
			$str .= $tab_str ."\tsoread_". $tmp_struct[ 'name' ] ."( byte_pack, ". $var_name ." );\n";
		break;
		case 'list':
			$var_name = "z_list_". $list_name ."_". $rank;
			$str .= $tab_str ."\tzval *". $var_name .";\n";
			$str .= $tab_str ."\tMAKE_STD_ZVAL( ". $var_name ." );\n";
			$str .= $tab_str ."\tarray_init( ". $var_name ." );\n";
			$str .= $tab_str ."\tadd_next_index_zval( ". $parent_arr .", ". $var_name ." );\n";
			$str .= tool_so_protocol_list_parse_loop( $list_rs[ 'sub_id' ], $list_name, $var_name, $tab_str ."\t", $rank + 1 );
		break;
		case 'char':
			$var_name = "c_". $list_name;
			$str .= $tab_str ."\tchar ". $var_name ."[ ". $list_rs[ 'char_len' ] ." ];\n";
			$str .= $tab_str ."\tphp_result_copy( byte_pack, ". $var_name .", sizeof( ". $var_name ." ) );\n";
			$str .= $tab_str ."\tadd_next_index_string( ". $parent_arr .", ". $var_name .", 1 );\n";
		break;
		case 'byte':
		case 'varchar':
			$var_name = "vc_". $list_name;
			$len_name = "len_". $list_name;
			$len = $len_name;
			if ( 'byte' == $list_rs[ 'type' ] )
			{
				$size_type = 'bytes_len_t';
			}
			else
			{
				$size_type = 'string_len_t';
			}
			$str .= $tab_str ."\t". $size_type ." ". $len_name .";\n";
			$str .= $tab_str ."\tchar *". $var_name .";\n";
			$str .= $tab_str ."\tphp_result_copy( byte_pack, &". $len_name .", sizeof( ". $size_type ." ) );\n";
			$str .= $tab_str ."\tset_data_pointer( byte_pack, ". $len_name .", ". $var_name .", char );\n";
			$str .= $tab_str ."\tadd_next_index_stringl( ". $parent_arr .", ". $var_name .", ". $len .", 1 );\n";
		break;
		default:
			$var_name = "v_". $list_name;
			$str .= $tab_str ."\t". $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ] ." ". $var_name .";\n";
			$str .= $tab_str ."\tphp_result_copy( byte_pack, ". $var_name .", sizeof( ". $var_name ." ) );\n";
			$str .= $tab_str ."\tadd_next_index_long( ". $parent_arr .", ". $var_name ." );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	return $str;
}