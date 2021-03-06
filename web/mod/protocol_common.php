<?php
/**
 * 生成name_id的map
 */
function tool_so_id_name_map( $type = 3 )
{
	$id_map_list = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $info )
	{
		if ( $info[ 'is_sub' ] )
		{
			continue;
		}
		if ( !( $info[ 'proto_type' ] & $type ) )
		{
			continue;
		}
		$id_map_list[ $info[ 'struct_id' ] ] = $pid;
	}
	return $id_map_list;
}

/**
 * 生成id=>name的map
 */
function tool_so_name_id_map( $type = 3 )
{
	$name_id_map = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $info )
	{
		if ( $info[ 'is_sub' ] )
		{
			continue;
		}
		if ( !( $info[ 'proto_type' ] & $type ) )
		{
			continue;
		}
		$name_id_map[ $pid ] = (int)$info[ 'struct_id' ];
	}
	return $name_id_map;
}

/**
 * 通用函数
 */
function tool_bin_protocol_common( $struct_prefix = 'proto' )
{
	$GLOBALS[ 'all_type_arr' ] = array(
		'tinyint'				=> 'int8_t',
		'unsigned tinyint'		=> 'uint8_t',
		'smallint'				=> 'int16_t',
		'unsigned smallint'		=> 'uint16_t',
		'int'					=> 'int32_t',
		'unsigned int'			=> 'uint32_t',
		'bigint'				=> 'int64_t',
		'varchar'				=> 'char*',
		'char'					=> 'char',
		'byte'					=> 'proto_bin_t*',
	);
	$GLOBALS[ 'struct_define' ] = array();
	$GLOBALS[ 'typedef_all' ] = array();
	$GLOBALS[ 'typedef_detail' ] = array();
	$GLOBALS[ 'all_list_define' ] = array();
	$GLOBALS[ 'list_type_def' ] = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		if ( empty( $items ) )
		{
			continue;
		}
		$name = $rs[ 'name' ];
		$struct_name = $struct_prefix ."_". $name ."_t";
		$GLOBALS[ 'all_type_arr' ][ 'struct_'. $pid ] = $struct_name ."*";
		$GLOBALS[ 'typedef_all' ][] = "\ntypedef struct ". $struct_name ." ". $struct_name .";\n";
	}

	foreach ( $GLOBALS[ 'all_list' ] as $list_id => $rs )
	{
		$name = $struct_prefix .'_list_'. tool_protocol_list_name( $rs ) .'_t';
		if ( !isset( $GLOBALS[ 'all_list_define' ][ $name ] ) )
		{
			$GLOBALS[ 'all_list_define' ][ $name ] = $list_id;
		}
		$GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] = $name .'*';
	}
	//list的define
	foreach ( $GLOBALS[ 'all_list_define' ] as $name => $id )
	{
		$def_name = $GLOBALS[ 'all_type_arr' ][ $GLOBALS[ 'list_type_def' ][ $id ] ];
		$GLOBALS[ 'typedef_detail' ][] = tool_protocol_list_def( $name, $def_name );
	}
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		if ( empty( $items ) )
		{
			continue;
		}
		$str = tool_protocol_def_struct( $pid, $items, $rs, $struct_prefix );
		$GLOBALS[ 'typedef_detail' ][] = $str;
	}
}


/**
 * list的名字
 */
function tool_protocol_list_name( $rs )
{
	$id = $rs[ 'name' ];
	switch ( $rs[ 'type' ] )
	{
		case 'struct':
			$GLOBALS[ 'list_type_def' ][ $id ] = 'struct_'. $rs[ 'sub_id' ];
			$st_info = $GLOBALS[ 'all_protocol' ][ $rs[ 'sub_id' ] ];
			return $st_info[ 'name' ];
		break;
		case 'list':
			$GLOBALS[ 'list_type_def' ][ $id ] = 'list_'. $rs[ 'sub_id' ];
			return 'list_'.tool_protocol_list_name( $GLOBALS[ 'all_list' ][ $rs[ 'sub_id' ] ] );
		break;
		default:
			$GLOBALS[ 'list_type_def' ][ $id ] = $rs[ 'type' ];
			return str_replace( ' ', '', $rs[ 'type' ] );
		break;
	}
}

/**
 * 生成一个list的def
 */
function tool_protocol_list_def( $def_name, $sub_name )
{
	$str = '';
	$sub_name = str_replace( '*', '', $sub_name );
	$GLOBALS[ 'typedef_all' ][] = "\ntypedef struct ". $def_name ." ". $def_name .";\n";
	$str .= "struct ". $def_name ."{\n";
	$str .= "\tuint16_t											len;\n";
	$len = strlen( $sub_name );
	$str .="\t". $sub_name;
	for ( $i = $len; $i < 52; $i += 4 )
	{
		$str .= "\t";
	}
	if ( 'char' == $sub_name )
	{
		$str .= "*";
	}
	$str .= "*item;\n";
	$str .="};\n";
	return $str;
}
/**
 * 生成一个struct是否固定长度
 */
function tool_protocol_is_struct_fix()
{
	$GLOBALS[ 'is_sizeof_struct_fix' ] = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $strct_rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$is_fix = true;
		foreach ( $items as $rs )
		{
			switch ( $rs[ 'type' ] )
			{
				case 'struct':
					$is_fix = false;
				break;
				case 'list':
					$is_fix = false;
				break;
				case 'byte':
					$is_fix = false;
				break;
				case 'varchar':
					$is_fix = false;
				break;
			}
		}
		$GLOBALS[ 'is_sizeof_struct_fix' ][ $pid ] = $is_fix;
	}
}

/**
 * 生成一个struct的定义
 */
function tool_protocol_def_struct( $pid, $items, $struct_info, $pre_fix = 'proto' )
{
	$name = $struct_info[ 'name' ];
	$str = "//". $struct_info[ 'desc' ] ."\n";
	$str .= "struct ". $pre_fix ."_". $name ."_t{\n";
	foreach ( $items as $rs )
	{
		switch ( $rs[ 'type' ] )
		{
			case 'struct':
				$type = 'struct_'. $rs[ 'sub_id' ];
			break;
			case 'list':
				$type = 'list_'. $rs[ 'sub_id' ];
			break;
			case 'byte':
				$type = 'byte';
			break;
			case 'varchar':
				$type = $rs[ 'type' ];
			break;
			default:
				$type = $rs[ 'type' ];
			break;
		}
		$name = $GLOBALS[ 'all_type_arr' ][ $type ];
		$def_name = $rs[ 'item_name' ];
		if ( 'char' == $rs[ 'type' ] )
		{
			$def_name .= '[ '. $rs[ 'char_len' ] .' ]';
		}
		$str .= "\t". $name;
		for ( $i = strlen( $name ); $i < 52; $i += 4 )
		{
			$str .= "\t";
		}
		$str .= $def_name. ";";
		$len = strlen( $def_name ) + 1;
		for ( $i = $len; $i < 20; $i += 4 )
		{
			$str .= "\t";
		}
		$str .= "//". $rs[ 'desc' ] ."\n";
	}
	$str .="};\n";
	return $str;
}
/**
 * 字符长度
 */
function tool_protocol_char_len( $str )
{
	$str_len = strlen( $str );
	$re = 0;
	$tmpchar = '';
	for ( $i = 0; $i < $str_len; )
	{
		$c = $str[ $i ];
		$oc = ord( $c );
		if ( $oc >= 0xFc && $oc <= 0xFD )
		{
			$char_len = 6;
			$dis_len = 2;
		}
		else if ( $oc > 0xF8 )
		{
			$char_len = 5;
			$dis_len = 2;
		}
		else if ( $oc > 0xF0 )
		{
			$char_len = 4;
			$dis_len = 2;
		}
		else if ( $oc > 0xE0 )
		{
			$char_len = 3;
			$dis_len = 2;
		}
		else if ( $oc > 0xC0 )
		{
			$char_len = 2;
			$dis_len = 2;
		}
		else
		{
			$char_len = 1;
			$dis_len = 1;
		}
		$i += $char_len;
		$re += $dis_len;
	}
	return $re;
}
/**
 * 补全tab数量
 */
function tool_tab_line ( $str, $bef_tab = 0, $max_len = 100 )
{
	$str_len = strlen( $str );
	if ( $bef_tab > 0 )
	{
		$str = str_repeat( "\t", $bef_tab ) . $str;
		$str_len += $bef_tab * 4;
	}
	$tab_num = ceil( ( $max_len - $str_len ) / 4 );
	if ( $tab_num > 0 )
	{
		$str .= str_repeat( "\t", $tab_num );
	}
	return $str ."\\\n";
}

/**
 * 代码缩进包装
 */
function tool_line( $str, $bef_tab = 0 )
{
	if ( $bef_tab > 0 )
	{
		$str = str_repeat( "\t", $bef_tab ) . $str;
	}
	return $str . "\n";
}

/**
 * 生成AS文件的通用部分
 * @param bool $is_bin 是否是二进制
 */
function tool_protocol_as_common( $pack_path, $is_bin = true, $as_class_affix = null )
{
	$GLOBALS[ 'all_type_arr' ] = array(
		'tinyint'				=> 'int',
		'unsigned tinyint'		=> 'int',
		'smallint'				=> 'int',
		'unsigned smallint'		=> 'int',
		'int'					=> 'int',
		'unsigned int'			=> 'uint',
		'bigint'				=> 'Number',
		'varchar'				=> 'String',
		'char'					=> 'String',
		'byte'					=> 'ByteArray',
	);
	$GLOBALS[ 'is_var_dim' ] = array();	//用于私有变量是否定义
	$GLOBALS[ 'main_pack' ] = $pack_path;
	$GLOBALS[ 'all_as_file' ] = array();
	$GLOBALS[ 'as_property_code' ] = array();
	$GLOBALS[ 'as_file_import' ] = array();
	if ( empty( $as_class_affix ) )
	{
		$GLOBALS[ 'as_class_affix' ] = array( 1 => 'In', 2 => 'Out' );
	}
	else
	{
		$GLOBALS[ 'as_class_affix' ] = $as_class_affix;
	}
	$GLOBALS[ 'as_class_affix' ][ 3 ] = 'Ds';
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$class_fix = $GLOBALS[ 'as_class_affix' ][ $rs[ 'proto_type' ] ];
		$GLOBALS[ 'all_type_arr' ][ 'struct_'. $pid ] = tool_protocol_as_struct_name( $rs, $class_fix );
		$GLOBALS[ 'as_file_import' ][ $pid ] = array();
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$property_list = array();
		foreach ( $items as $item_rs )
		{
			$property = "\t\tpublic var " . $item_rs[ 'item_name' ] . ':';
			switch ( $item_rs[ 'type' ] )
			{
				case 'varchar':
					$property .= 'String';
				break;
				case 'char':
					$property .= 'String';
					if ( $is_bin )
					{
						$import_str = "import com.yile.tkd.core.Game;\n";
						$GLOBALS[ 'as_file_import' ][ $pid ][ $import_str ] = true;
					}
				break;
				case 'bigint':
					$property .= 'Number';
				break;
				case 'byte':
					$property .= 'ByteArray';
					if ( $is_bin )
					{
						$import_str = "import com.yile.tkd.core.Game;\n";
						$GLOBALS[ 'as_file_import' ][ $pid ][ $import_str ] = true;
					}
				break;
				case 'unsigned int':
					$property .= 'uint';
				break;
				case 'struct';
					$sub_struct = $GLOBALS[ 'all_protocol' ][ $item_rs[ 'sub_id' ] ];
					$sub_class = tool_protocol_as_struct_name( $sub_struct );
					$property .= $sub_class .' = new '. $sub_class .'()';
					$import_str = tool_protocol_as_import( $sub_struct, $pack_path );
					$GLOBALS[ 'all_type_arr' ][ 'struct_'. $item_rs[ 'sub_id' ] ] = $sub_class;
					$GLOBALS[ 'as_file_import' ][ $pid ][ $import_str ] = true;
				break;
				case 'list':
					$property .= tool_protocol_as_vector( $item_rs[ 'sub_id' ], $GLOBALS[ 'as_file_import' ][ $pid ], $pack_path, $class_fix );
				break;
				default:
					$property .= 'int';
				break;
			}
			$property_list[] = $property;
		}
		$GLOBALS[ 'as_property_code' ][ $pid ] = $property_list;
	}
}

/**
 * as数组格式定义
 */
function tool_protocol_as_vector( $list_id, &$imports, $package_path = 'com.yile.tkd.protocol.amf', $class_fix = '_DS', $call_type = '' )
{
	$data = $GLOBALS[ 'all_list' ][ $list_id ];
	$begstr = 'Vector.<';
	switch ( $data[ 'type' ] )
	{
		case 'bigint':  //有符号64位数字
			$str = 'Number';
		break;
		case 'varchar':  //字符串
			$str = 'String';
		break;
		case 'char':
			$import_str = "import com.yile.tkd.core.Game;\n";
			$imports[ $import_str ] = true;
			$str = 'String';
		break;
		case 'byte':
			$import_str = "import com.yile.tkd.core.Game;\n";
			$imports[ $import_str ] = true;
			$str = 'Object';
		break;
		case 'unsigned int':  //无符号32位数字
			$str = 'uint';
		break;
		case 'list':
			$str = tool_protocol_as_vector( $data[ 'sub_id' ], $imports, $package_path, $class_fix, $call_type );
		break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $data[ 'sub_id' ] ];
			$sub_class = tool_protocol_as_struct_name( $struct );
			$import_str = tool_protocol_as_import( $struct, $package_path );
			if( $call_type == 'amf' )
			{
				$imports[ $sub_class ] = "\t".$import_str;
			}
			else
			{
				$imports[ $import_str ] = true;
			}

			$str = $sub_class;
		break;
		default:
			$str = 'int';
		break;
	}
	$endstr = '>';
	$def_name = $begstr . $str .$endstr;
	$GLOBALS[ 'all_type_arr' ][ 'list_'. $list_id ] = $def_name;
	$GLOBALS[ 'all_type_arr' ][ 'list_vector_'. $list_id ] = $str;
	return $def_name;
}

/**
 * struct的定义
 */
function tool_protocol_as_struct_name( $rs, $class_fix = '' )
{
	$name = str_to_camel( $rs[ 'name' ] );
	if( !$rs['is_sub'] )
	{
		$name .= $class_fix;
	}
	else
	{
		$name .= '_DS';
	}
	return $name;
}

/**
 * as import
 */
function tool_protocol_as_import( $rs, $package_path )
{
	$import_str = "import " . $package_path . '.';
	$name = tool_protocol_as_struct_name( $rs, '' );
	if ( !empty( $rs[ 'module' ] ) )
	{
		$import_str .= lcfirst( $rs[ 'module' ] ) . '.';
	}
	$import_str .= $name . ";\n";
	return $import_str;
}

/**
 * 给AS3代码骆驼命名
 * @param string $str
 * @return string
 */
function str_to_camel( $str )
{
	$arr = explode( '_', $str );
	foreach( $arr as & $value )
	{
		$value = ucfirst( $value );
	}
	return join( '', $arr );
}