<?php
$GLOBALS['AS_ROOT_PATH'] = '/data/wwwroot/flash_protocol';
//$GLOBALS['AS_ROOT_PATH'] = ROOT_PATH . 'protocol/as3';

/**
 * 生成协议PHP文件
 */
function tool_protocol( $file_name )
{
	$php_doc = array( );
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => & $protocol )
	{
		$protocol[ 'name' ] = lcfirst( $protocol[ 'name' ] );
		$struct_name = $protocol[ 'name' ];
		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		$func_str = "\t\$out_data = array();\n";
		foreach ( $items as $rs )
		{
			switch ( $rs[ 'type' ] )
			{
				case 'byte'://复杂数据
					$func_str .= "\t\$tmp = is_array( \$data_arr[ '{$rs[ 'item_name' ]}' ] ) ? amf_encode( \$data_arr[ '{$rs[ 'item_name' ]}' ], 1|2 ) : \$data_arr[ '{$rs[ 'item_name' ]}' ];\n";
					$func_str .= "\t\$out_data[] = base64_encode( \$tmp );\n";
					break;
				case 'varchar':
				case 'char':
					$func_str .= "\t\$out_data[] = \$data_arr[ '" . $rs[ 'item_name' ] . "' ];\n";
					break;
				case 'list':	//数组
					$func_str .= "\t";
					$func_str .= tool_protocol_read_list( $rs[ 'sub_id' ], "out_data", "data_arr[ '" . $rs[ 'item_name' ] . "' ]" );
					break;
				case 'struct':
					$func_str .= "\t";
					$struct = $GLOBALS[ 'all_protocol' ][ $rs[ 'sub_id' ] ];
					$func_str .= "\$out_data[] = prot_" . $struct[ 'name' ] . "_out( \$data_arr[ '" . $rs[ 'item_name' ] . "' ] );\n";
					break;
				default:
					$func_str .= "\t\$out_data[] = (int)\$data_arr[ '" . $rs[ 'item_name' ] . "' ];\n";
					break;
			}
		}
		$func_str .= "\treturn \$out_data;\n";
		$func_str .= "}\n\n";
		$head_str = "/**\n";
		$head_str .= " * " . str_replace( '*', '', $protocol[ 'desc' ] ) . "\n";
		$head_str .= " */\n";
		$head_str .= "function prot_" . $struct_name . "_out( \$data_arr )\n{\n";
		$func_str = $head_str . $func_str;
		$php_doc[] = $func_str;
	}
	//写文件
	$file_str = "<?php\n" .join( '', $php_doc ) ."\n?>";
	file_put_contents( $file_name, $file_str );
	echo "生成协议文件:" . $file_name ."\n" ;
}

/**
 * 生成name_id的map
 */
function tool_so_id_name_map( $type = 2 )
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
	return var_export( $id_map_list, true );
}

/**
 * 生成id=>name的map
 */
function tool_so_name_id_map( $type = 2 )
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
	return var_export( $name_id_map, true );
}

/**
 * 读数组
 */
function tool_protocol_read_list( $list_id, $parent, $data, $rank = 1 )
{
	$list_rs = $GLOBALS[ 'all_list' ][ $list_id ];
	$sub_tab = '';
	for ( $i = 0; $i < $rank; ++$i )
	{
		$sub_tab .= "\t";
	}
	$re_str = "\$list_re" . $rank . " = array();\n";
	$re_str .= $sub_tab . "if( empty( \${$data} ) )\n";
	$re_str .= $sub_tab . "{\n";
	$re_str .= $sub_tab . "\t\${$data} = array();\n";
	$re_str .= $sub_tab . "}\n";

	$re_str .= $sub_tab . "foreach( \$" . $data . " as \$sub_dat" . $rank . " )\n";
	$re_str .= $sub_tab . "{\n";
	switch ( $list_rs[ 'type' ] )
	{
		case 'varchar':case 'char':  //字符串
			$re_str .= $sub_tab . "\t\$list_re" . $rank . "[] = \$sub_dat" . $rank . ";\n";
			break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $list_rs[ 'sub_id' ] ];
			$re_str .= $sub_tab . "\t\$list_re" . $rank . "[] = prot_" . $struct[ 'name' ] . "_out( \$sub_dat" . $rank . " );\n";
			break;
		case 'list':
			$re_str .= $sub_tab . "\t";
			$re_str .= tool_protocol_read_list( $list_rs[ 'sub_id' ], "list_re" . $rank, "sub_dat" . $rank, $rank + 1 );
			break;
		default:
			$re_str .= $sub_tab . "\t\$list_re" . $rank . "[] = (int)\$sub_dat" . $rank . ";\n";
			break;
	}
	$re_str .= $sub_tab . "}\n";
	$re_str .= $sub_tab . "\$" . $parent . "[] = \$list_re" . $rank . ";\n";
	return $re_str;
}

/**
 * AS目录清空重构
 */
function tool_protocol_amf_dir_reset()
{
	$root_path = $GLOBALS['AS_ROOT_PATH'];
	//删除文件夹内的as文件
	reverse_del_dir( $root_path );
	//根据协议模块分类生成文件夹
	$arr = array();
	$xml_path = ROOT_PATH . 'tool/protocol/xml/as_php_amf';
	$xml_dir = opendir( $xml_path );
	$module = array();
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		$module_name = $rs[ 'module' ];
		if ( isset( $module[ $module_name ] ) )
		{
			continue;
		}
		$module[ $module_name ] = true;
		$module_path = $root_path . '/' . $module_name;
		if ( !is_dir( $module_path ) )
		{
			mkdir( $module_path );
			echo "生成目录文件夹>>>{$module_path}\n";
		}
	}
}

/**
 * 响应协议类
 */
function tool_protocol_amf_s2c()
{
	tool_protocol_xml( ROOT_PATH .'tool/protocol/xml/as_php_amf', 'response' );
	tool_protocol_amf_dir_reset();
	$root_path = $GLOBALS['AS_ROOT_PATH'];
	$com_path = 'com.yile.tkd';
	$ivo_package = "{$com_path}.protocol.amf.IVo";
	$protocol_path = "{$com_path}.protocol.amf";
	$model_path = "{$com_path}.model";

	$protocol_vo_imports = array( );
	$protocol_vo_funcs = array( );

	//生成协议数据包类结构
	$protocols = $GLOBALS[ 'all_protocol' ];
	foreach ( $protocols as $pid => $struct )
	{
		$struct_id = $struct['struct_id'];
		if( 0 != $struct_id && 1 == $struct[ 'proto_type' ] )
		{
			continue;
		}
		$class_name = str_to_camel( $struct[ 'name' ] ) . ( 0 != $struct_id ? '_DataVO' : '_DS' );
		$model_name = ucfirst( $struct[ 'module' ] . 'Model' );
		$struct[ 'module' ] = lcfirst( $struct[ 'module' ] );

		$package = 'package ' . $protocol_path . '.' . $struct[ 'module' ];
		$package_class = $protocol_path . '.' . $struct[ 'module' ] . '.' . $class_name;
		$protocol_data_file = $root_path . '/' . $struct[ 'module' ] . '/' . $class_name . '.as';
		$model_class = $model_path . '.' . $model_name;

		if( 0 != $struct_id )
		{
			$protocol_vo_imports[] = "import {$package_class};\n";
			$protocol_vo_funcs[] = "_factory.addVo( new {$class_name}() );\n";
		}

		$imports = array( "\timport flash.utils.ByteArray;\n" );
		$imports[] = "\timport com.yile.tkd.core.Game;\n";
		$propertys = array( );
		$propertys_init = array( );

		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];

		if( 0 != $struct_id )
		{
			$propertys[] = "\t\tprivate var _protocol_id:int = {$struct[ 'struct_id' ]};\n";
			$propertys[] = "\t\tprivate var _modelClass:String = \"{$model_class}\";\n";
			$property_name = lcfirst( str_to_camel( $struct[ 'name' ] ) );
			$propertys[] = "\t\tprivate var _modelProperty:String = \"{$property_name}\";\n";
			$propertys[] = "\n";
		}

		foreach ( $items as $key => $val )
		{
			$property = "\t\tpublic var " . $val[ 'item_name' ] . ':';

			switch ( $val[ 'type' ] )
			{
				case 'byte'://复杂数据
					$property .= "ByteArray";
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = Game.tool.base64_decode( data[ {$key} ] );\n";
					break;
				case 'tinyint':  //有符号8位数字
				case 'unsigned tinyint':  //无符号8位数字
				case 'smallint':  //有符号16位数字
				case 'unsigned smallint':  //无符号16位数字
				case 'int':   //有符号32位数字
					$property .= "int";
					//属性构造
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = data[{$key}];\n";
					break;
				case 'unsigned int':  //无符号32位数字
					$property .= "uint";
					//属性构造
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = data[{$key}];\n";
					break;
				case 'bigint':  //有符号64位数字
					$property .= "Number";
					//属性构造
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = data[{$key}];\n";
					break;
				case 'varchar':  //字符串
				case 'char':
					$property .= "String";
					//属性构造
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = data[{$key}];\n";
					break;
				case 'list':  //数组
					$property .= tool_protocol_as_vector( $val[ 'sub_id' ], $imports, $protocol_path, '_DS', 'amf' );
					$propertys_init[] = "\n";
					$propertys_init[] = "\t\t\t//数组\n";
					$propertys_init[] = tool_protocol_property_vector_code( $val[ 'sub_id' ], "data[{$key}]", $val[ 'item_name' ], $key );
					$propertys_init[] = "\n";
					break;
				case 'struct':
					$sub_data = $GLOBALS[ 'all_protocol' ][ $val[ 'sub_id' ] ];
					$vo_class = str_to_camel( $sub_data[ 'name' ] ) . '_DS';
					$sub_data[ 'module' ] = lcfirst( $sub_data[ 'module' ] );
					$property .= $vo_class;
					if ( empty( $sub_data[ 'module' ] ) )
					{
						$imports[ $vo_class ] = "\timport " . $protocol_path . '.' . $vo_class . ";\n";
					}
					else
					{
						$sub_data[ 'module' ] = lcfirst( $sub_data[ 'module' ] );
						$imports[ $vo_class ] = "\timport " . $protocol_path . '.' . $sub_data[ 'module' ] . '.' . $vo_class . ";\n";
					}
					//属性构造
					$propertys_init[] = "\t\t\t//数据对象\n";
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]} = new {$vo_class}();\n";
					$propertys_init[] = "\t\t\tthis.{$val[ 'item_name' ]}.init( data[{$key}] );\n";
					break;
			}

			$propertys[] = $property . ";\n";
		}
		//准备拼接AS代码
		$class_content = array( );
		$class_content[] = $package . "\n";
		$class_content[] = "{\n";
		//其他协议数据类
		$class_content[] = join( '', $imports );

		if( 0 != $struct_id )
		{
			$class_content[] = "\timport {$ivo_package};\n";
			$class_content[] = "\n";
			$class_content[] = "\tpublic class {$class_name} implements IVo\n";
		}
		else
		{
			$class_content[] = "\tpublic class {$class_name}\n";
		}

		$class_content[] = "\t{\n";

		//属性
		$class_content[] = join( '', $propertys ) . "\n";

		//构造
		$class_content[] = "\t\tpublic function " . $class_name . "():void{}\n\n";

		//赋值
		$class_content[] = "\t\tpublic function init( data:Array ):void\n";
		$class_content[] = "\t\t{\n";
		$class_content[] = join( '', $propertys_init );//属性赋值
		$class_content[] = "\t\t}\n\n";
		$class_content[] = tool_as_protocol_print( $items, $struct );
		//接口
		if( 0 != $struct_id )
		{
			$class_content[] = "\t\tpublic function get modelClass():String\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn _modelClass;\n";
			$class_content[] = "\t\t}\n\n";

			$class_content[] = "\t\tpublic function get modelProperty():String\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn _modelProperty;\n";
			$class_content[] = "\t\t}\n\n";

			$class_content[] = "\t\tpublic function get pid():int\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn _protocol_id;\n";
			$class_content[] = "\t\t}\n";

			$class_content[] = "\t\tpublic function get isWhiteList():Boolean\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn ";
			$class_content[] = isset( $struct[ 'is_write_list' ] ) ? 'true' : 'false';
			$class_content[] = ";\n";
			$class_content[] = "\t\t}\n";
		}

		$class_content[] = "\t}\n";

		$class_content[] = "}\n";

		file_put_contents( $protocol_data_file, $class_content );
		if( 0 != $struct_id )
		{
			echo "生成响应协议数据类文件++++++> ", $protocol_data_file, "\n";
		}
		else
		{
			echo "生成数据结构> ", $protocol_data_file, "\n";
		}
	}

	//接口基类，注册基类，工厂基类
	$reg_file = $root_path . '/ProtocolRegister.as';
	$rge_file_class = array( );
	$rge_file_class[] = "package {$protocol_path}\n";
	$rge_file_class[] = "{\n";
	$rge_file_class[] = "\t" . join( "\t", $protocol_vo_imports ) . "\n";
	$rge_file_class[] = "\tpublic class ProtocolRegister\n";
	$rge_file_class[] = "\t{\n";
	$rge_file_class[] = "\t\tprivate var _factory:VoFactory = VoFactory.getInstance();\n\n";
	$rge_file_class[] = "\t\tpublic function ProtocolRegister():void\n";
	$rge_file_class[] = "\t\t{\n";
	$rge_file_class[] = "\t\t\t" . join( "\t\t\t", $protocol_vo_funcs );
	$rge_file_class[] = "\t\t}\n";
	$rge_file_class[] = "\t}\n";
	$rge_file_class[] = "}";

	file_put_contents( $reg_file, $rge_file_class );
	echo "\n响应协议注册基类.>>>>>>", $reg_file, "\n\n";
}

/**
 * 发送协议数据类
 */
function tool_protocol_amf_c2s()
{
	tool_protocol_xml( ROOT_PATH .'tool/protocol/xml/as_php_amf', 'request' );
	$root_path = $GLOBALS['AS_ROOT_PATH'];
	$com_path = 'com.yile.tkd';
	$protocol_path = "{$com_path}.protocol.amf";
	$ireq_package = "{$com_path}.protocol.amf.IReq";

	//生成协议数据包类结构
	$protocols = $GLOBALS[ 'all_protocol' ];

	foreach ( $protocols as $pid => $struct )
	{
		$struct_id = $struct['struct_id'];
		if( 0 == $struct_id )
		{
			//continue;
		}



		$struct[ 'module' ] = lcfirst( $struct[ 'module' ] );

		$package = 'package ' . $protocol_path . '.' . $struct[ 'module' ];
		//$class_name = str_to_camel( $struct[ 'name' ] ) . '_Req';
		$class_name = str_to_camel( $struct[ 'name' ] ) . ( 0 != $struct_id ? '_Req' : '_DS' );
		$protocol_data_file = $root_path . '/' . $struct[ 'module' ] . '/' . $class_name . '.as';

		$imports = array( );
		$propertys = array( );
		$propertys_init = array( );

		$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
		if(  0 != $struct_id )
		{
			$propertys[] = "\t\tprivate var _pid:int = {$struct[ 'struct_id' ]};\n";
		}
		$propertys[] = "\n";

		$colls = array();

		foreach ( $items as $key => $val )
		{
			$colls[] = "\"{$val[ 'item_name' ]}\"";

			$property = "\t\tpublic var " . $val[ 'item_name' ] . ':';

			switch ( $val[ 'type' ] )
			{
				case 'byte'://复杂数据
					$property .= "Object";
				break;
				case 'tinyint':  //有符号8位数字
				case 'unsigned tinyint':  //无符号8位数字
				case 'smallint':  //有符号16位数字
				case 'unsigned smallint':  //无符号16位数字
				case 'int':   //有符号32位数字
					$property .= "int";
				break;
				case 'unsigned int':  //无符号32位数字
					$property .= "uint";
				break;
				case 'bigint':  //有符号64位数字
					$property .= "Number";
				break;
				case 'varchar':  //字符串
				case 'char':
					$property .= "String";
				break;
				case 'list':  //数组
					$property .= tool_protocol_as_vector( $val[ 'sub_id' ], $imports, $protocol_path );
				break;
				case 'struct':
					$sub_data = $GLOBALS[ 'all_protocol' ][ $val[ 'sub_id' ] ];
					$sub_data[ 'module' ] = lcfirst( $sub_data[ 'module' ] );
					$ro_class = str_to_camel( $sub_data[ 'name' ] ). '_DS';
					$property .= "{$ro_class} = new {$ro_class}()";
					if ( empty( $sub_data[ 'module' ] ) )
					{
						$imports[ $ro_class ] = "\timport " . $protocol_path . '.' . $ro_class . ";\n";
					}
					else
					{
						$imports[ $ro_class ] = "\timport " . $protocol_path . '.' . $sub_data[ 'module' ] . '.' . $ro_class . ";\n";
					}
				break;
			}

			$propertys[] = $property . ";\n";
		}

		if( 0 != $struct_id )
		{
			if( !empty( $colls ) )
			{
				$propertys[] = "\t\tprivate var _args:Array = [ " . join( ',', $colls ) . " ];\n";
			}
			else
			{
				$propertys[] = "\t\tprivate var _args:Array = [];\n";
			}
		}

		//准备拼接AS代码
		$class_content = array( );
		$class_content[] = $package . "\n";
		$class_content[] = "{\n";
		//其他协议数据类
		$class_content[] = join( '', $imports );

		if( 0 != $struct_id )
		{
			$class_content[] = "\timport {$ireq_package};\n";
			$class_content[] = "\timport {$com_path}.protocol.amf.GetRequest;\n";
			$class_content[] = "\n";
			$class_content[] = "\tpublic class {$class_name} extends GetRequest implements IReq\n";
		}
		else
		{
			$class_content[] = "\n";
			$class_content[] = "\tpublic class {$class_name}\n";
		}


		$class_content[] = "\t{\n";

		//属性
		$class_content[] = join( '', $propertys ) . "\n";

		//构造
		$class_content[] = "\t\tpublic function " . $class_name . "():void{}\n\n";
		if( 0 != $struct_id )
		{
			//接口
			$class_content[] = "\t\tpublic function get pid():int\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn _pid;\n";
			$class_content[] = "\t\t}\n";
			$class_content[] = "\n";

			$class_content[] = "\t\tpublic function get args():Array\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\treturn _args;\n";
			$class_content[] = "\t\t}\n";
			$class_content[] = "\n";

			$class_content[] = "\t\tpublic function send():void\n";
			$class_content[] = "\t\t{\n";
			$class_content[] = "\t\t\tthis.sendToServer(this);\n";
			$class_content[] = "\t\t}\n";
		}
		$class_content[] = tool_as_protocol_print( $items, $struct );
		$class_content[] = "\t}\n";
		$class_content[] = "}\n";
		file_put_contents( $protocol_data_file, $class_content );
		echo "生成发送协议数据类文件++++++> ", $protocol_data_file, "\n";
	}
	echo "\n";
}

/**
 * 基础类
 */
function tool_protocol_amf_base()
{
	$root_path = $GLOBALS['AS_ROOT_PATH'];

	$src_file = ROOT_PATH . 'tool/protocol/as3/IVo.as';
	$target_file = $root_path . '/IVo.as';
	file_put_contents( $target_file, file_get_contents( $src_file ) );
	echo "基类>>>>>>", $target_file, "\n";

	$src_file = ROOT_PATH . 'tool/protocol/as3/VoFactory.as';
	$target_file = $root_path . '/VoFactory.as';
	file_put_contents( $target_file, file_get_contents( $src_file ) );
	echo "基类>>>>>>", $target_file, "\n";

	$src_file = ROOT_PATH . 'tool/protocol/as3/IReq.as';
	$target_file = $root_path . '/IReq.as';
	file_put_contents( $target_file, file_get_contents( $src_file ) );
	echo "基类>>>>>>", $target_file, "\n";
}

/**
 * 数组AS3代码生成
 */
function tool_protocol_property_vector_code( $list_id, $data_name, $property_name, $key )
{
	$code = array( );
	$count = 0;

	$data_val = 'data_' . $key . '_' . $count;
	$vector_val = 'vector_' . $key . '_' . $count;

	$code[] = "\t\t\tvar {$data_val}:Array = " . $data_name . ";\n";
	$code[] = "\t\t\t" . vector_list_parse( $list_id, $data_val, $vector_val, $count, "\t\t\t" );
	$code[] = "\t\t\tthis.{$property_name} = " . $vector_val . ";\n";

	return join( '', $code );
}

/**
 * 数组单元AS3代码解析
 */
function vector_list_parse( $list_id, $data_val, $vector_val, & $count, $p )
{
	$for_code = array( );
	$data = $GLOBALS[ 'all_list' ][ $list_id ];

	if ( $data[ 'type' ] == 'list' )
	{
		for ( $i = 0; $i < $count; $i++ )
		{
			$p .= "\t";
		}
	}
	else if ( 0 != $count )
	{
		$p .= "\t";
	}
	++$count;

	$pos = strrpos( $data_val, '_' );
	$next_data_val = substr( $data_val, 0, $pos + 1 ) . $count;
	$pos = strrpos( $vector_val, '_' );
	$next_vector_val = substr( $vector_val, 0, $pos + 1 ) . $count;

	$a = array( );
	$for_code[] = "var {$vector_val}:" . tool_protocol_as_vector( $list_id, $a ) . " = new " . tool_protocol_as_vector( $list_id, $a ) . "();\n";

	switch ( $data[ 'type' ] )
	{
		case 'byte':  //复杂数据
			$for_code[] = "for each( var {$next_data_val}:String in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t{$vector_val}.push( Game.tool.base64_decode( {$next_data_val} ) );\n";
			$for_code[] = "}\n";
			break;
		case 'bigint':  //有符号64位数字
			$for_code[] = "for each( var {$next_data_val}:Number in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t{$vector_val}.push( {$next_data_val} );\n";
			$for_code[] = "}\n";
			break;
		case 'varchar':
		case 'char':
			$for_code[] = "for each( var {$next_data_val}:String in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t{$vector_val}.push( {$next_data_val} );\n";
			$for_code[] = "}\n";
			break;
		case 'tinyint':  //有符号8位数字
		case 'unsigned tinyint':  //无符号8位数字
		case 'smallint':  //有符号16位数字
		case 'unsigned smallint':  //无符号16位数字
		case 'int':   //有符号32位数字
			$for_code[] = "for each( var {$next_data_val}:int in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t{$vector_val}.push( {$next_data_val} );\n";
			$for_code[] = "}\n";
			break;
		case 'unsigned int':  //无符号32位数字
			$for_code[] = "for each( var {$next_data_val}:uint in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t{$vector_val}.push( {$next_data_val} );\n";
			$for_code[] = "}\n";
			break;
		case 'list':
			$for_code[] = "for each( var {$next_data_val}:Array in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\t" . vector_list_parse( $data[ 'sub_id' ], $next_data_val, $next_vector_val, $count, $p );
			$for_code[] = "\t{$vector_val}.push( {$next_vector_val} );\n";
			$for_code[] = "}\n";
			break;
		case 'struct':
			$struct = $GLOBALS[ 'all_protocol' ][ $data[ 'sub_id' ] ];
			$vo_class = str_to_camel( $struct[ 'name' ] ) . '_DS';

			$for_code[] = "for each( var {$next_data_val}:Array in {$data_val} )\n";
			$for_code[] = "{\n";
			$for_code[] = "\tvar tmp_{$vo_class}:{$vo_class} = new {$vo_class}();\n";
			$for_code[] = "\ttmp_{$vo_class}.init( {$next_data_val} );\n";
			$for_code[] = "\t{$vector_val}.push( tmp_{$vo_class} );\n";
			$for_code[] = "}\n";
			break;
	}
	return join( $p, $for_code );
}