<?php
/**
 * 拷贝两个基类文件
 */
function tool_protocol_as_interface( $base_path )
{
	$name = 'IRequest.as';
	$code = file_get_contents( ROOT_PATH .'tool/protocol/as3/'. $name );
	file_put_contents( $base_path . $name, $code );
	$name = 'IResponse.as';
	$code = file_get_contents( ROOT_PATH .'tool/protocol/as3/'. $name );
	file_put_contents( $base_path . $name, $code );
	$reg_str = "package com.yile.tkd.protocol.bin\n";
	$reg_str .= "{\n";
	$import_arr = array();
	$code_arr = array();
	$max_id = 0;
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		if ( $rs[ 'proto_type' ] & 1 )
		{
			continue;
		}
		if ( $rs[ 'struct_id' ] > $max_id )
		{
			$max_id = $rs[ 'struct_id' ];
		}
		$class_name = tool_protocol_as_struct_name( $rs, 'Out' );
		$import_arr[] = "\timport com.yile.tkd.protocol.bin.". $rs[ 'module' ] .".". $class_name .";\n";
		$code_arr[] = "\t\t\t_factory.addOut( new ". $class_name ."(), ". $rs[ 'struct_id' ] ." );\n";
	}
	$reg_str .= join( '', $import_arr );
	$reg_str .= "\tpublic class ProtocolRegister\n";
	$reg_str .= "\t{\n";
	$reg_str .= "\t\tprivate var _factory:OutFactory = OutFactory.getInstance();\n";
	$reg_str .= "\t\tpublic function ProtocolRegister():void\n";
	$reg_str .= "\t\t{\n";
	$reg_str .= join( '', $code_arr );
	$reg_str .= "\t\t}\n";
	$reg_str .= "\t}\n";
	$reg_str .= "}";
	$name = 'ProtocolRegister.as';
	file_put_contents( $base_path . $name,  $reg_str );
	$name = "OutFactory.as";
	$max_id += 1;
	$code = file_get_contents( ROOT_PATH .'tool/protocol/as3/'. $name );
	$code = str_replace( "__MAX_PROTOCOL_ID__", $max_id, $code );
	file_put_contents( $base_path . $name, $code );
}

/**
 * 生成客户端AS3协议包类
 */
function tool_protocol_as_bin( $base_path )
{
	tool_protocol_as_common( 'com.yile.tkd.protocol.bin', true );
	//所有的协议
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$code = tool_as_protocol_struct_code( $pid, $rs, $items );
		$GLOBALS[ 'all_as_file' ][ $rs[ 'name' ] ] = $code;
		$dir = $base_path . $rs[ 'module' ];
		if ( !is_dir( $dir ) )
		{
			mkdir( $dir );
		}
		$class_fix = 1 == $rs[ 'proto_type' ] ? 'In' : 'Out';
		$file_name = tool_protocol_as_struct_name( $rs, $class_fix );
		$file = $dir .'/'. $file_name .'.as';
		file_put_contents( $file, $code );
		echo '生成文件:'. $file, "\n";
	}
}

/**
 * as转二进制协议
 */
function tool_as_protocol_struct_code( $pid, $rs, $items )
{
	$str = "package ". $GLOBALS[ 'main_pack' ] .".". $rs[ 'module' ] ."\n";
	$str .= "{\n";
	$extend_arr = array();
	if ( !$rs[ 'is_sub' ] )
	{
		if ( $rs[ 'proto_type' ] & 1 )
		{
			$extend_arr[] = 'IRequest';
			$str .= "\timport com.yile.tkd.protocol.bin.IRequest;\n";
		}
		if ( $rs[ 'proto_type' ] & 2 )
		{
			$extend_arr[] = 'IResponse';
			$str .= "\timport com.yile.tkd.protocol.bin.IResponse;\n";
		}
	}
	$str .= "\timport flash.utils.ByteArray;\n";
	if ( !empty( $GLOBALS[ 'as_file_import' ][ $pid ] ) )
	{
		$import_arr = array_keys( $GLOBALS[ 'as_file_import' ][ $pid ] );
		$str .= "\t". join( "\t", $import_arr );
	}
	$class_fix = 1 == $rs[ 'proto_type' ] ? 'In' : 'Out';
	$str .= "\tpublic class ". tool_protocol_as_struct_name( $rs, $class_fix );
	if ( 0 == $rs[ 'is_sub' ] )
	{
		$str .= " implements ". join( ',', $extend_arr );
	}
	$str .= "\n\t{\n";
	if ( !empty( $GLOBALS[ 'as_property_code' ][ $pid ] ) )
	{
		$str .= join( ";\n", $GLOBALS[ 'as_property_code' ][ $pid ] ) .";\n";
	}
	if ( !$rs[ 'is_sub' ] )
	{
		$str .= "\t\tprivate const _packtype:int = ". $rs[ 'struct_id' ] .";\n";
	}
	$GLOBALS[ 'list_for_var' ] = array();
	$GLOBALS[ 'list_for_var_parse' ] = array();
	if ( $rs[ 'proto_type' ] & 1 )
	{
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$str .= "\t\tpublic function write( buff:ByteArray ):void\n";
		$str .= "\t\t{\n";
		$tab_str = "\t\t\t";
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
					$str .= $tab_str ."Game.tool.writeString( buff, this.". $item_rs[ 'item_name' ] .", ". $item_rs[ 'char_len' ] ." );\n";
				break;
				case 'byte':	//字节流
					$read_byte_after[] = $item_rs[ 'item_name' ];
				break;
				default:
					$func_str = tool_as_protocol_byte_arr_func( $item_rs[ 'type' ] );
					$str .= $tab_str ."buff.". $func_str ."( this.". $item_rs[ 'item_name' ] ." );\n";
				break;
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			foreach ( $read_string_after as $str_name )
			{
				$str .= tool_as_protocol_write_string( $pid, "this.". $str_name, $tab_str );
			}
		}
		//字节流
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $key_name )
			{
				$str .= $tab_str."Game.tool.writeObject( buff, this.". $key_name ." );\n";
			}
		}
		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$str .= $tab_str ."this.". $struct_name .".write( buff );\n";
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_as_protocol_list_loop( $list_id, "this.". $list_name, "\t\t\t", 0, $pid, $list_name );
			}
		}
		$str .= "\t\t}\n";
		$str .= tool_as_protocol_print( $items, $rs );
	}
	if ( $rs[ 'proto_type' ] & 2 )
	{
		$read_struct_after = array( );
		$read_list_after = array( );
		$read_string_after = array();
		$read_byte_after = array();
		$str .= "\t\tpublic function read( buff:ByteArray ):void\n";
		$str .= "\t\t{\n";
		$tab_str = "\t\t\t";
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
				case 'byte':
					$read_byte_after[] = $item_rs[ 'item_name' ];
				break;
				case 'char':
					$str .= $tab_str ."this.". $item_rs[ 'item_name' ] ." = buff.readUTFBytes( ". $item_rs[ 'char_len' ] ." );\n";
				break;
				case 'varchar':
					$read_string_after[] = $item_rs[ 'item_name' ];
				break;
				default:
					$func_str = tool_as_protocol_byte_arr_read_func( $item_rs[ 'type' ] );
					$str .= $tab_str ."this.". $item_rs[ 'item_name' ] ." = buff.". $func_str ."();\n";
				break;
			}
		}
		//字符串处理
		if ( !empty( $read_string_after ) )
		{
			foreach ( $read_string_after as $str_name )
			{
				$str .= $tab_str ."this.". $str_name ." = buff.readUTF();\n";
			}
		}
		//字节流处理
		if ( !empty( $read_byte_after ) )
		{
			foreach ( $read_byte_after as $byte_name )
			{
				$str .= $tab_str ."this.". $byte_name ." = Game.tool.readBytesArray( buff );\n";
			}
		}
		//其它struct处理
		if ( !empty( $read_struct_after ) )
		{
			foreach ( $read_struct_after as $struct_name => $struct_id )
			{
				$str .= $tab_str ."this.". $struct_name .".read( buff );\n";
			}
		}
		//list处理
		if ( !empty( $read_list_after ) )
		{
			foreach ( $read_list_after as $list_name => $list_id )
			{
				$str .= tool_as_protocol_list_loop_parse( $list_id, "this.". $list_name, "\t\t\t", 0, $list_name );
			}
		}
		$str .= "\t\t}\n";
		if ( !( $rs[ 'proto_type' ] & 1 ) )
		{
			$str .= tool_as_protocol_print( $items, $rs );
		}
	}
	//生成paketype
	if ( !$rs[ 'is_sub' ] )
	{
		if ( 2 == $rs[ 'proto_type' ] )
		{
			$str .= "\t\tpublic function get isWhiteList():Boolean\n";
			$str .= "\t\t{\n";
			$str .= "\t\t\treturn ";
			$str .= isset( $rs[ 'is_write_list' ] ) ? 'true' : 'false';
			$str .= ";\n";
			$str .= "\t\t}\n";
		}
		$str .= "\t\tpublic function get packtype():int\n";
		$str .= "\t\t{\n";
		$str .= "\t\t\treturn _packtype;\n";
		$str .= "\t\t}\n";
	}
	$str .= "\t}\n";
	$str .= "}\n";
	return $str;
}

/**
 * 写字符串的代码
 */
function tool_as_protocol_write_string( $pid, $var, $tab_str )
{
	$str = '';
	$str .= $tab_str ."buff.writeUTF( ". $var ." );\n";
	return $str;
}

/**
 * 循环体代码
 */
function tool_as_protocol_list_loop( $list_id, $parent, $tab_str, $rank, $struct_id, $list_name )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$for_var = "for_". $list_name. $rank;
	$str = $tab_str. "buff.writeShort( ". $parent .".length );\n";
	if ( !isset( $GLOBALS[ 'list_for_var' ][ $for_var ] ) )
	{
		$str .= $tab_str ."var ". $for_var .":". $GLOBALS[ 'all_type_arr' ][ 'list_vector_'. $list_id ] .";\n";
		$GLOBALS[ 'list_for_var' ][ $for_var ] = true;
	}
	$str .= $tab_str . "for each( ". $for_var ." in " . $parent . " )\n";
	$str .= $tab_str . "{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'varchar':
			$str .= tool_as_protocol_write_string( $struct_id, $for_var, $tab_str ."\t" );
		break;
		case 'byte':
			$str .= $tab_str ."\tGame.tool.writeObject( buff, ". $for_var ." );\n";
		break;
		case 'char':
			$str .= $tab_str ."\tGame.tool.writeString( buff, ". $for_var .", ". $list_rs[ 'char_len' ] ." );\n";
		break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$str .= $tab_str."\t". $for_var .".write( buff );\n";
		break;
		case 'list':
			$str .= tool_as_protocol_list_loop( $list_rs[ 'sub_id' ], $for_var, $tab_str ."\t", $rank + 1, $struct_id, $list_name );
		break;
		default:
			$func_str = tool_as_protocol_byte_arr_func( $list_rs[ 'type' ] );
			$str .= $tab_str ."\tbuff.". $func_str ."( ". $for_var ." );\n";
		break;
	}
	$str .= $tab_str . "}\n";
	return $str;
}

/**
 * 解析list
 */
function tool_as_protocol_list_loop_parse( $list_id, $parent_var, $tab_str, $rank, $list_name )
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
	$str .= $tab_str . $len_var ." = buff.readUnsignedShort();\n";
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
			$str .= $tab_str ."\t". $value_var .".read( buff );\n";
		break;
		case 'byte':
			$str .= $tab_str ."\tvar ". $value_var .":ByteArray = Game.tool.readBytesArray( buff );\n";
		break;
		case 'list':
			$str .= tool_as_protocol_list_loop_parse( $list_rs[ 'sub_id' ], $value_var, $tab_str ."\t", $rank + 1, $list_name );
		break;
		case 'char':
			$str .= $tab_str . "\tvar ". $value_var .":String = buff.readUTFBytes(". $list_rs[ 'char_len' ] .");\n";
		break;
		default:
			$func_name = tool_as_protocol_byte_arr_read_func( $list_rs[ 'type' ] );
			$str .= $tab_str . "\tvar ". $value_var .":". $GLOBALS[ 'all_type_arr' ][ $list_rs[ 'type' ] ] ." = buff.". $func_name ."();\n";
		break;
	}
	$str .= $tab_str . "\t". $parent_var ."[ ". $for_var ." ] = ". $value_var .";\n";
	$str .= $tab_str ."}\n";
	return $str;
}

/**
 * funcdoc
 */
function tool_as_protocol_print( $items, $rs )
{
	$tab_str = "\t\t\t";
	$str = '';
	$str .= "\t\tCONFIG::Debug\n";
	$str .= "\t\t{\n";
	$str .= "\t\tpublic function to_object( obj:Object ):void\n";
	$str .= "\t\t{\n";
	foreach ( $items as $item_rs )
	{
		$item_name = $item_rs[ 'item_name' ];
		switch ( $item_rs[ 'type' ] )
		{
			case 'struct':
				$sub_struct = 'sub_'. $item_name;
				$str .= $tab_str ."var ". $sub_struct .":Object = {};\n";
				$str .= $tab_str ."this.". $item_name .".to_object( ". $sub_struct ." );\n";
				$str .= $tab_str ."obj[ '". $item_name ."' ] = ". $sub_struct .";\n";
			break;
			case 'list':
				$str .= tool_as_protocol_print_list( $item_rs[ 'sub_id' ], $item_name, "obj[ '". $item_name ."' ]", 'this.'. $item_name, 0 );
			break;
			case 'byte':
				$str .= $tab_str ."obj[ '". $item_name ."' ] = '[binary]';\n";
			break;
			default:
				$str .= $tab_str ."obj[ '". $item_name ."' ] = this.". $item_name .";\n";
			break;
		}
	}
	$str .= "\t\t}\n";
	$str .= "\t\tpublic function get_pack_desc():String\n";
	$str .= "\t\t{\n";
	$str .= "\t\t\treturn '". $rs[ 'desc' ] ."';\n";
	$str .= "\t\t}\n";
	$str .= "\t\t}\n";
	return $str;
}

/**
 * list打印
 */
function tool_as_protocol_print_list( $list_id, $item_name, $p_var, $value_var, $rank )
{
	$tab_str = str_repeat( "\t", $rank + 3 );
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$var_i = 'for_'. $item_name .'_'. $rank;
	$str = '';
	$arr_var = 'arr_'. $item_name .'_'. $rank;
	$str .= $tab_str .'var '. $arr_var .":Array = [];\n";
	$len_arr = 'len_'. $item_name .'_'. $rank;
	$str .= $tab_str . 'var '. $len_arr . ":int = ". $value_var .".length;\n";
	$str .= $tab_str ."for( var ". $var_i .":int = 0; ". $var_i ." < ". $len_arr ."; ++". $var_i ." )\n";
	$str .= $tab_str ."{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'struct':
			$obj_name = 'obj_'. $item_name .'_'. $rank;
			$str .= $tab_str ."\tvar ". $obj_name .":Object = {};\n";
			$str .= $tab_str ."\t". $value_var . '[ '. $var_i ." ].to_object( ". $obj_name ." );\n";
			$str .= $tab_str ."\t". $arr_var .'.push( '. $obj_name ." );\n";
		break;
		case 'list':
			$str .= tool_as_protocol_print_list( $list_rs[ 'sub_id' ], $item_name, $arr_var.'[ '. $var_i .' ]', $value_var .'[ '. $var_i .' ]', $rank + 1 );
		break;
		case 'byte':
			$str .= $tab_str ."\t". $arr_var .".push( '[binary]' );\n";
		break;
		default:
			$str .= $tab_str ."\t". $arr_var .'.push( '. $value_var .'[ '. $var_i ." ] );\n";
		break;
	}
	$str .= $tab_str ."}\n";
	$str .= $tab_str . $p_var . " = ". $arr_var .";\n";
	return $str;
}

/**
 * 返回函数名
 */
function tool_as_protocol_byte_arr_func( $type )
{
	switch ( $type )
	{
		case 'bigint':
			$func_str = 'writeDouble';
		break;
		case 'tinyint':
		case 'unsigned tinyint':
			$func_str = 'writeByte';
		break;
		case 'smallint':
		case 'unsigned smallint':
			$func_str = 'writeShort';
		break;
		case 'unsigned int';
			$func_str = 'writeUnsignedInt';
		break;
		default:
			$func_str = 'writeInt';
		break;
	}
	return $func_str;
}

/**
 * 返回函数名
 */
function tool_as_protocol_byte_arr_read_func( $type )
{
	switch ( $type )
	{
		case 'bigint':
			$func_str = 'readDouble';
		break;
		case 'tinyint':
			$func_str = 'readByte';
		break;
		case 'unsigned tinyint':
			$func_str = 'readUnsignedByte';
		break;
		case 'smallint':
			$func_str = 'readByte';
		break;
		case 'unsigned smallint':
			$func_str = 'readUnsignedShort';
		break;
		case 'unsigned int';
			$func_str = 'readUnsignedInt';
		break;
		case 'varchar':
			$func_str = 'readUTF';
		break;
		default:
			$func_str = 'readInt';
		break;
	}
	return $func_str;
}