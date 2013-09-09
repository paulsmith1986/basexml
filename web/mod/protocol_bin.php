<?php
/**
 * 生成协议里的size define串
 */
function tool_bin_protocol_encode_def( $side_type, $build_path )
{
	$encode_str = "#ifndef PROTOCOL_ENCODE_DATA_H\n#define PROTOCOL_ENCODE_DATA_H\n";
	$decode_str = "#ifndef PROTOCOL_DECODE_DATA_H\n#define PROTOCOL_DECODE_DATA_H\n";
	$re_var_name = "_proto_read_result";
	$decode_str .= "#define is_decode_error() 0 != ". $re_var_name;
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		$var_type = "proto_". $rs[ 'name' ] ."_t";
		$char_len = strtoupper( "proto_size_". $rs[ 'name'] );
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		//服务器端的读 和 客户端的写
		if ( ( 'server' == $side_type && $rs[ 'proto_type' ] & 2 ) || ( 'client' == $side_type && $rs[ 'proto_type' ] & 1 ) )
		{
			$encode_str .= "\n//". $rs[ 'desc' ] ." 将原始数据转换成tcp包数据\n";
			$tmp_char = "#define encode_". $rs[ 'name' ] ."( pack_name";
			if ( empty( $items ) )
			{
				$data_len = "sizeof( packet_head_t )";
			}
			else
			{
				$tmp_char .= ", data";
				//固定长度
				if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
				{
					$data_len = $char_len;
				}
				else
				{
					if ( $rs[ 'size' ] > 0 )
					{
						$data_len = $char_len;
					}
					else
					{
						$tmp_char .=", size";
						$data_len = "size";
					}
				}
			}
			$tmp_char .= " )";
			$proto_char_name = "WRITE_". strtoupper( $rs[ 'name' ] );
			$encode_str .= tool_tab_line( $tmp_char );
			$encode_str .= tool_tab_line( "char ". $proto_char_name ."[ ". $data_len ." ];", 1 );
			$encode_str .= tool_tab_line( "protocol_result_t pack_name;", 1 );
			$encode_str .= tool_tab_line( "pack_name.pos = 0;", 1 );
			$encode_str .= tool_tab_line( "pack_name.error_code = 0;", 1 );
			$encode_str .= tool_tab_line( "pack_name.is_resize = 0;", 1 );
			$encode_str .= tool_tab_line( "pack_name.str = ". $proto_char_name .";", 1 );
			$encode_str .= tool_tab_line( "pack_name.max_pos = ". $data_len .";", 1 );
			$encode_str .= "\twrite_". $rs[ 'name' ] ."( &pack_name";
			if ( !empty( $items ) )
			{
				$encode_str .= ", data";
			}
			$encode_str .= " )\n";
		}
		else
		{
			$read_char = "READ_". strtoupper( $rs[ 'name' ] );
			$char_name = "TCP_". strtoupper( $rs[ 'name' ] );
			$decode_str .= "\n//". $rs[ 'desc' ] ." 读取网络层数据转换成原始数据\n";
			$tmp_char = "#define read_and_decode_". $rs[ 'name' ] ."( fd";
			if ( empty( $items ) )
			{
				$data_len = "sizeof( packet_head_t )";
			}
			else
			{
				$tmp_char .= ", var_name";
				//固定长度
				if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
				{
					$data_len = $char_len;
				}
				else
				{
					if ( $rs[ 'size' ] > 0 )
					{
						$data_len = $rs[ 'size' ];
					}
					else
					{
						$tmp_char .= ", size";
						$data_len = 'size';
					}
				}

			}
			$tmp_char .= " )";
			$decode_str .= tool_tab_line( $tmp_char );
			$decode_str .= tool_tab_line( "char ". $char_name ."[ ". $data_len ." ];", 1 );
			$decode_str .= tool_tab_line( "protocol_packet_t _tmp_buff_pack;", 1 );
			$decode_str .= tool_tab_line( "_tmp_buff_pack.pos = 0;", 1 );
			$decode_str .= tool_tab_line( "_tmp_buff_pack.max_pos = sizeof( packet_head_t );", 1 );
			$decode_str .= tool_tab_line( "_tmp_buff_pack.pool_size = ". $data_len .";", 1 );
			$decode_str .= tool_tab_line( "_tmp_buff_pack.is_resize = 0;", 1 );
			$decode_str .= tool_tab_line( "_tmp_buff_pack.data = ". $char_name .";", 1 );
			$decode_str .= tool_tab_line( "protocol_recv_pack( fd, &_tmp_buff_pack );", 1 );
			$decode_str .= tool_tab_line( "int ". $re_var_name ." = 0;", 1 );
			$decode_str .= tool_tab_line( $var_type ." *var_name = NULL;", 1 );
			$decode_str .= tool_tab_line( "if ( 0 == _tmp_buff_pack.max_pos )", 1 );
			$decode_str .= tool_tab_line( "{", 1 );
			$decode_str .= tool_tab_line( $re_var_name ." = PROTO_READ_NET_DATA_ERROR;", 2 );
			$decode_str .= tool_tab_line( "}", 1 );
			$decode_str .= tool_tab_line( "else", 1 );
			$decode_str .= tool_tab_line( "{", 1 );
			$decode_str .= tool_tab_line( "packet_head_t *pack_head = ( packet_head_t* )&". $char_name ."[ 0 ];", 2 );
			$decode_str .= tool_tab_line( "if ( ". $rs[ 'struct_id' ] ." != pack_head->pack_id )", 2 );
			$decode_str .= tool_tab_line( "{", 2 );
			$decode_str .= tool_tab_line( $re_var_name ." = PROTO_READ_PACK_ID_ERROR;", 3 );
			$decode_str .= tool_tab_line( "}", 2 );
			if ( !empty( $items ) )
			{
				$decode_str .= tool_tab_line( "else", 2 );
				$decode_str .= tool_tab_line( "{", 2 );
				$decode_str .= tool_tab_line( "_tmp_buff_pack.pos = sizeof( packet_head_t );", 3 );
				$decode_str .= tool_tab_line( "char ". $read_char ."[ ". $data_len ." ];", 3 );
				$decode_str .= tool_tab_line( "protocol_result_t _tmp_result_pack;", 3 );
				$decode_str .= tool_tab_line( "_tmp_result_pack.pos = 0;", 3 );
				$decode_str .= tool_tab_line( "_tmp_result_pack.str = ". $read_char .";", 3 );
				$decode_str .= tool_tab_line( "_tmp_result_pack.error_code = 0;", 3 );
				$decode_str .= tool_tab_line( "_tmp_result_pack.max_pos = ". $data_len .";", 3 );
				$decode_str .= tool_tab_line( "var_name = read_". $rs[ 'name' ] ."( &_tmp_buff_pack, &_tmp_result_pack );", 3 );
				$decode_str .= tool_tab_line( "if( _tmp_result_pack.error_code > 0 )", 3 );
				$decode_str .= tool_tab_line( "{", 3 );
				$decode_str .= tool_tab_line( $re_var_name ." = _tmp_result_pack.error_code;", 4 );
				$decode_str .= tool_tab_line( "}", 3 );
				$decode_str .= tool_tab_line( "}", 2 );
			}
			$decode_str .= tool_tab_line( "}", 1 );
			$decode_str .= tool_tab_line( "if( _tmp_buff_pack.is_resize )", 1 );
			$decode_str .= tool_tab_line( "{", 1 );
			$decode_str .= tool_tab_line( "free( _tmp_buff_pack.data );", 2 );
			$decode_str .= tool_line( "}", 1 );
		}
	}
	$encode_str .= "\n#endif\n";
	$decode_str .= "\n#endif\n";
	$encode_file = $build_path .'/encode_'. $side_type .'.h';
	$decode_file = $build_path .'/decode_'. $side_type .'.h';
	file_put_contents( $encode_file, $encode_str );
	file_put_contents( $decode_file, $decode_str );
	echo "生成将原始数据转换成网络传输数据的头文件:". $encode_file, "\n";
	echo "生成将网络传输数据转换成原始数据的头文件:". $decode_file, "\n";
}

/**
 * 生成长度定义
 */
function tool_bin_protocol_size_def( $build_path, $xml_path = null )
{
	if ( !empty( $xml_path ) )
	{
		tool_protocol_xml( $xml_path, 'all' );
		tool_bin_protocol_common( );
	}
	$def_var = "PROTOCOL_POOL_SIZE_HEAD";
	$str = "#ifndef ". $def_var ."\n#define ". $def_var ."\n";
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$data_len = false;
		if ( empty( $items ) )
		{
			$data_len = 'sizeof( packet_head_t )';
		}
		else if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
		{
			$data_len = "sizeof( proto_". $rs[ 'name' ] ."_t ) + sizeof( packet_head_t )";
		}
		else if( $rs[ 'size' ] > 0 )
		{
			$data_len = $rs[ 'size' ];
		}
		if ( false !== $data_len )
		{
			$len_name = strtoupper( "proto_size_". $rs[ 'name'] );
			$str .= "//". $rs[ 'desc' ] ." 解析时占内存\n";
			$str .= "#define ". $len_name ." ". $data_len ."\n";
		}
	}
	$str .= "\n#endif\n";
	$file = $h_file = $build_path. '/proto_size.h';
	file_put_contents( $file, $str );
	echo "生成size定义头文件:". $file, "\n";
}

/**
 * 任务派发文件
 */
function tool_bin_protocol_task_dispatch( $side_type, $build_path )
{
	if ( 'server' == $side_type )
	{
		$main_c = 'proto_bin';
		$proto_type = 2;
		$use_fd_info = true;
	}
	else
	{
		$main_c = 'proto_c';
		$proto_type = 1;
		$use_fd_info = false;
	}
	$def_str = "#ifndef FIRST_PROTOCOL_REQESUT_TASK_H\n#define FIRST_PROTOCOL_REQESUT_TASK_H\n";
	$def_str .= "#include \"first_protocol.h\"\n";
	$def_str .= "#include \"". $main_c .".h\"\n";
	$def_str .= "#include \"encode_". $side_type .".h\"\n";
	$def_str .= "#include \"decode_". $side_type .".h\"\n";
	$def_str .= "#include \"proto_size.h\"\n";
	$def_str .= "//尝试释放读取网络数据时申请的内存\n";
	$def_str .= "#define try_free_proto_pack( tmp_pack ) if( tmp_pack.is_resize ) free( tmp_pack.data )\n";
	$str = "//主任务线程\n";
	$str .= "int do_request_task ( protocol_packet_t *tmp_pack";
	if ( $use_fd_info )
	{
		$str .= ", fd_struct_t *fd_info";
	}
	$str .= " )";
	$def_str .= $str .";\n";
	$str .= "\n{\n";
	$str .= "\tprotocol_result_t read_result_pool;\n";
	$str .= "\tmemset( &read_result_pool, 0, sizeof( protocol_result_t ) );\n";
	$str .= "\tpacket_head_t *pack_head = ( packet_head_t* )tmp_pack->data;\n";
	$str .= "\tswitch( pack_head->pack_id )\n";
	$str .= "\t{\n";
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		if ( $rs[ 'proto_type' ] & $proto_type )
		{
			continue;
		}
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$func_name = "request_". $rs[ 'name' ];
		$proto_name_var = "proto_". $rs[ 'name' ] ."_t";
		$def_str .= "\n/**\n";
		$def_str .= " * pack_id: ". $rs[ 'struct_id' ] ." ". $rs[ 'desc' ] ."\n";
		$def_str .= " */\n";
		$arg = array();
		$def_str .= "void ". $func_name ."( ";
		if ( $use_fd_info )
		{
			$arg[] = "fd_struct_t *fd_info";
		}
		if ( !empty( $items ) )
		{
			$arg[] = $proto_name_var ." *req_pack";
		}
		$def_str .= join( ", ", $arg ) ." );\n";
		$str .= "\t\tcase ". $rs[ 'struct_id' ] .": //". $rs[ 'desc' ] ."\n";
		if ( empty( $items ) )
		{
			$str .= "\t\t\t". $func_name ."(". ( $use_fd_info ? " fd_info " : " " ) .");\n";
		}
		else
		{
			$str .= "\t\t{\n";
			$tab_str = "\t\t\t";
			//定长协议无需分配内存
			if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
			{
				$str .= $tab_str. "read_result_pool.str = NULL;\n";
			}
			//运行时刻确定大小
			elseif ( 'runtime' === $rs[ 'size' ] )
			{
				$char_len = strtoupper( "proto_size_". $rs[ 'name'] );
				$str .= $tab_str ."char char_". $pid ."[ PROTOCOL_DATA_LEN ];\n";
				$str .= $tab_str ."size_read_". $rs[ 'name' ] ."( tmp_pack, &read_result_pool );\n";
				$str .= $tab_str . "if( read_result_pool.error_code > 0 )\n";
				$str .= $tab_str ."{\n";
				$str .= $tab_str ."\treturn read_result_pool.error_code;\n";
				$str .= $tab_str ."}\n";
				$str .= $tab_str ."if( read_result_pool.max_pos > PROTOCOL_DATA_LEN )\n";
				$str .= $tab_str ."{\n";
				$str .= $tab_str ."\tread_result_pool.is_resize = 1;\n";
				$str .= $tab_str ."\tread_result_pool.str = (char*)malloc( read_result_pool.max_pos );\n";
				$str .= $tab_str ."}\n";
				$str .= $tab_str ."else\n";
				$str .= $tab_str ."{\n";
				$str .= $tab_str ."\tread_result_pool.str = char_". $pid .";\n";
				$str .= $tab_str ."\tread_result_pool.max_pos = sizeof( char_". $pid ." );\n";
				$str .= $tab_str ."}\n";
			}
			else
			{
				$char_len = strtoupper( "proto_size_". $rs[ 'name'] );
				$str .= $tab_str ."char char_". $pid ."[ ". $char_len ." ];\n";
				$str .= $tab_str ."read_result_pool.str = char_". $pid .";\n";
				$str .= $tab_str ."read_result_pool.max_pos = sizeof( char_". $pid ." );\n";
			}
			$str .= $tab_str . $proto_name_var ." *req_data = read_". $rs[ 'name' ] ."( tmp_pack, &read_result_pool );\n";
			$str .= $tab_str . "if( read_result_pool.error_code > 0 )\n";
			$str .= $tab_str ."{\n";
			if ( 'runtime' === $rs[ 'size' ] )
			{
				$str .= $tab_str ."\ttry_free_result_pack( read_result_pool );\n";
			}
			$str .= $tab_str ."\treturn read_result_pool.error_code;\n";
			$str .= $tab_str ."}\n";
			$str .= $tab_str . $func_name ."( ". ( $use_fd_info ? "fd_info, " : '' ) ."req_data );\n";
			if ( 'runtime' === $rs[ 'size' ] )
			{
				$str .= $tab_str ."try_free_result_pack( read_result_pool );\n";
			}
			$str .= "\t\t}\n";
		}
		$str .= "\t\tbreak;\n";
	}
	$def_str .= "#endif\n";
	$str .= "\t}\n";
	$str .= "\treturn 0;\n";
	$str .= "}\n";
	$h_file = $build_path .'/task_'. $side_type .'.h';
	$c_file = $build_path .'/task_'. $side_type .'.c';
	file_put_contents( $c_file, "#include \"task_". $side_type .".h\"\n" . $str );
	echo "生成任务派发文件:", $c_file, "\n";
	echo "生成任务派发文件头文件:", $h_file, "\n";
	file_put_contents( $h_file, $def_str );
}

/**
 * 生成h文件
 */
function tool_bin_protocol_head( $type )
{
	$def_var = "FIRST_PROTOCOL_". strtoupper( $type ) ."_H";
	$protocol_h = "#ifndef ". $def_var ."\n#define ". $def_var ."\n";
	$protocol_h .= "#include <stdint.h>\n";
	$protocol_h .= "#include <stdlib.h>\n";
	$protocol_h .= "#include \"first_protocol.h\"\n";
	$protocol_h .= "#pragma pack(1)\n";
	$protocol_h .= join( '', $GLOBALS[ 'typedef_all' ] );
	$protocol_h .= join( '', $GLOBALS[ 'typedef_detail' ] );
	$protocol_h .= "#pragma pack()\n";
	//打包head文件
	$protocol_h .= join( '', $GLOBALS[ 'struct_define' ] );
	//解析数据的head文件
	$protocol_h .= join( '', $GLOBALS[ 'parse_struct_define' ] );
	//打印结构体的head文件
	$protocol_h .= join( '', $GLOBALS[ 'size_func_define' ] );
	//打印结构体的head文件
	$protocol_h .= "#ifdef PROTOCOL_DEBUG\n";
	$protocol_h .= join( '', $GLOBALS[ 'print_func_define' ] );
	$protocol_h .= "#endif\n";
	$protocol_h .= "#endif";
	return $protocol_h;
}

/**
 * 生成二进制协议
 */
function tool_bin_protocol_server( $build_path, $server_xml, $extend_protocol = array()  )
{
	tool_protocol_xml( $server_xml, 'all', $extend_protocol );
	tool_bin_protocol_common( );
	$GLOBALS[ 'parse_struct_define' ] = array();
	$GLOBALS[ 'struct_code' ] = array();
	$GLOBALS[ 'parse_struct_code' ] = array();
	$GLOBALS[ 'print_struct_code' ] = array();
	$GLOBALS[ 'print_func_define' ] = array();
	$GLOBALS[ 'size_func_define' ] = array();
	$GLOBALS[ 'size_func_code' ] = array();
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		//请求协议
		if ( $rs[ 'proto_type' ] & 2 )
		{
			$code = tool_bin_protocol_struct_code( $pid, $rs, $items );
			$GLOBALS[ 'struct_code' ][] = $code;
		}
		if ( empty( $items ) )
		{
			continue;
		}
		//解析协议
		if ( $rs[ 'proto_type' ] & 1 )
		{
			$parse_code = tool_bin_protocol_struct_parse_code( $pid, $rs, $items );
			$print_code = tool_bin_protocol_struct_print_code( $pid, $rs, $items );
			if ( 'runtime' === $rs[ 'size' ] )
			{
				$size_code = tool_bin_protocol_struct_size_code( $pid, $rs, $items );
				$GLOBALS[ 'size_func_code' ][] = $size_code;
			}
			$GLOBALS[ 'parse_struct_code' ][] = $parse_code;
			$GLOBALS[ 'print_struct_code' ][] = $print_code;
		}
	}
	$head_str = tool_bin_protocol_head( 'server' );
	$h_file = $build_path .'/proto_bin.h';
	$c_file = $build_path .'/proto_bin.c';
	$c_code = "#include \"proto_bin.h\"\n". join( '', $GLOBALS[ 'struct_code' ] );
	$c_code .= join( '', $GLOBALS[ 'parse_struct_code' ] );
	$c_code .= join( '', $GLOBALS[ 'size_func_code' ] );
	$c_code .= "#ifdef PROTOCOL_DEBUG\n";
	$c_code .= join( '', $GLOBALS[ 'print_struct_code' ] );
	$c_code .= "#endif\n";
	file_put_contents( $h_file, $head_str );
	file_put_contents( $c_file, $c_code );
	echo "\n生成二进制协议文件(Server)", $h_file, "\n";
	echo "\n生成二进制协议文件(Server)", $c_file, "\n";
	tool_bin_protocol_task_dispatch( 'server', $build_path );
	tool_bin_protocol_encode_def( 'server', $build_path );
}

/**
 * 生成二进制协议 客户端
 */
function tool_bin_protocol_client( $build_path, $xml_path )
{
	tool_protocol_xml( $xml_path, 'all' );
	tool_bin_protocol_common( );
	$GLOBALS[ 'parse_struct_define' ] = array();
	$GLOBALS[ 'struct_code' ] = array();
	$GLOBALS[ 'parse_struct_code' ] = array();
	$GLOBALS[ 'print_struct_code' ] = array();
	$GLOBALS[ 'print_func_define' ] = array();
	$GLOBALS[ 'size_func_define' ] = array();
	$GLOBALS[ 'size_func_code' ] = array();
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		//请求协议
		if ( $rs[ 'proto_type' ] & 1 )
		{
			$code = tool_bin_protocol_struct_code( $pid, $rs, $items );
			$GLOBALS[ 'struct_code' ][] = $code;
		}
		if ( empty( $items ) )
		{
			continue;
		}
		//解析协议
		if ( $rs[ 'proto_type' ] & 2 )
		{
			$parse_code = tool_bin_protocol_struct_parse_code( $pid, $rs, $items );
			$print_code = tool_bin_protocol_struct_print_code( $pid, $rs, $items );
			if ( 'runtime' === $rs[ 'size' ] )
			{
				$size_code = tool_bin_protocol_struct_size_code( $pid, $rs, $items );
				$GLOBALS[ 'size_func_code' ][] = $size_code;
			}
			$GLOBALS[ 'parse_struct_code' ][] = $parse_code;
			$GLOBALS[ 'print_struct_code' ][] = $print_code;
		}
	}
	$head_str = tool_bin_protocol_head( 'client' );
	$h_file = $build_path .'/proto_c.h';
	$c_file = $build_path .'/proto_c.c';
	$c_code = "#include \"proto_c.h\"\n". join( '', $GLOBALS[ 'struct_code' ] );
	$c_code .= join( '', $GLOBALS[ 'parse_struct_code' ] );
	$c_code .= join( '', $GLOBALS[ 'size_func_code' ] );
	$c_code .= "#ifdef PROTOCOL_DEBUG\n";
	$c_code .= join( '', $GLOBALS[ 'print_struct_code' ] );
	$c_code .= "#endif\n";
	file_put_contents( $h_file, $head_str );
	file_put_contents( $c_file, $c_code );
	echo "\n生成二进制协议文件(Client)", $h_file, "\n";
	echo "\n生成二进制协议文件(Client)", $c_file, "\n";
	tool_bin_protocol_task_dispatch( 'client', $build_path );
	tool_bin_protocol_encode_def( 'client', $build_path );
}

/**
 * 通过string生成c代码
 */
function tool_bin_protocol_struct_code( $pid, $rs, $items )
{
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$tmp_int_type_arr = array();
	$read_byte_after = array();
	$proto_name_var = "proto_". $rs[ 'name' ];
	$head_char = "\n/**\n * 生成 ". $rs[ 'desc' ] ."\n */\nvoid write_". $rs[ 'name' ] ."( protocol_result_t *all_result";
	if ( !empty( $items ) )
	{
		$head_char .= ", ". $proto_name_var ."_t *data_arr";
	}
	$head_char .= " )";

	$GLOBALS[ 'struct_define' ][] = $head_char .";\n";
	$str = $head_char ."\n{\n";
	//是空协议
	if ( empty( $items ) )
	{
		if ( $rs[ 'is_sub' ] )
		{
			show_error( 'struct '. $rs[ 'name' ] .' 为空' );
		}
		$str .= "\tall_result->pos = 0;\n";
		$str .= "\tpacket_head_t packet_info;\n";
		$str .= "\tpacket_info.size = 0;\n";
		$str .= "\tpacket_info.pack_id = ". $rs[ 'struct_id' ] .";\n";
		$str .= "\tfirst_result_push_data( all_result, &packet_info, sizeof( packet_head_t ) );\n";
	}
	else
	{
		//如果是主协议
		if ( !$rs[ 'is_sub' ] )
		{
			//$GLOBALS[ 'struct_define' ][] = "#define write_". $rs[ 'name' ] ."( a, b ) ". $rs[ 'name' ] ."( a, b, NULL )";
			$str .= "\tall_result->pos = 0;\n";
			$str .= "\tpacket_head_t packet_info;\n";
			$str .= "\tpacket_info.pack_id = ". $rs[ 'struct_id' ] .";\n";
			$str .= "\tfirst_result_push_data( all_result, NULL, sizeof( packet_head_t ) );\n";
		}
		if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
		{
			$str .= "\tfirst_result_push_data( all_result, data_arr, sizeof( ". $proto_name_var ."_t ) );\n";
		}
		else
		{
			foreach ( $items as $item_rs )
			{
				switch ( $item_rs[ 'type' ] )
				{
					case 'struct':
						$read_struct_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
					break;
					case 'list':
						$read_list_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
					break;
					case 'varchar':
						$read_string_after[] = $item_rs[ 'item_name' ];
					break;
					case 'byte':
						$read_byte_after[] = $item_rs[ 'item_name' ];
					break;
					default:
						$str .= "\tfirst_result_push_data( all_result, &data_arr->". $item_rs[ 'item_name' ] .", sizeof( data_arr->". $item_rs[ 'item_name' ] ." ) );\n";
					break;
				}
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			foreach ( $read_string_after as $str_name )
			{
				$str .= "\twrite_UTF( all_result, data_arr->". $str_name ." );\n";
			}
		}
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $key_name )
			{
				$str .= "\twrite_bytes( all_result, data_arr->". $key_name ." );\n";
			}
		}

		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
				$str .= "\twrite_". $struct_rs[ 'name' ] ."( all_result, data_arr->". $struct_name ." );\n";
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			$str .= "\tint for_i0;\n";
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_bin_protocol_list_loop( $list_id, 'data_arr->'. $list_name .'->', "\t", 0 );
			}
		}
		if ( !$rs[ 'is_sub' ] )
		{
			$str .= "\tpacket_info.size = all_result->pos - sizeof( packet_head_t );\n";
			$str .= "\tmemcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );\n";
		}
	}
	$str .= "}\n";
	return $str;
}

/**
 * list循环体
 */
function tool_bin_protocol_list_loop( $list_id, $p_list_var, $tab_str, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$str = '';
	$list_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] );
	$for_var = "for_i". $rank;
	if ( $rank > 0 )
	{
		$str .= $tab_str ."int ". $for_var .";";
	}
	$str .= $tab_str ."first_result_push_data( all_result, &". $p_list_var ."len, sizeof( ". $p_list_var ."len ) );\n";
	$str .= $tab_str ."for( ". $for_var ." = 0; ". $for_var ." < ". $p_list_var ."len; ++". $for_var ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$tmp_struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str ."\twrite_". $tmp_struct[ 'name' ] ."( all_result, &". $p_list_var ."item[ ". $for_var ." ] );\n";
		break;
		case 'list':
			$sub_id = $list_rs[ 'sub_id' ];
			$tmp_list = $GLOBALS[ 'all_list' ][ $sub_id ];
			$str .= tool_bin_protocol_list_loop( $sub_id, $p_list_var ."item[ ". $for_var ." ].", $tab_str ."\t", $rank + 1 );
		break;
		case 'varchar':
			$str .= $tab_str ."\twrite_UTF( all_result, ". $p_list_var ."item[ ". $for_var ." ] );\n";
		break;
		case 'byte':
			$str .= $tab_str ."write_bytes( all_result, ". $p_list_var ."item[ ". $for_var ." ] );\n";
		break;
		case 'char':
			$char_name = "tmp_char";
			$str .= $tab_str ."\tchar ". $char_name ."[ ". $list_rs[ 'char_len' ] ." ];\n";
			$str .= $tab_str ."\twrite_fix_char( ". $p_list_var ."item[ ". $for_var ." ], ". $char_name .", ". $list_rs[ 'char_len' ] ." );\n";
			$str .= $tab_str ."\tfirst_result_push_data( all_result, ". $char_name .", sizeof( ". $char_name ." ) );\n";
		break;
		default:
			$int_type = $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ];
			$str .= $tab_str ."\tfirst_result_push_data( all_result, &". $p_list_var ."item[ ". $for_var ." ], sizeof( ". $p_list_var ."item[ ". $for_var ." ] ) );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	return $str;
}

/**
 * 解析struct的c代码
 */
function tool_bin_protocol_struct_parse_code( $pid, $rs, $items )
{
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$read_byte_after = array();
	$proto_name_var = "proto_". $rs[ 'name' ];
	$head_char = "\n/**\n * 解析 ". $rs[ 'desc' ] ."\n */\nproto_". $rs[ 'name' ] ."_t *read_". $rs[ 'name' ] ."( protocol_packet_t *byte_pack, protocol_result_t *result_pool";
	if ( $rs[ 'is_sub' ] )
	{
		$head_char .= ", proto_". $rs[ 'name' ] ."_t *re_struct";
	}
	$head_char .= ")";
	$GLOBALS[ 'parse_struct_define' ][] = $head_char .";\n";
	$str = $head_char ."\n{\n";
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\tproto_". $rs[ 'name' ] ."_t *re_struct = NULL;\n";
	}
	//如果是主协议并且是固定长度的包
	if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] && !$rs[ 'is_sub' ] )
	{
		$str .= "\tif( byte_pack->max_pos - byte_pack->pos != sizeof( ". $proto_name_var ."_t ) )\n";
		$str .= "\t{\n";
		$str .= "\t\tresult_pool->error_code = PROTO_ERROR_SIZEERROR;\n";
		$str .= "\t}\n";
		$str .= "\telse\n";
		$str .= "\t{\n";
		$str .= "\t\tre_struct = (". $proto_name_var ."_t*)&byte_pack->data[ byte_pack->pos ];\n";
		$str .= "\t}\n";
	}
	else
	{
		$str .= "\tif( NULL == re_struct )\n";
		$str .= "\t{\n";
		$str .= "\t\tif( result_pool->pos + sizeof( ". $proto_name_var ."_t ) > result_pool->max_pos )\n";
		$str .= "\t\t{\n";
		$str .= "\t\t\tresult_pool->error_code = PROTO_ERROR_OVERFLOW;\n";
		$str .= "\t\t\treturn NULL;\n";
		$str .= "\t\t}\n";
		$str .= "\t\tre_struct = (". $proto_name_var ."_t*)&result_pool->str[ result_pool->pos ];\n";
		$str .= "\t\tresult_pool->pos += sizeof( ". $proto_name_var ."_t );\n";
		$str .= "\t}\n";
		if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
		{
			$str .= "\tresult_copy( byte_pack, re_struct, sizeof( ". $proto_name_var ."_t ), result_pool );\n";
		}
		else
		{
			foreach ( $items as $item_rs )
			{
				switch ( $item_rs[ 'type' ] )
				{
					case 'struct':
						$read_struct_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
					break;
					case 'list':
						$read_list_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
					break;
					case 'varchar':
						$read_string_after[] = $item_rs[ 'item_name' ];
					break;
					case 'char':
						$str .= "\tresult_copy( byte_pack, re_struct->". $item_rs[ 'item_name' ] .", ". $item_rs[ 'char_len' ] .", result_pool );\n";
					break;
					case 'byte':
						$read_byte_after[] = $item_rs[ 'item_name' ];
					break;
					default:
						$int_type = $GLOBALS[ 'all_type_arr' ][ $item_rs[ 'type' ] ];
						$str .= "\tresult_copy( byte_pack, &re_struct->". $item_rs[ 'item_name' ] .", sizeof( ". $int_type ." ), result_pool );\n";
					break;
				}
			}
		}
	}

	//字符串处理
	if ( !empty( $read_string_after ) )
	{
		foreach ( $read_string_after as $str_name )
		{
			$str .= "\tre_struct->". $str_name ." = read_UTF( byte_pack, result_pool );\n";
		}
	}
	//字节流
	if ( !empty( $read_byte_after ) )
	{
		foreach ( $read_byte_after as $key_name )
		{
			$str .= "\tre_struct->". $key_name ." = read_bytes( byte_pack, NULL, result_pool );\n";
		}
	}
	//其它struct处理
	if ( !empty( $read_struct_after ) )
	{
		foreach ( $read_struct_after as $struct_name => $struct_id )
		{
			$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
			$str .= "\tre_struct->". $struct_name ." = read_". $struct_rs[ 'name' ] ."( byte_pack, result_pool, NULL );\n";
		}
	}
	//list处理
	if ( !empty( $read_list_after ) )
	{
		//$str .= "\tHashTable *new_list_hash;\n";
		//$str .= "\tzval **z_for_item0;\n";
		//$str .= "\tHashPosition for_pointer0;\n";
		$str .= "\tint i_0;\n";
		foreach ( $read_list_after as $list_name => $list_id )
		{
			$str .= tool_bin_protocol_list_parse_loop( $list_id, $list_name, "re_struct->". $list_name, "\t", 0 );
		}
	}
	$str .= "\treturn re_struct;\n";
	$str .= "}\n";
	return $str;
}

/**
 * 解析list的循环体
 */
function tool_bin_protocol_list_parse_loop( $list_id, $list_name, $parent_point, $tab_str, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$list_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] );
	$loop_pointer = "list_var_". $list_name ."_". $rank;
	$for_i = "i_". $rank;
	$str = $tab_str . $list_type ." *". $loop_pointer .";\n";
	if ( 0 == $rank )
	{
		$str .= $tab_str . $loop_pointer ." = (". $list_type ."*)&result_pool->str[ result_pool->pos ];\n";
		$str .= $tab_str ."add_data_pool_size( byte_pack, result_pool, sizeof( ". $list_type ." ) );\n";
		$str .= $tab_str . $parent_point ." = ". $loop_pointer .";\n";
	}
	else
	{
		$str .= $tab_str ."int ". $for_i .";\n";
		$str .= $tab_str . $loop_pointer ." = &". $parent_point .";\n";
	}
	$str .= $tab_str ."result_copy( byte_pack, &". $loop_pointer ."->len, sizeof( uint16_t ), result_pool );\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$item_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'struct_'. $list_rs[ 'sub_id' ] ] );
		break;
		case 'list':
			$item_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_rs[ 'sub_id' ] ] );
		break;
		case 'byte':
			$item_type = 'proto_bin_t';
		break;
		case 'char':
			$item_type = 'char*';
		break;
		default:
			$item_type = $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ];
		break;
	}
	$str .= $tab_str . $loop_pointer ."->item = (". $item_type ."*)&result_pool->str[ result_pool->pos ];\n";
	$str .= $tab_str ."add_data_pool_size( byte_pack, result_pool, sizeof( ". $item_type ." ) * ". $loop_pointer ."->len );\n";
	$str .= $tab_str ."if( result_pool->error_code )\n";
	$str .= $tab_str ."{\n";
	$str .= $tab_str ."\treturn NULL;\n";
	$str .= $tab_str ."}\n";
	$str .= $tab_str ."for( ". $for_i ." = 0; ". $for_i ." < ". $loop_pointer ."->len; ++". $for_i ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$tmp_struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str ."\tread_". $tmp_struct[ 'name' ] ."( byte_pack, result_pool, &". $loop_pointer ."->item[ ". $for_i ." ] );\n";
		break;
		case 'byte':
			$str .= $tab_str ."\tread_bytes( byte_pack, &". $loop_pointer ."->item[ ". $for_i ." ], result_pool );\n";
		break;
		case 'list':
			$str .= tool_bin_protocol_list_parse_loop( $list_rs[ 'sub_id' ], $list_name, $loop_pointer ."->item[ ". $for_i ." ]", $tab_str ."\t", $rank + 1 );
		break;
		case 'char':
			$str .= $tab_str ."\t". $loop_pointer ."->item[ ". $for_i ." ] = read_fix_char( byte_pack, result_pool, ". $list_rs[ 'char_len' ] ." );\n";
		break;
		case 'varchar':
			$str .= $tab_str ."\t". $loop_pointer ."->item[ ". $for_i ." ] = read_UTF( byte_pack, result_pool );\n";
		break;
		default:
			$str .= $tab_str ."\tresult_copy( byte_pack, &". $loop_pointer ."->item[ ". $for_i ." ], sizeof( ". $item_type ." ), result_pool );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	return $str;
}

/**
 * 打印结构体
 */
function tool_bin_protocol_struct_print_code( $pid, $rs, $items )
{
	$print_head_char = "\n/**\n * 打印 ". $rs[ 'desc' ] ."\n */\nvoid print_". $rs[ 'name' ] ."( proto_". $rs[ 'name' ] ."_t *re";
	if ( $rs[ 'is_sub' ] )
	{
		$print_head_char .= ", int rank";
	}
	$print_head_char .= ")";
	$GLOBALS[ 'print_func_define' ][] = $print_head_char .";\n";
	$str = $print_head_char ."\n{\n";
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\tint rank = 0;\n";
	}
	$str .= "\tchar prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];\n";
	$str .= "\tfirst_printf_tab_string( prefix_char, rank );\n";
	$str .= "\tprintf( \"". $rs[ 'name' ] ."\\n\" );\n";
	$str .= "\tprintf( \"%s(\\n\", prefix_char );\n";
	$list_num = 0;
	foreach ( $items as $item_rs )
	{
		$str .= "\tprintf( \"    %s[". $item_rs[ 'item_name' ] ."] = > \", prefix_char );\n";
		switch ( $item_rs[ 'type' ] )
		{
			case 'struct':
				$tmp_struct = $GLOBALS[ 'all_protocol' ][ $item_rs[ 'sub_id' ] ];
				$str .= "\tprint_". $tmp_struct[ 'name' ] ."( re->". $item_rs[ 'item_name' ] .", rank + 1 );\n";
			break;
			case 'list':
				if ( 0 == $list_num++ )
				{
					$str .= "\tint print_i1;\n";
				}
				$str .= "\tprintf( \"List\\n\" );\n";
				$str .= tool_protocol_print_list( $item_rs[ 'sub_id' ], "re->". $item_rs[ 'item_name' ]."->", 1 );
			break;
			case 'byte':
				$str .= "\tprintf( \"[Blob %d]\\n\", re->". $item_rs[ 'item_name' ] ."->len );\n";
			break;
			case 'varchar':
			case 'char':
				$str .= "\tprintf( \"%s\\n\", re->". $item_rs[ 'item_name' ] ." );\n";
			break;
			default:
			{
				switch ( $item_rs[ 'type' ] )
				{
					case 'bigint';
						$int_type = 'lld';
					break;
					case 'unsigned int':
						$int_type = 'u';
					break;
					default:
						$int_type = 'd';
					break;
				}
				$str .= "\tprintf( \"%". $int_type ."\\n\", re->". $item_rs[ 'item_name' ] ." );\n";
				break;
			}
		}
	}
	$str .= "\tprintf( \"%s)\\n\", prefix_char );\n";
	$str .= "}\n";
	return $str;
}

/**
 * 打印List
 */
function tool_protocol_print_list( $list_id, $p_var, $rank )
{
	$tab_str = str_repeat( "\t", $rank );
	$tab_str_c = str_repeat( "    ", $rank );
	$str = '';
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$var_i = "print_i". $rank;
	if ( $rank > 1 )
	{
		$str .= $tab_str ."int ". $var_i .";";
	}
	$str .= $tab_str ."printf( \"". $tab_str_c ."%s{\\n\", prefix_char );\n";
	$str .= $tab_str ."for( ". $var_i ." = 0; ". $var_i ." < ". $p_var ."len; ++". $var_i ." )\n";
	$str .= $tab_str ."{\n";
	$str .= $tab_str ."\tprintf( \"    ". $tab_str_c ."%s[%d] => \", prefix_char, ". $var_i ." );\n";
	$type_char = '';
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$struct_rs = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str. "\tprint_". $struct_rs[ 'name' ] ."( &". $p_var ."item[ ". $var_i ." ], rank + 2 );\n";
		break;
		case 'list':
			$str .= $tab_str ."\tprintf( \" List\\n\" );\n";
			$str .= tool_protocol_print_list( $list_rs[ 'sub_id' ], $p_var ."item[ ". $var_i ." ].", $rank + 1 );
		break;
		case 'byte':
			$str .= $tab_str ."\tprintf( \"[Blob %d]\\n\", ". $p_var ."item[ ". $var_i ." ].len );\n";
		break;
		case 'varchar':
		case 'char':
			$type_char = 's';
		break;
		case 'bigint';
			$type_char = 'lld';
		break;
		case 'unsigned int':
			$type_char = 'u';
		break;
		default:
			$type_char = 'd';
		break;
	}
	if ( !empty( $type_char ) )
	{
		$str .= $tab_str ."\tprintf( \"%". $type_char ."\\n\", ". $p_var ."item[ ". $var_i ." ] );\n";
	}
	$str .= $tab_str ."}\n";
	$str .= $tab_str ."printf( \"". $tab_str_c ."%s}\\n\", prefix_char );\n";
	return $str;
}

/**
 * 确认数据大小
 */
function tool_bin_protocol_struct_size_code( $pid, $rs, $items )
{
	$func_name = 'size_read_'. $rs[ 'name' ];
	$read_struct_after = array( );
	$read_list_after = array( );
	$read_string_after = array();
	$read_byte_after = array();
	$proto_name_var = "proto_". $rs[ 'name' ];
	$head_char = "\n/**\n * 解压内存 ". $rs[ 'desc' ] ."\n */\nvoid ". $func_name ."( protocol_packet_t *byte_pack, protocol_result_t *result_pool )";
	$GLOBALS[ 'size_func_define' ][] = $head_char .";\n";
	$str = $head_char ."\n{\n";
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\tuint32_t old_byte_pack_size = byte_pack->pos;\n";
		$str .= "\tresult_pool->max_pos += sizeof( ". $proto_name_var ."_t );\n";
	}
	//如果是固定长度的包
	if ( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
	{
		$str .= "\tpre_read_size( byte_pack, sizeof( ". $proto_name_var ."_t ), result_pool );\n";
	}
	else
	{
		foreach ( $items as $item_rs )
		{
			switch ( $item_rs[ 'type' ] )
			{
				case 'struct':
					$read_struct_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
				break;
				case 'list':
					$read_list_after[ $item_rs[ 'item_name' ] ] = $item_rs[ 'sub_id' ];
				break;
				case 'varchar':
					$read_string_after[] = $item_rs[ 'item_name' ];
				break;
				case 'char':
					$str .= "\tpre_read_size( byte_pack, ". $item_rs[ 'char_len' ] .", result_pool );\n";
				break;
				case 'byte':
					$read_byte_after[] = $item_rs[ 'item_name' ];
				break;
				default:
					$int_type = $GLOBALS[ 'all_type_arr' ][ $item_rs[ 'type' ] ];
					$str .= "\tpre_read_size( byte_pack, sizeof( ". $int_type ." ), result_pool );\n";
				break;
			}
		}
	}

	//字符串处理
	if ( !empty( $read_string_after ) )
	{
		foreach ( $read_string_after as $str_name )
		{
			$str .= "\tpre_read_UTF( byte_pack, result_pool );\n";
		}
	}
	//字节流
	if ( !empty( $read_byte_after ) )
	{
		foreach ( $read_byte_after as $key_name )
		{
			$str .= "\tresult_pool->max_pos += sizeof( proto_bin_t );\n";
			$str .= "\tpre_read_UTF( byte_pack, result_pool );\n";
		}
	}
	//其它struct处理
	if ( !empty( $read_struct_after ) )
	{
		foreach ( $read_struct_after as $struct_name => $struct_id )
		{
			$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
			$str .= "\tresult_pool->max_pos += sizeof( proto_". $struct_rs[ 'name' ] ."_t );\n";
			$str .= "\tsize_read_". $struct_rs[ 'name' ] ."( byte_pack, result_pool );\n";
		}
	}
	//list处理
	if ( !empty( $read_list_after ) )
	{
		$str .= "\tint i_0;\n";
		$str .= "\tuint16_t loop_len_0;\n";
		foreach ( $read_list_after as $list_name => $list_id )
		{
			$str .= tool_bin_protocol_list_size_loop( $list_id, "\t", 0 );
		}
	}
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\tbyte_pack->pos = old_byte_pack_size;\n";
	}
	$str .= "}\n";
	return $str;
}

/**
 * 解析list的循环体
 */
function tool_bin_protocol_list_size_loop( $list_id, $tab_str, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$list_type = str_replace( '*', '', $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] );
	$for_i = "i_". $rank;
	$loop_var_len = "loop_len_". $rank;
	$str = '';
	if ( 0 != $rank )
	{
		$str .= $tab_str ."uint16_t ". $loop_var_len .";\n";
		$str .= $tab_str ."int i_". $rank .";\n";
	}
	$str .= $tab_str . "result_pool->max_pos += sizeof( ". $list_type ." );\n";
	$str .= $tab_str ."pre_result_copy( byte_pack, &". $loop_var_len .", sizeof( uint16_t ), result_pool );\n";
	$str .= $tab_str ."for( ". $for_i ." = 0; ". $for_i ." < ". $loop_var_len ."; ++". $for_i ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$tmp_struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str ."\tresult_pool->max_pos += sizeof( proto_". $tmp_struct[ 'name' ] ."_t );\n";
			$str .= $tab_str ."\tsize_read_". $tmp_struct[ 'name' ] ."( byte_pack, result_pool );\n";
		break;
		case 'byte':
			$str .= $tab_str ."\tresult_pool->max_pos += sizeof( proto_bin_t );\n";
			$str .= $tab_str ."\tpre_read_UTF( byte_pack, result_pool );\n";
		break;
		case 'list':
			$str .= tool_bin_protocol_list_size_loop( $list_rs[ 'sub_id' ], $tab_str ."\t", $rank + 1 );
		break;
		case 'char':
			$str .= $tab_str ."\tresult_pool->max_pos += ( ". $list_rs[ 'char_len' ] ." + sizeof( char* ) );\n";
			$str .= $tab_str ."\tpre_read_size( byte_pack, ". $list_rs[ 'char_len' ] .", result_pool );\n";
		break;
		case 'varchar':
			$str .= $tab_str ."\tresult_pool->max_pos += sizeof( char* );\n";
			$str .= $tab_str ."\tpre_read_UTF( byte_pack, result_pool );\n";
		break;
		default:
			$item_type = $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ];
			$str .= $tab_str ."\tresult_pool->max_pos += sizeof( ". $item_type ." );\n";
			$str .= $tab_str ."\tpre_read_size( byte_pack, sizeof( ". $item_type ." ), result_pool );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	return $str;
}
?>
