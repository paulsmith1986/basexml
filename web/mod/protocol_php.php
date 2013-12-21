<?php
/**
 * PHP协议文件生成
 */
function tool_protocol_php_build( $build_path, $is_simulate = true, $is_dispatch = false, $arg = array() )
{
	$need_base64 = isset( $arg[ 'need_base64' ] ) ? $arg[ 'need_base64' ] : true;
	$unpack_mod = isset( $arg[ 'unpack_mod' ] ) ? $arg[ 'unpack_mod' ] : 3;
	$file_name = isset( $arg[ 'file_name' ] ) ? $arg[ 'file_name' ] : 'proto_simulate.php';
	$str = "<?php\n";
	$str .= tool_protocol_unpack_dispatch( $is_simulate, $unpack_mod, $need_base64, $is_dispatch );
	if ( $is_simulate )
	{
		foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
		{
			$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
			$struct_name = strtolower( $rs[ 'name' ] );
			$str .= tool_line( '/**' );
			$str .= tool_line( ' * 模拟数据 '. $rs[ 'desc' ] );
			$str .= tool_line( ' */' );
			$str .= tool_line( 'function proto_simulate_'. $struct_name .'()' );
			$str .= tool_line( '{' );
			$str .= tool_line( '$data = array();', 1 );
			foreach ( $items as $item_rs )
			{
				$item_type = $item_rs[ 'type' ];
				$item_name = $item_rs[ 'item_name' ];
				switch ( $item_type )
				{
					case 'byte':
						$str .= tool_line( '$data[ "'. $item_name .'" ] = tool_protocol_simulate_char( mt_rand( 50, 200 ) );', 1 );
					break;
					case 'char':
						$str .= tool_line( '$data[ "'. $item_name .'" ] = tool_protocol_simulate_char( '. $item_rs[ 'char_len' ] .' );', 1 );
					break;
					case 'varchar':
						$str .= tool_line( '$data[ "'. $item_name .'" ] = tool_protocol_simulate_char( mt_rand( 20, 50 ) );', 1 );
					break;
					case 'list':
						$var_name = '$arr_'. $item_name;
						$str .= tool_line( $var_name .' = array();', 1 );
						$str .= tool_protocol_php_build_list( $item_rs[ 'sub_id' ], $var_name, 1 );
						$str .= tool_line( '$data[ "'. $item_name .'" ] = '. $var_name .';', 1 );
					break;
					case 'struct':
						$struct = $GLOBALS[ 'all_protocol' ][ $item_rs[ 'sub_id' ] ];
						$str .= tool_line( '$data[ "'. $item_name .'" ] = proto_simulate_'. strtolower( $struct[ 'name' ] ) .'();', 1 );
					break;
					default:
						$str .= tool_line( '$data[ "'. $item_name .'" ] = tool_protocol_simulate_int( "'. $item_type .'" );', 1 );
					break;
				}
			}
			$str .= tool_line( 'return $data;', 1 );
			$str .= tool_line( '}' );
		}
	}
	$pack_mod = isset( $arg[ 'pack_mod' ] ) ? $arg[ 'pack_mod' ] : 3;

	if ( !isset( $arg[ 'no_pack' ] ) )
	{
		$str .= tool_protocol_php_pack( $pack_mod, $need_base64 );
		$str .= tool_protocol_pack_dispatch( $pack_mod );
	}
	if ( !isset( $arg[ 'no_unpack' ] ) )
	{
		$str .= tool_protocol_php_unpack( $unpack_mod );
		$str .= tool_protocol_unpack_func();
	}
	if ( $is_simulate )
	{
		$str .= tool_protocol_php_simulate_func();
	}
	$file = $build_path . $file_name;
	file_put_contents( $file, $str );
	echo '生成PHP协议文件:'. $file ."\n";
}

/**
 * PHP协议处理list
 */
function tool_protocol_php_build_list( $list_id, $parent, $rank = 1 )
{
	$str = '';
	$for_i = '$i_'. $rank;
	$str .= tool_line( 'for ( '. $for_i .' = 0; '. $for_i .' < mt_rand( 0, 10 ); '. $for_i .'++ )', $rank );
	$str .= tool_line( '{', $rank );
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	switch ( $list_rs[ 'type' ] )
	{
		case 'byte':
			$str .= tool_line( $parent .'[] = tool_protocol_simulate_char( mt_rand( 50, 200 ) );', $rank + 1 );
		break;
		case 'char':
			$str .= tool_line( $parent .'[] = tool_protocol_simulate_char( '. $list_rs[ 'char_len' ] .' );', $rank + 1 );
		break;
		case 'varchar':
			$str .= tool_line( $parent .'[] = tool_protocol_simulate_char( mt_rand( 20, 500 ) );', $rank + 1 );
		break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= tool_line( $parent .'[] = proto_simulate_'. strtolower( $struct[ 'name' ] ) .'();', $rank + 1 );
		break;
		case 'list':
			$arr_name = '$arr_'. $rank;
			$str .= tool_line( $arr_name .' = array();', $rank + 1 );
			$str .= tool_protocol_php_build_list( $list_rs[ 'sub_id' ], $arr_name, $rank + 1 );
			$str .= tool_line( $parent .'[] = '. $arr_name .';', $rank + 1 );
		break;
		default:
			$str .= tool_line( $parent .'[] = tool_protocol_simulate_int( "'. $list_rs[ 'type' ] .'" );', $rank + 1 );
		break;
	}
	$str .= tool_line( '}', $rank );
	return $str;
}

/**
 * 打包
 */
function tool_protocol_php_pack( $pack_mod, $need_base64 )
{
	$str = "\n";
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( !( $pack_mod & $rs[ 'proto_type' ] ) )
		{
			continue;
		}
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$struct_name = strtolower( $rs[ 'name' ] );
		$str .= tool_line( '/**' );
		$str .= tool_line( ' * 打包数据 '. $rs[ 'desc' ] );
		$str .= tool_line( ' */' );
		$func_str = 'function proto_pack_'. $struct_name .'( $data';
		if ( !$rs[ 'is_sub' ] && $need_base64 )
		{
			$func_str .= ', $base64 = false';
		}
		$func_str .= ' )';
		$str .= tool_line( $func_str );
		$str .= tool_line( '{' );
		$str .= tool_line( '$bin_str = \'\';', 1 );
		foreach ( $items as $item_rs )
		{
			$item_type = $item_rs[ 'type' ];
			$item_name = $item_rs[ 'item_name' ];
			$value_char = '$data[ "'. $item_name .'" ]';
			switch ( $item_type )
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
				case 'char':
					$str .= tool_line( '$bin_str .= pack( "a'. $item_rs[ 'char_len' ] .'", '. $value_char .' );', 1 );
				break;
				default:
					$char_type = tool_protocol_pack_int( $item_type );
					$str .= tool_line( '$bin_str .= pack( "'. $char_type .'", '. $value_char .' );', 1 );
				break;
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			foreach ( $read_string_after as $str_name )
			{
				$var = '$data[ "'. $str_name .'" ]';
				$str .= tool_line( '$varchar_len = strlen( '. $var .' );', 1 );
				$str .= tool_line( '$bin_str .= pack( "S", $varchar_len );', 1 );
				$str .= tool_line( '$bin_str .= pack( "a". $varchar_len, '. $var .' );', 1 );
			}
		}
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $key_name )
			{
				$var = '$data[ "'. $key_name .'" ]';
				$str .= tool_line( '$varchar_len = strlen( '. $var .' );', 1 );
				$str .= tool_line( '$bin_str .= pack( "S", $varchar_len );', 1 );
				$str .= tool_line( '$bin_str .= pack( "a". $varchar_len, '. $var .' );', 1 );
			}
		}

		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
				$str .= tool_line( '$bin_str .= proto_pack_'. $struct_rs[ 'name' ] .'( $data[ "'. $struct_name .'" ] );', 1 );
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_protocol_simulate_pack_list( $list_id, '$data[ "'. $list_name .'" ]', 1 );
			}
		}
		//主协议要加包包头 判断输出方式
		if ( !$rs[ 'is_sub' ] )
		{
			$str .= tool_line( '$head_str = pack( "LS", strlen( $bin_str ), '. $rs[ 'struct_id' ] .' );', 1 );
			$str .= tool_line( '$bin_str = $head_str . $bin_str;', 1 );
			if ( $need_base64 )
			{
				$str .= tool_line( 'if( $base64 )', 1 );
				$str .= tool_line( '{', 1 );
				$str .= tool_line( '$bin_str = base64_encode( $bin_str );', 2 );
				$str .= tool_line( '}', 1 );
			}
		}
		$str .= tool_line( 'return $bin_str;', 1 );
		$str .= tool_line( '}' );
	}
	return $str;
}

/**
 * 打包一个list
 */
function tool_protocol_simulate_pack_list( $list_id, $parent_data, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$str = '';
	$for_var = '$sub_dat'. $rank;
	$str .= tool_line( '$bin_str .= pack( "S", count( '. $parent_data .' ) );', $rank );
	$str .= tool_line( 'foreach( '. $parent_data .' as '. $for_var .' )', $rank );
	$str .= tool_line( '{', $rank );
	switch ( $list_rs[ 'type' ] )
	{
		case 'varchar':
		case 'byte':
			$str .= tool_line( '$tmp_len = strlen( '. $for_var .' );', $rank + 1 );
			$str .= tool_line( '$bin_str .= pack( "Sa". $tmp_len, $tmp_len, '. $for_var .' );', $rank + 1 );
		break;
		case 'char':
			$str .= tool_line( '$bin_str .= pack( "a'. $list_rs[ 'char_len' ] .'", '. $for_var .' );', $rank + 1 );
		break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= tool_line( '$bin_str .= proto_pack_'. $struct[ 'name' ] .'( '. $for_var .' );', $rank + 1 );
		break;
		case 'list':
			$str .= tool_protocol_simulate_pack_list( $list_rs[ 'sub_id' ], $for_var, $rank + 1 );
		break;
		default:
			$char_type = tool_protocol_pack_int( $list_rs[ 'type' ] );
			$str .= tool_line( '$bin_str .= pack( "'. $char_type .'", '. $for_var .' );', $rank + 1 );
		break;
	}
	$str .= tool_line( '}', $rank );
	return $str;
}

/**
 * 生成dispatch
 */
function tool_protocol_unpack_dispatch( $is_simulate, $unpack_mod, $need_base64, $is_dispatch = false )
{
	$proto_list = array();
	$str = '';
	$str .= tool_line( '/**' );
	$str .= tool_line( ' * 解包路由' );
	$str .= tool_line( ' */' );
	$empty_protocol = array();
	$tmp_line = 'function proto_unpack_data( $byte_data';
	if ( $need_base64 )
	{
		$tmp_line .= ', $base64 = false';
	}
	$tmp_line .= ' )';
	$str .= tool_line( $tmp_line );
	$str .= tool_line( '{' );
	if ( $need_base64 )
	{
		$str .= tool_line( 'if( $base64 )', 1 );
		$str .= tool_line( '{', 1 );
		$str .= tool_line( '$byte_data = base64_decode( $byte_data );', 2 );
		$str .= tool_line( '}', 1 );
	}
	$str .= tool_line( '$bin_len = strlen( $byte_data );', 1 );
	$str .= tool_line( '$head_len = 6;', 1 );
	$str .= tool_line( 'if( $bin_len < $head_len )', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( 'throw new Exception( "Bad protocol data!\n", 60001 );', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( '$head = unpack( "Llen/Spack_id", $byte_data );', 1 );
	$str .= tool_line( 'if ( $head[ "len" ] != $bin_len - $head_len )', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( 'throw new Exception( "Bad protocol data!\n", 60001 );', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( '$pack_id = $head[ \'pack_id\' ];', 1 );
	$str .= tool_line( '$bin_str = substr( $byte_data, $head_len );', 1 );
	$str .= tool_line( 'switch( $pack_id )', 1 );
	$str .= tool_line( '{', 1 );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] || !( $unpack_mod & $rs[ 'proto_type' ] ) )
		{
			continue;
		}
		$proto_list[ $rs[ 'name' ] ] = $rs[ 'desc' ] . ' 【'. $rs[ 'struct_id' ] .'】';
		if ( empty( $GLOBALS[ 'all_protocol_item' ][ $pid ] ) )
		{
			$empty_protocol[] = $rs[ 'struct_id' ];
		}
		else
		{
			$str .= tool_line( 'case '. $rs[ 'struct_id' ] .':', 2 );
			$str .= tool_line( '$data = proto_unpack_'. $rs[ 'name' ] .'( $bin_str );', 3 );
			$str .= tool_line( 'break;', 2 );
		}
	}
	if ( !empty( $empty_protocol ) )
	{
		foreach ( $empty_protocol as $pid )
		{
			$str .= tool_line( 'case '. $pid .':', 2 );
		}
		$str .= tool_line( '$data = null;', 3 );
		$str .= tool_line( 'break;', 2 );
	}
	$str .= tool_line( 'default:', 2 );
	$str .= tool_line( 'throw new Exception( "Unkown pack_id:". $pack_id ."\n", 60002 );', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( '}', 1 );
	if ( $is_dispatch )
	{
		$str .= tool_line( 'proto_action_dispatch( $pack_id, $data );', 1 );
	}
	else
	{
		$str .= tool_line( 'return $data;', 1 );
	}
	$str .= tool_line( '}' );
	if ( $is_simulate )
	{
		$str .= tool_line( '$GLOBALS[ "all_protocol_list" ] = '. var_export( $proto_list, true ) .';' );
	}
	return $str;

}

/**
 * 生成pack的dispatch
 */
function tool_protocol_pack_dispatch( $pack_mod )
{
	$proto_list = array();
	$str = '';
	$str .= tool_line( '/**' );
	$str .= tool_line( ' * pack路由' );
	$str .= tool_line( ' */' );
	$tmp_line = 'function proto_pack_data( $pack_id, $data )';
	$str .= tool_line( $tmp_line );
	$str .= tool_line( '{' );
	$str .= tool_line( 'switch( $pack_id )', 1 );
	$str .= tool_line( '{', 1 );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] || !( $pack_mod & $rs[ 'proto_type' ] ) )
		{
			continue;
		}
		$str .= tool_line( 'case '. $rs[ 'struct_id' ] .':', 2 );
		$str .= tool_line( '$data = proto_pack_'. $rs[ 'name' ] .'( $data );', 3 );
		$str .= tool_line( 'break;', 2 );
	}
	$str .= tool_line( 'default:', 2 );
	$str .= tool_line( 'throw new Exception( "Unkown pack_id:". $pack_id ."\n", 60002 );', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( 'return $data;' );
	$str .= tool_line( '}' );
	return $str;
}

/**
 * 解包
 */
function tool_protocol_php_unpack( $pack_mod )
{
	$str = "\n";
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( !( $pack_mod & $rs[ 'proto_type' ] ) )
		{
			continue;
		}
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		if ( empty( $items ) && !$rs[ 'is_sub' ] )
		{
			continue;
		}
		$struct_name = strtolower( $rs[ 'name' ] );
		$str .= tool_line( '/**' );
		$str .= tool_line( ' * 解包数据 '. $rs[ 'desc' ] );
		$str .= tool_line( ' */' );
		$func_str = 'function proto_unpack_'. $struct_name .'( $bin_str';
		if ( $rs[ 'is_sub' ] )
		{
			$func_str .= ', &$unpack_pos';
		}
		$func_str .= ' )';
		$str .= tool_line( $func_str );
		$str .= tool_line( '{' );
		$upack_str = '';
		$pos = 0;
		if ( !$rs[ 'is_sub' ] && !$GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
		{
			$str .= tool_line( '$unpack_pos = 0;', 1 );
		}
		if ( empty( $items ) )
		{
			$str .= tool_line( '$result = null;', 1 );
		}
		//固定长度
		else if( $GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] )
		{
			foreach ( $items as $item_rs )
			{
				if ( 'char' == $item_rs[ 'type' ] )
				{
					$upack_str .= 'a'. $item_rs[ 'char_len' ];
					$pos += $item_rs[ 'char_len' ];
				}
				else
				{
					$upack_str .= tool_protocol_pack_int( $item_rs[ 'type' ], $len );
					$pos += $len;
				}
				$upack_str .= $item_rs[ 'item_name' ] .'/';
			}
			$str .= tool_line( '$result = unpack( "'. $upack_str .'", $bin_str );', 1 );
			if ( $rs[ 'is_sub' ] )
			{
				$str .= tool_line( '$unpack_pos += '. $pos .';', 1 );
			}
		}
		//不固定长度的
		else
		{
			foreach ( $items as $item_rs )
			{
				$item_type = $item_rs[ 'type' ];
				$item_name = $item_rs[ 'item_name' ];
				switch ( $item_type )
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
					case 'char':
						$upack_str .= 'a'. $item_rs[ 'char_len' ] . $item_rs[ 'item_name' ] .'/';
						$pos += $item_rs[ 'char_len' ];
					break;
					default:
						$char_type = tool_protocol_pack_int( $item_type, $len );
						$pos += $len;
						$upack_str .= $char_type . $item_rs[ 'item_name' ] .'/';
					break;
				}
			}
			if ( !empty( $upack_str ) )
			{
				$str .= tool_line( '$result = unpack( "'. $upack_str .'", substr( $bin_str, $unpack_pos ) );', 1 );
				if ( $rs[ 'is_sub' ] )
				{
					$str .= tool_line( '$unpack_pos += '. $pos .';', 1 );
				}
				else
				{
					$str .= tool_line( '$unpack_pos = '. $pos .';', 1 );
				}

			}
			else
			{
				$str .= tool_line( '$result = array();', 1 );
			}
			//字符串处理
			if ( !empty( $read_string_after ) )
			{
				foreach ( $read_string_after as $str_name )
				{
					$str .= tool_protocol_unpack_string( '$result[ "'. $str_name .'" ]', 1 );
				}
			}
			if ( !empty( $read_byte_after ) )
			{
				foreach ( $read_byte_after as $key_name )
				{
					$str .= tool_protocol_unpack_string( '$result[ "'. $key_name .'" ]', 1 );
				}
			}

			//其它struct处理
			if ( !empty( $read_struct_after ) )
			{
				foreach ( $read_struct_after as $struct_name => $struct_id )
				{
					$struct_rs = $GLOBALS[ 'all_protocol' ][ $struct_id ];
					$str .= tool_line( '$result[ "'. $struct_name .'" ] = proto_unpack_'. $struct_rs[ 'name' ] . '( $bin_str, $unpack_pos );', 1 );
				}
			}
			//list处理
			if ( !empty( $read_list_after ) )
			{
				foreach ( $read_list_after as $list_name => $list_id )
				{
					$str .= tool_protocol_php_unpack_list( $list_id, '$result[ "'. $list_name .'" ]', 1 );
				}
			}
		}
		$str .= tool_line( 'return $result;', 1 );
		$str .= tool_line( '}' );
	}
	return $str;
}

/**
 * 解list包
 */
function tool_protocol_php_unpack_list( $list_id, $parent, $rank )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$str = '';
	$result_arr = '$sub_arr'. $rank;
	$len_var = '$len_'. $rank;
	$for_var = '$i_'. $rank;
	$str .= tool_line( $result_arr. ' = array();', $rank );
	$str .= tool_line( '$tmp_len = substr( $bin_str, $unpack_pos, 2 );', $rank );
	$str .= tool_line( '$unpack_pos += 2;', $rank );
	$str .= tool_line( '$tmp_len_re = unpack( "Slen", $tmp_len );', $rank );
	$str .= tool_line( $len_var .' = $tmp_len_re[ "len" ];', $rank );
	$int_list = array(
		'tinyint'			=> true,
		'int'				=> true,
		'smallint'			=> true,
		'bigint'			=> true,
		'unsigned tinyint'	=> true,
		'unsigned int'		=> true,
		'unsigned smallint'	=> true
	);
	if ( isset( $int_list[ $list_rs[ 'type' ] ] ) )
	{
		$int_type = tool_protocol_pack_int( $list_rs[ 'type' ], $len );
		$str .= tool_line( $parent .' = unpack( "'. $int_type .'". '. $len_var .', substr( $bin_str, $unpack_pos ) );', $rank );
		$str .= tool_line( '$unpack_pos += ( '. $len_var .' * '. $len .' );', $rank );
	}
	else
	{
		$str .= tool_line( 'for( '. $for_var .' = 0; '. $for_var .' < '. $len_var .'; ++'. $for_var .'  )', $rank );
		$str .= tool_line( '{', $rank );
		switch ( $list_rs[ 'type' ] )
		{
			case 'varchar':
			case 'byte':
				$str .= tool_line( $result_arr .'[] = tool_protocol_unpack_str( $bin_str, $unpack_pos );', $rank + 1 );
			break;
			case 'char':
				$str .= tool_line( '$tmp_re = unpack( "a'. $list_rs[ 'char_len' ] .'str", substr( $bin_str, $unpack_pos ) );', $rank + 1 );
				$str .= tool_line( $result_arr .'[] = $tmp_re[ "str" ]', $rank + 1 );
				$str .= tool_line( '$unpack_pos +='. $list_rs[ 'char_len' ] .';', $rank + 1 );
			break;
			case 'struct':
				$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
				$str .= tool_line( $result_arr .'[] = proto_unpack_'. $struct[ 'name' ] .'( $bin_str, $unpack_pos );', $rank + 1 );
			break;
			case 'list':
				$str .= tool_protocol_php_unpack_list( $list_rs[ 'sub_id' ], $result_arr .'[ '. $for_var .' ]', $rank + 1 );
			break;
			default:
				$char_type = tool_protocol_pack_int( $list_rs[ 'type' ], $len );
				$str .= tool_line( '$bin_str .= pack( "'. $char_type .'", '. $result_arr .' );', $rank + 1 );
			break;
		}
		$str .= tool_line( '}', $rank );
		$str .= tool_line( $parent .' = '. $result_arr .';', $rank );
	}
	return $str;
}

/**
 * 字符串解包
 */
function tool_protocol_unpack_string( $parent, $rank )
{
	return tool_line( $parent .' = tool_protocol_unpack_str( $bin_str, $unpack_pos );', $rank );
}

/**
 * 打包数字
 */
function tool_protocol_pack_int( $type, &$len = 0 )
{
	switch ( $type )
	{
		case 'tinyint':
			$char = 'c';
			$len = 1;
		break;
		case 'int':
			$char = 'l';
			$len = 4;
		break;
		case 'smallint':
			$char = 's';
			$len = 2;
		break;
		case 'bigint':
			$char = 'q';
			$len = 8;
		break;
		case 'unsigned tinyint':
			$char = 'C';
			$len = 1;
		break;
		case 'unsigned int':
			$char = 'L';
			$len = 4;
		break;
		case 'unsigned smallint':
			$char = 'S';
			$len = 2;
		break;
	}
	if ( !isset( $char ) )
	{
		var_dump( $type );
	}
	return $char;
}

/**
 * 功能函数代码
 */
function tool_protocol_php_simulate_func()
{
	$str = '';
	$str .= tool_line( '/**' );
	$str .= tool_line( ' * 生成模拟数字' );
	$str .= tool_line( ' */' );
	$str .= tool_line( 'function tool_protocol_simulate_int( $type )' );
	$str .= tool_line( '{' );
	$str .= tool_line( 'switch ( $type )', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( 'case "tinyint":', 2 );
	$str .= tool_line( '$beg = -5;', 3 );
	$str .= tool_line( '$end = 50;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "smallint":', 2 );
	$str .= tool_line( '$beg = -25;', 3 );
	$str .= tool_line( '$end = 2500;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "int":', 2 );
	$str .= tool_line( '$beg = -50;', 3 );
	$str .= tool_line( '$end = 5000;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "bigint":', 2 );
	$str .= tool_line( '$beg = -200;', 3 );
	$str .= tool_line( '$end = 2000000;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "unsigned tinyint":', 2 );
	$str .= tool_line( '$beg = 0;', 3 );
	$str .= tool_line( '$end = 100;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "unsigned smallint":', 2 );
	$str .= tool_line( '$beg = 0;', 3 );
	$str .= tool_line( '$end = 5000;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( 'case "unsigned int":', 2 );
	$str .= tool_line( '$beg = 0;', 3 );
	$str .= tool_line( '$end = 10000;', 3 );
	$str .= tool_line( 'break;', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( 'return mt_rand( $beg, $end );', 1 );
	$str .= tool_line( '}' );
	$str .= tool_line( '/**' );
	$str .= tool_line( ' * 模拟生成字符串' );
	$str .= tool_line( ' */' );
	$str .= tool_line( 'function tool_protocol_simulate_char( $length )' );
	$str .= tool_line( '{' );
	$str .= tool_line( '$output = "";', 1 );
	$str .= tool_line( 'for ( $i = 0; $i < mt_rand( 1, $length ); ++$i )', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( '$output .= chr( mt_rand( 65, 90 ) );', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( 'return $output;', 1 );
	$str .= tool_line( '}' );
	return $str;
}

/**
 * 解包函数
 */
function tool_protocol_unpack_func()
{
	$str = tool_line( '/**' );
	$str .= tool_line( ' * 反解字符串' );
	$str .= tool_line( ' */' );
	$str .= tool_line( 'function tool_protocol_unpack_str( $bin_str, &$uppack_pos )' );
	$str .= tool_line( '{' );
	$str .= tool_line( '$len_str = substr( $bin_str, $uppack_pos, 2 );', 1 );
	$str .= tool_line( '$uppack_pos += 2;', 1 );
	$str .= tool_line( '$tmp = unpack( "Slen", $len_str );', 1 );
	$str .= tool_line( 'if ( 0 == $tmp[ "len" ] )', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( '$str = "";', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( 'else', 1 );
	$str .= tool_line( '{', 1 );
	$str .= tool_line( '$str = substr( $bin_str, $uppack_pos, $tmp[ "len" ] );', 2 );
	$str .= tool_line( '$uppack_pos += $tmp[ "len" ];', 2 );
	$str .= tool_line( '}', 1 );
	$str .= tool_line( 'return $str;', 1 );
	$str .= tool_line( '}' );
	return $str;
}