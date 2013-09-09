<?php
/**
 * 通用函数
 */
function tool_cpp_protocol_common( $struct_prefix = 'proto' )
{
	$GLOBALS[ 'all_type_arr' ] = array(
		'tinyint'				=> 'int8_t',
		'unsigned tinyint'		=> 'uint8_t',
		'smallint'				=> 'int16_t',
		'unsigned smallint'		=> 'uint16_t',
		'int'					=> 'int32_t',
		'unsigned int'			=> 'uint32_t',
		'bigint'				=> 'int64_t',
		'varchar'				=> 'string',
		'char'					=> 'char',
		'byte'					=> 'proto_bin_t*',
	);
	$GLOBALS[ 'class_define' ] = array();
	$GLOBALS[ 'typedef_detail' ] = array();
	$GLOBALS[ 'all_list_define' ] = array();
	$GLOBALS[ 'list_type_def' ] = array();
	$type_arr = array( 1 => 'In', 2 => 'Out', 3 => 'Ds' );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$name = $rs[ 'name' ];
		$struct_name = tool_protocol_cpp_struct_name( $rs, $type_arr[ $rs[ 'proto_type' ] ] );
		$GLOBALS[ 'all_type_arr' ][ 'class_'. $pid ] = $struct_name;
	}
	foreach ( $GLOBALS[ 'all_list' ] as $list_id => $rs )
	{
		tool_protocol_cpp_vector( $list_id );
	}
	$GLOBALS[ 'done_class_list' ] = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		tool_protocol_cpp_class( $pid );
	}
}

/**
 * 生成一个struct的定义
 */
function tool_protocol_cpp_class( $pid )
{
	if ( isset( $GLOBALS[ 'done_class_list' ][ $pid ] ) )
	{
		return;
	}
	$GLOBALS[ 'done_class_list' ][ $pid ] = true;
	$proto_rs = $GLOBALS[ 'all_protocol' ][ $pid ];
	$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
	$name = $proto_rs[ 'name' ];
	$GLOBALS[ 'all_protocol_item' ][ $pid ];
	$class_name = $GLOBALS[ 'all_type_arr' ][ 'class_'. $pid ];
	$str = tool_line( '/**' );
	$str .= tool_line( ' * '. $proto_rs[ 'desc' ] );
	$str .= tool_line( ' */' );
	$str .= tool_line( 'class '. $class_name );
	$str .= tool_line( '{' );
	$str .= tool_line( 'public:', 1 );
	if ( !$proto_rs[ 'is_sub' ] )
	{
		$str .= tool_line( $class_name .'() : proto_id_( '. $proto_rs[ 'struct_id' ] .' )', 2 );
		$str .= tool_line( '{', 2 );
		if ( $proto_rs[ 'proto_type' ] & 1 )
		{
			$str .= tool_line( 'tmp_write_buff_.str = default_buff_char_;', 3 );
			$str .= tool_line( 'tmp_write_buff_.pos = sizeof( packet_head_t );', 3 );
			$str .= tool_line( 'tmp_write_buff_.is_resize = 0;', 3 );
			$str .= tool_line( 'tmp_write_buff_.max_pos = DEFAULT_CHAR_BUFF_LEN;', 3 );
			$str .= tool_line( '}', 2 );
			$str .= tool_line( '~'. $class_name .'()', 2 );
			$str .= tool_line( '{', 2 );
			$str .= tool_line( 'try_free_result_pack( tmp_write_buff_ );', 3 );
		}
		$str .= tool_line( '}', 2 );
		$str .= tool_line( '', 2 );
		$str .= tool_line( 'inline uint16_t get_pid(){ return proto_id_; }', 2 );
		if ( $proto_rs[ 'proto_type' ] & 1 )
		{
			$str .= tool_line( '' );
			$str .= tool_line( "protocol_result_t *getBuff(){ return &tmp_write_buff_; }", 2 );
		}
	}
	if ( $proto_rs[ 'proto_type' ] & 1 )
	{
		$tmp_func = "void write(";
		if ( $proto_rs[ 'is_sub' ] )
		{
			$tmp_func .= ' protocol_result_t *byte_buff_ ';
		}
		$tmp_func .= ');';
		$str .= tool_line( '' );
		$str .= tool_line( $tmp_func, 2 );
	}
	if ( $proto_rs[ 'proto_type' ] & 2 )
	{
		$str .= tool_line( '' );
		$str .= tool_line( 'void read( protocol_packet_t *pack_data );', 2 );
	}
	foreach ( $items as $item_rs )
	{
		switch ( $item_rs[ 'type' ] )
		{
			case 'struct':
				$type = 'class_'. $item_rs[ 'sub_id' ];
				tool_protocol_cpp_class( $item_rs[ 'sub_id' ] );
			break;
			case 'list':
				$type = 'list_'. $item_rs[ 'sub_id' ];
				tool_protocol_cpp_list_class( $item_rs[ 'sub_id' ] );
			break;
			case 'byte':
				$type = 'byte';
			break;
			default:
				$type = $item_rs[ 'type' ];
			break;
		}
		$str .= tool_line( '' );
		$str .= tool_line( ' /**'. $item_rs[ 'desc' ] .'**/', 2 );
		$name = $GLOBALS[ 'all_type_arr' ][ $type ] .' ';
		$def_name = $item_rs[ 'item_name' ];
		if ( 'char' == $item_rs[ 'type' ] )
		{
			$def_name .= '[ '. $item_rs[ 'char_len' ] .' ]';
		}
		$str .= tool_line( $name .' '. $def_name .';', 2 );
	}
	if ( !$proto_rs[ 'is_sub' ] )
	{
		$str .= tool_line( 'private:', 1 );
		$str .= tool_line( 'const uint16_t proto_id_;', 2 );
		if ( $proto_rs[ 'proto_type' ] & 1 )
		{
			$str .= tool_line( 'protocol_result_t tmp_write_buff_;', 2 );
			$str .= tool_line( 'char default_buff_char_[ DEFAULT_CHAR_BUFF_LEN ];', 2 );
		}
	}
	$str .= tool_line( '};' );
	$GLOBALS[ 'typedef_detail' ][] = $str;
}

/**
 * 检查list里有没有struct
 */
function tool_protocol_cpp_list_class( $list_id )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			tool_protocol_cpp_class( $list_rs[ 'sub_id' ] );
		break;
		case 'list':
			tool_protocol_cpp_list_class( $list_rs[ 'sub_id' ] );
		break;
	}
}

/**
 * struct的定义
 */
function tool_protocol_cpp_struct_name( $rs, $class_fix = '' )
{
	$name = str_to_camel( $rs[ 'name' ] );
	if( $rs['is_sub'] )
	{
		$name .= 'Struct';
	}
	return $name;
}

/**
 * 数组
 */
function tool_protocol_cpp_vector( $list_id )
{
	$data = $GLOBALS[ 'all_list' ][ $list_id ];
	$begstr = 'std::vector< ';
	switch ( $data[ 'type' ] )
	{
		case 'list':
			$str = tool_protocol_cpp_vector( $data[ 'sub_id' ] );
		break;
		case 'struct':
			$str = $GLOBALS[ 'all_type_arr' ][ 'class_'. $data[ 'sub_id' ] ];
		break;
		case 'char':
			$str = $GLOBALS[ 'all_type_arr' ][ $data[ 'type' ] ] .'*';
		break;
		default:
			$str = $GLOBALS[ 'all_type_arr' ][ $data[ 'type' ] ];
		break;
	}
	$endstr = ' >';
	$def_name = $begstr . $str .$endstr;
	$GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] = $def_name;
	$GLOBALS[ 'all_type_arr' ][ 'list_vector_'. $list_id ] = $str;
	return $def_name;
}

/**
 * 生成客户端AS3协议包类
 */
function tool_protocol_cpp( $build_path )
{
	tool_cpp_protocol_common();
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$GLOBALS[ 'list_for_var' ] = array();
		$GLOBALS[ 'list_for_var_parse' ] = array();
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$code = tool_cpp_protocol_struct_code( $pid, $rs, $items );
		$file_name = $GLOBALS[ 'all_type_arr' ][ 'class_'. $pid ];
		$file = $build_path . $file_name .'.cpp';
		file_put_contents( $file, $code );
		echo '生成文件:'. $file, "\n";
	}
	$head_name = 'FIRST_PROTOCOL_CPP_HEAD';
	$head_line = '#ifndef '. $head_name ."\n";
	$head_line .= '#define '. $head_name ."\n";
	$head_line .= "using namespace std;\n";
	$head_line .= "#define DEFAULT_CHAR_BUFF_LEN 1024\n";
	$head_line .= join( "\n", $GLOBALS[ 'typedef_detail' ] );
	$head_line .= "#endif\n";
	$file = $build_path .'protocol_class.h';
	file_put_contents( $file, $head_line );
	echo '生成头文件:', $file, "\n";
}

/**
 * cpp转二进制协议
 */
function tool_cpp_protocol_struct_code( $pid, $rs, $items )
{
	$class_name = $GLOBALS[ 'all_type_arr' ][ 'class_'. $pid ];
	$str = tool_line( '#include "first_protocol_cpp.h"' );
	$str .= tool_line( '#include "protocol_class.h"' );
	if ( $rs[ 'proto_type' ] & 1 )
	{
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$tmp_func = 'void '. $class_name .'::write(';
		if ( $rs[ 'is_sub' ] )
		{
			$tmp_func .= ' protocol_result_t *byte_buff_ ';
		}
		$tmp_func .= ')';
		$str .= tool_line( $tmp_func );
		$str .= tool_line( '{' );
		if ( !$rs[ 'is_sub' ] )
		{
			$str .= tool_line( 'protocol_result_t *byte_buff_ = &tmp_write_buff_;', 1 );
		}
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
				case 'byte':	//字节流
					$read_byte_after[] = $item_name;
				break;
				default:
					$str .= tool_line( 'first_result_push_data( byte_buff_, &this->'. $item_name .', sizeof( this->'. $item_name .' ) );', 1 );
				break;
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			foreach ( $read_string_after as $str_name )
			{
				$str .= tool_line( 'proto_write_string( byte_buff_, this->'. $str_name .' );', 1 );
			}
		}
		//字节流
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $key_name )
			{
				$str .= tool_line( 'first_result_push_data( byte_buff_, &this->'. $key_name .'.len, sizeof( bytes_len_t ) );', 1 );
				$str .= tool_line( 'first_result_push_data( byte_buff_, this->'. $key_name .'.bytes, this->'. $key_name .'.bytes );', 1 );
			}
		}
		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$str .= tool_line( 'this->'. $struct_name .'.write( byte_buff_ );', 1 );
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_cpp_protocol_list_loop( $list_id, "this->". $list_name, 1, $pid, $list_name );
			}
		}
		if ( !$rs[ 'is_sub' ] )
		{
			$str .= tool_line( 'packet_head_t proto_head;', 1 );
			$str .= tool_line( 'proto_head.size = tmp_write_buff_.pos - sizeof( packet_head_t );', 1 );
			$str .= tool_line( 'proto_head.pack_id = this->proto_id_;', 1 );
			$str .= tool_line( 'memcpy( tmp_write_buff_.str, &proto_head, sizeof( packet_head_t ) );', 1 );
		}
		$str .= tool_line( '}' );
	}
	if ( $rs[ 'proto_type' ] & 2 )
	{
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$str .= tool_line( 'void '. $class_name .'::read( protocol_packet_t *pack_data )' );
		$str .= tool_line( '{' );
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
				case 'byte':
					$read_byte_after[] = $item_name;
				break;
				case 'varchar':
					$read_string_after[] = $item_name;
				break;
				default:
					$str .= tool_line( 'cpp_result_copy( pack_data, &this->'. $item_name .', sizeof( this->'. $item_name .' ) );', 1 );
				break;
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			$str .= tool_line( 'string_len_t str_len;', 1 );
			$str .= tool_line( 'uint32_t old_pos;', 1 );
			foreach ( $read_string_after as $str_name )
			{
				$str .= tool_line( 'cpp_result_copy( pack_data, &str_len, sizeof( string_len_t ) );', 1 );
				$str .= tool_line( 'old_pos = pack_data->pos;', 1 );
				$str .= tool_line( 'cpp_add_pack_data_len( pack_data, str_len );', 1 );
				$str .= tool_line( 'this->'. $str_name .' = string( &pack_data->data[ old_pos ], str_len );', 1 );
			}
		}
		//字节流处理
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $byte_name )
			{
				$str .= tool_line( 'cpp_result_copy( pack_data, &this->'. $byte_name .'.len, sizeof( bytes_len_t ) );', 1 );
				$str .= tool_line( 'this->'. $byte_name .'.bytes = &pack_data->data[ pack_data->pos ];', 1 );
				$str .= tool_line( 'cpp_add_pack_data_len( pack_data, this->'. $byte_name .'.len );', 1 );
			}
		}
		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$class_name = $GLOBALS[ 'all_type_arr' ][ 'class_'. $struct_id ];
				$str .= tool_line( 'this->'. $struct_name .'.read( pack_data );', 1 );
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_cpp_protocol_list_loop_parse( $list_id, "this->". $list_name, 1, $list_name );
			}
		}
		$str .= tool_line( '}' );
	}
	return $str;
}

/**
 * 循环体代码
 */
function tool_cpp_protocol_list_loop( $list_id, $parent, $rank, $struct_id, $list_name )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$for_var = "_for_". $list_name .'_'. $rank;
	$len_var = 'list_'. $list_name .'_len';
	$str = tool_line( 'uint16_t '. $len_var .' = '. $parent .'.size();', $rank );
	$str .= tool_line( 'first_result_push_data( byte_buff_, &'. $len_var .', sizeof( uint16_t ) );', $rank );
	if ( !isset( $GLOBALS[ 'list_for_var' ][ $for_var ] ) )
	{
		$str .= tool_line( 'size_t '. $for_var .';', $rank );
		$GLOBALS[ 'list_for_var' ][ $for_var ] = true;
	}
	$str .= tool_line( 'for ( '. $for_var .' = 0;'. $for_var .' < '. $parent .'.size(); ++'. $for_var .' )', $rank );
	$str .= tool_line( '{', $rank );
	$for_value = 'tmp_'. $list_name .'_'. $rank;
	$for_type = $GLOBALS[ 'all_type_arr' ][ 'list_vector_'. $list_id ];
	switch ( $list_rs[ 'type' ] )
	{
		case 'varchar':
			$str .= tool_line( $for_type .' *'. $for_value .' = &'. $parent .'[ '. $for_var .' ];', $rank + 1 );
			$str .= tool_line( 'proto_write_string( byte_buff_, '. $for_value .' );', $rank + 1 );
		break;
		case 'byte':
			$str .= tool_line( $for_type .' *'. $for_value .' = &'. $parent .'[ '. $for_var .' ];', $rank + 1 );
			$str .= tool_line( 'bytes_len_t byte_len = '. $for_value .'->len;', 1 );
			$str .= tool_line( 'first_result_push_data( byte_buff_, &byte_len, sizeof( bytes_len_t ) );', $rank + 1 );
			$str .= tool_line( 'first_result_push_data( byte_buff_, '. $for_value .'->bytes, byte_len );', $rank + 1 );
		break;
		case 'char':
			$str .= tool_line( 'first_result_push_data( byte_buff_, '. $parent .'[ '. $for_var .' ], '. $list_rs[ 'char_len' ] .' );', $rank + 1 );
		break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= tool_line( $for_type .' *'. $for_value .' = &'. $parent .'[ '. $for_var .' ];', $rank + 1 );
			$str .= tool_line( $for_value .'->write( byte_buff_ );', $rank + 1 );
		break;
		case 'list':
			$str .= tool_as_protocol_list_loop( $list_rs[ 'sub_id' ], $parent .'[ '. $for_var .' ]', $rank + 1, $struct_id, $list_name );
		break;
		default:
			$str .= tool_line( 'first_result_push_data( byte_buff_, &'. $parent .'[ '. $for_var .' ], sizeof( '. $GLOBALS[ 'all_type_arr' ][ 'list_vector_'. $list_id ] .' ) );', $rank + 1 );
		break;
	}
	$str .= tool_line( '}', $rank );
	return $str;
}

/**
 * 解析list
 */
function tool_cpp_protocol_list_loop_parse( $list_id, $parent_var, $rank, $list_name )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$len_var = "vector_len". $rank;
	$for_var = "_i". $rank;
	$value_var = "tmp_value_". $list_name . $rank;
	$str = '';
	if ( !isset( $GLOBALS[ 'list_for_var_parse' ][ $len_var ] ) )
	{
		$str .= tool_line( 'uint16_t '. $len_var .';', $rank );
		$GLOBALS[ 'list_for_var_parse' ][ $len_var ] = true;
	}
	$str .= tool_line( 'cpp_result_copy( pack_data, &'. $len_var .', sizeof( '. $len_var .' ) );', $rank );
	if ( $rank > 1 )
	{
		$list_type = $GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ];
		$str .= tool_line( $list_type .' '. $parent_var .' = '. $list_type .'( '. $len_var .' );', $rank );
	}
	else
	{
		$str .= tool_line( $parent_var .'.resize( '. $len_var .' );', $rank );
	}
	if ( !isset( $GLOBALS[ 'list_for_var_parse' ][ $for_var ] ) )
	{
		$str .= tool_line( 'int '. $for_var .';', $rank );
		$GLOBALS[ 'list_for_var_parse' ][ $for_var ] = true;
	}
	$str .= tool_line( 'for( '. $for_var .' = 0; '. $for_var .' < '. $len_var .'; ++'. $for_var .' )', $rank );
	$str .= tool_line( '{', $rank );
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$class_name = $GLOBALS[ 'all_type_arr' ][ 'class_'. $list_rs[ 'sub_id' ] ];
			$str .= tool_line( $class_name .' '. $value_var .';', $rank + 1 );
			$str .= tool_line( $value_var .'.read( pack_data );', $rank + 1 );
		break;
		case 'byte':
			$str .= tool_line( 'proto_bin_t '. $value_var .';' );
			$str .= tool_line( 'cpp_result_copy( pack_data, &'. $value_var .'.len, sizeof( bytes_len_t ) );', $rank + 1 );
			$str .= tool_line( $value_var .'.bytes = &pack_data->data[ pack_data->pos ];', $rank + 1 );
			$str .= tool_line( 'cpp_add_pack_data_len( pack_data, '. $value_var .'.len );', $rank + 1 );
		break;
		case 'list':
			$str .= tool_cpp_protocol_list_loop_parse( $list_rs[ 'sub_id' ], $value_var, $rank + 1, $list_name );
		break;
		case 'char':
			$str .= tool_line( 'char *'. $value_var .' = &pack_data->data[ pack_data->pos ];', $rank + 1 );
			$str .= tool_line( 'cpp_add_pack_data_len( pack_data, '. $list_rs[ 'char_len' ] .' );', $rank + 1 );
		break;
		case 'varchar':
			$len_var = 'str_len_'. $rank;
			$pos_var = 'old_pos_'. $rank;
			$str .= tool_line( 'cpp_result_copy( pack_data, &'. $len_var .', sizeof( '. $len_var .' ) );', $rank + 1 );
			$str .= tool_line( $pos_var .' = pack_data->len;', $rank + 1 );
			$str .= tool_line( 'cpp_add_pack_data_len( pack_data, '. $pos_var .' );', $rank + 1 );
			$str .= tool_line( 'string '. $value_var .' = string( &pack_data->data[ '. $pos_var .' ], '. $len_var .' );', $rank + 1 );
		break;
		default:
			$str .= tool_line( $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ] .' '. $value_var .';', $rank + 1 );
			$str .= tool_line( 'cpp_result_copy( pack_data, &'. $value_var .', sizeof( '. $value_var .' ) );', $rank + 1 );
		break;
	}
	$str .= tool_line( $parent_var .'[ '. $for_var .' ] = '. $value_var .';', $rank + 1 );
	$str .= tool_line( '}', $rank );
	return $str;
}