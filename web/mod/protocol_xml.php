<?php
$GLOBALS[ 'valid_type_list' ] = array(
	'tinyint'				=> true,
	'unsigned tinyint'		=> true,
	'smallint'				=> true,
	'unsigned smallint'		=> true,
	'int'					=> true,
	'unsigned int'			=> true,
	'bigint'				=> true,
	'varchar'				=> true,
	'char'					=> true,
	'list'					=> true,
	'struct'				=> true,
	'byte'					=> true,
);
//主协议类型
$GLOBALS[ 'proto_type_list' ] = array(
	'request'	=> 1,
	'response'	=> 2,
	'both'		=> 3
);
$GLOBALS[ 'proto_type_list_reverse' ] = array(
	'request'	=> 2,
	'response'	=> 1,
	'both'		=> 3
);
//全部id
$GLOBALS[ 'auto_protocol_id' ] = array();
//协议id详细信息
$GLOBALS[ 'all_protocol_id_info' ] = array();
//协议名和id关系
$GLOBALS[ 'all_protocol_id_list' ] = array();
/**
 * 检查所有xml文件，如果没有指定id的.生成id
 */
function tool_protocol_auto_all_id( $path, $rank = 0 )
{
	$current_dir = opendir( $path );
	if ( !$current_dir )
	{
		show_excp( '无法打开目录:'. $path );
	}
	while( false !== ( $file = readdir( $current_dir ) ) )
	{
		$sub_path = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
		if( '.' == $file{0} )
		{
			continue;
		}
		if( is_dir( $sub_path ) )
		{
			tool_protocol_auto_all_id( $sub_path, $rank + 1 );
		}
		else
		{
			if ( '.xml' == substr( $file, -4 ) )
			{
				protocol_collect_id( $sub_path );
			}
		}
	}
	if ( 0 == $rank )
	{
		$auto_id = 100;
		foreach ( $GLOBALS[ 'auto_protocol_id' ] as $node )
		{
			while( isset( $GLOBALS[ 'all_protocol_id_info' ][ $auto_id ] ) )
			{
				$auto_id++;
			}
			$GLOBALS[ 'all_protocol_id_info' ][ $auto_id ] = $node;
			$GLOBALS[ 'all_protocol_id_list' ][ $node[ 'name' ] ] = $auto_id;
			$auto_id++;
		}
	}
}

/**
 * 收集id
 */
function protocol_collect_id( $file )
{
	$dom = new DOMDocument();
	$dom->load( $file );
	$path = new DOMXpath( $dom );
	if ( empty( $query ) )
	{
		protocol_node_id( $path, '/root/protocol/response', $file );
		protocol_node_id( $path, '/root/protocol/request', $file );
		protocol_node_id( $path, '/root/protocol/both', $file );
	}
	unset( $dom );
}

/**
 * 找id
 */
function protocol_node_id( $path, $query, $file )
{
	$node_list = $path->query( $query );
	$module = basename( $file, '.xml' );
	$path = dirname( $file );
	for ( $i = 0; $i < $node_list->length; ++$i )
	{
		$node = $node_list->item( $i );
		$node_name = $node->nodeName;
		if ( !$node->hasAttribute( 'name' ) )
		{
			show_excp( $file .' '. $node_name .' 没有name属性' );
		}
		$proto_name = $node->getAttribute( 'name' );
		if ( isset( $GLOBALS[ 'all_protocol_id_list' ][ $proto_name ] ) )
		{
			show_excp( $file .' 协议名:'. $proto_name .' 已经存!' );
		}
		$node_info = array( 'path' => $path, 'file' => $module, 'name' => $proto_name );
		if ( $node->hasAttribute( 'id' ) )
		{
			$id = $node->getAttribute( 'id' );
			if ( isset( $GLOBALS[ 'all_protocol_id_info' ][ $id ] ) )
			{
				$tmp = $GLOBALS[ 'all_protocol_id_info' ][ $id ];
				show_excp( $file .' '. $proto_name .' id【'. $id .'】 冲突 已经存在于 '. $tmp[ 'path' ] .'/'. $tmp[ 'file' ] .'.xml 中' );
			}
			$GLOBALS[ 'all_protocol_id_info' ][ $id ] = $node_info;
			$GLOBALS[ 'all_protocol_id_list' ][ $proto_name ] = $id;
		}
		else
		{
			$GLOBALS[ 'auto_protocol_id' ][] = $node_info;
			$GLOBALS[ 'all_protocol_id_list' ][ $proto_name ] = -1;
		}
	}
}

/**
 * 读取一个目录的xml文件
 */
function tool_protocol_xml( $xml_dir, $proto_type = 'all', $filter = array() )
{
	$GLOBALS[ 'all_protocol' ] = array();
	$GLOBALS[ 'all_protocol_item' ] = array();
	$GLOBALS[ 'all_list' ] = array();
	//协议反转  reqeust和response互换
	$GLOBALS[ 'protocol_reverse' ] = array();
	//过滤
	$GLOBALS[ 'protocol_filter' ] = $filter;
	if ( empty( $xml_dir ) )
	{
		show_excp( '未指定xml目录或者 文件夹' );
	}
	if ( !is_array( $xml_dir ) )
	{
		$xml_dir = array( $xml_dir );
	}
	$xml_file_list = tool_protoxol_get_xmlfile( $xml_dir );
	if ( empty( $GLOBALS[ 'all_protocol_id_info' ] ) )
	{
		$auto_path = dirname( dirname( $xml_file_list[ 0 ] ) );
		tool_protocol_auto_all_id( $auto_path );
	}
	tool_protocol_xml_preread( $xml_file_list );
	tool_protocol_xml_read_dir( $xml_file_list );
	//移除不用的struct
	tool_protocol_struct_remove_unuse();
	//过滤类型
	tool_protocol_struct_filt_type( $proto_type );
	//确定struct是否是定长
	tool_protocol_is_struct_fix();
}

/**
 * 获取指定的xml列表
 */
function tool_protoxol_get_xmlfile( $xml_dir )
{
	$file_list = array();
	foreach ( $xml_dir as $key => $value )
	{
		if ( is_numeric( $key ) )
		{
			$base_path = $value;
			$is_reverse = false;
		}
		else
		{
			$base_path = $key;
			$is_reverse = $value;
		}
		if ( is_dir(  $base_path ) )
		{
			$current_dir = opendir( $base_path );
			while( false !== ( $file = readdir( $current_dir ) ) )
			{
				$file_path = $base_path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
				if( '.' == $file{0} || is_dir( $file_path ) )
				{
					continue;
				}
				if ( '.xml' == substr( $file, -4 ) )
				{
					$file_list[ $file_path ] = true;
					if ( $is_reverse )
					{
						$GLOBALS[ 'protocol_reverse' ][ basename( $file_path, '.xml' ) ] = true;
					}
				}
			}
		}
		else
		{
			if ( !is_file( $base_path ) || '.xml' != substr( $base_path, -4 ) )
			{
				show_excp( '不支持文件:'. $base_path );
			}
			$file_list[ $base_path ] = true;
			if ( $is_reverse )
			{
				$GLOBALS[ 'protocol_reverse' ][ basename( $base_path, '.xml' ) ] = true;
			}
		}
	}
	return array_keys( $file_list );
}

/**
 * 读文件夹内的xml文件
 */
function tool_protocol_xml_read_dir( $xml_path )
{
	//递归检测
	$GLOBALS[ 'recursion_check' ] = array();
	$GLOBALS[ 'file_dom_list' ] = array();
	$GLOBALS[ 'include_recursion_check' ] = array();
	foreach ( $xml_path as $file )
	{
		tool_protocol_xml_read_file( $file );
	}
}

/**
 * 正式读取一个文件
 */
function tool_protocol_xml_read_file( $file, $query = '' )
{
	$dom = new DOMDocument();
	$dom->formatOutput = true;
	$module = basename( $file, '.xml' );
	$dom->load( $file );
	$GLOBALS[ 'file_dom_list' ][ $file ] = $dom;
	tool_protocol_xml_read( $dom, $file, $module, $query );
}

/**
 * 从结果里提取一个struct和相关struct
 */
function tool_protocol_xml_get_struct( $struct_name, &$result )
{
	$result[ $struct_name ] = true;
	$items = $GLOBALS[ 'all_protocol_item' ][ $struct_name ];
	foreach ( $items as $item_rs )
	{
		if ( 'struct' == $item_rs[ 'type' ] )
		{
			tool_protocol_xml_get_struct( $item_rs[ 'sub_id' ], $result );
		}
		if ( 'list' == $item_rs[ 'type' ] )
		{
			tool_protocol_xml_get_list( $item_rs[ 'sub_id' ], $result );
		}
	}
}

/**
 * 从结果里提取一个list的struct
 */
function tool_protocol_xml_get_list( $list_id, &$result )
{
	$list_info = $GLOBALS[ 'all_list' ][ $list_id ];
	if ( 'struct' == $list_info[ 'type' ] )
	{
		tool_protocol_xml_get_struct( $list_info[ 'sub_id' ], $result );
	}
	elseif ( 'list' == $list_info[ 'type' ] )
	{
		tool_protocol_xml_get_list( $list_info[ 'sub_id' ], $result );
	}
}

/**
 * 过滤不需要的类型
 */
function tool_protocol_struct_filt_type( $proto_type )
{
	switch ( $proto_type )
	{
		case 'request':
			$need_type = 1;
		break;
		case 'all':
			$need_type = 3;
		break;
		default:
			$need_type = 2;
		break;
	}
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( $need_type == $rs[ 'proto_type' ] )
		{
			continue;
		}
		if ( 3 == $rs[ 'proto_type' ] )
		{
			//trigger_error( '检测到response和request共用相同的struct:'. $pid, E_USER_WARNING );
			continue;
		}
		if ( 0 == $rs[ 'proto_type' ] )
		{
			echo "检测到没有使用的struct ". $pid ."\n";
		}
		if ( 0 == $rs[ 'proto_type' ] || 3 != $need_type )
		{
			unset( $GLOBALS[ 'all_protocol' ][ $pid ] );
			unset( $GLOBALS[ 'all_protocol_items' ][ $pid ] );
		}
	}
}

/**
 * 预处理
 */
function tool_protocol_xml_preread( $xml_file_list )
{
	foreach ( $xml_file_list as $file )
	{
		tool_protocol_xml_preread_file( $file );
	}
}

/**
 * 预处理一个文件
 */
function tool_protocol_xml_preread_file( $file, $query = '' )
{
	$dom = new DOMDocument();
	$module = basename( $file, '.xml' );
	$dom->load( $file );
	$dom->formatOutput = true;
	$path = new DOMXpath( $dom );
	if ( empty( $query ) )
	{
		tool_protocol_xml_preread_query( $path, '/root/struct', $module, $file );
		tool_protocol_xml_preread_query( $path, '/root/protocol/struct', $module, $file );
		tool_protocol_xml_preread_query( $path, '/root/protocol/response', $module, $file );
		tool_protocol_xml_preread_query( $path, '/root/protocol/request', $module, $file );
		tool_protocol_xml_preread_query( $path, '/root/protocol/both', $module, $file );
	}
	else
	{
		tool_protocol_xml_preread_query( $path, $query, $module, $file );
	}
	unset( $dom );
}

/**
 * 解析注释
 */
function tool_protocol_read_comment( $node, $file = '', $auto_comment = false )
{
	$type = $node->nodeType;
	$pnode = $node->previousSibling;
	if ( !$pnode )
	{
		return;
	}
	$i = 0;
	$is_find = false;
	while ( $type != $pnode->nodeType && ++$i < 5 )
	{
		//找到注释
		if ( 8 == $pnode->nodeType )
		{
			if ( !$auto_comment )
			{
				$node->setAttribute( 'desc', $pnode->textContent );
			}
			$is_find = true;
			break;
		}
		$pnode = $pnode->previousSibling;
		if ( !$pnode )
		{
			break;
		}
	}
	//没找到注释, 尝试desc字段
	if ( !$is_find && $auto_comment )
	{
		$desc = '';
		if ( $node->hasAttribute( 'desc' ) )
		{
			$desc = $node->getAttribute( 'desc' );
			$node->removeAttribute( 'desc' );
		}
		if ( empty( $desc ) )
		{
			$desc = 'Comment here!';
		}
		$comment = new DOMComment( $desc );
		$line_node = $node->previousSibling->cloneNode();
		//$node->parentNode->insertBefore( $line, $node );
		$node->parentNode->insertBefore( $comment, $node );
		$node->parentNode->insertBefore( $line_node, $node );
		//$GLOBALS[ 'xml_file_is_change' ] = $file;
	}
}

/**
 * 预处理节点
 */
function tool_protocol_xml_preread_query( $path, $query, $module, $file )
{
	$struct_list = $path->query( $query );
	for ( $i = 0; $i < $struct_list->length; ++$i )
	{
		tool_protocol_read_comment( $struct_list->item( $i ), $file, true );
		tool_protocol_xml_pre_read_struct( $struct_list->item( $i ), $module, $file );
	}
}

/**
 * 预读struct
 */
function tool_protocol_xml_pre_read_struct( $node, $module, $file )
{
	if ( !$node->hasAttribute( 'name' ) )
	{
		show_excp( '文件:'. $file .' Node:'. $node->nodeName .' 公用的struct必须有name属性' );
	}
	$struct_name = tool_protocol_struct_name( $module, $node->getAttribute( 'name' ) );
	//从其它文件读取
	if ( 'struct' == $node->nodeName && 0 == $node->childNodes->length && $node->hasAttribute( 'from' ) )
	{
		//$GLOBALS[ 'all_protocol' ][ $struct_name ] = true;
		return;
	}

	if ( isset( $GLOBALS[ 'all_protocol' ][ $struct_name ] ) )
	{
		show_excp( '文件:'. $file .' '. $struct_name .' 重复 node_name:'. $node->nodeName );
	}
	$GLOBALS[ 'all_protocol' ][ $struct_name ] = true;
}

/**
 * 读取一个xml文件
 */
function tool_protocol_xml_read( $dom, $file, $module, $query )
{
	$path = new DOMXpath( $dom );
	if ( empty( $query ) )
	{
		tool_protocol_xml_read_path( $path, '/root/struct', $module );
		tool_protocol_xml_read_path( $path, '/root/protocol/struct', $module );
		tool_protocol_xml_read_path( $path, '/root/protocol/response', $module );
		tool_protocol_xml_read_path( $path, '/root/protocol/request', $module );
		tool_protocol_xml_read_path( $path, '/root/protocol/both', $module );
	}
	else
	{
		tool_protocol_xml_read_path( $path, $query, $module );
	}
}

/**
 * xpath
 */
function tool_protocol_xml_read_path( $path, $query, $module )
{
	$struct_list = $path->query( $query );
	for ( $i = 0; $i < $struct_list->length; ++$i )
	{
		tool_protocol_read_comment( $struct_list->item( $i ) );
		tool_protocol_xml_read_struct( $struct_list->item( $i ), $module, 1 );
	}
}

/**
 * 读取一个struct
 */
function tool_protocol_xml_read_struct( $node, $module, $rank = 1 )
{
	$is_sub = !isset( $GLOBALS[ 'proto_type_list' ][ $node->nodeName ] );
	$struct_name = tool_protocol_struct_name( $module, $node->getAttribute( 'name' ) );
	if ( isset( $GLOBALS[ 'recursion_check' ][ $struct_name ] ) )
	{
		show_excp( '检测到循环递归:'. $struct_name );
	}
	//检测引用其它文件的struct
	if ( 'struct' == $node->nodeName && $node->hasAttribute( 'from' ) )
	{
		//struct已经存在
		if ( isset( $GLOBALS[ 'all_protocol' ][ $struct_name ] ) )
		{
			return;
		}
		$file = ROOT_PATH .'protocol/xml/'. $node->getAttribute( 'from' );
		if ( !is_file( $file ) )
		{
			show_excp( '找不到包含文件:'. $file );
		}
		if ( isset( $GLOBALS[ 'include_recursion_check' ][ $file .'_'. $struct_name ] ) )
		{
			show_excp( '检测到循环引用:'. $file );
		}
		$GLOBALS[ 'include_recursion_check' ][ $file .'_'. $struct_name ] = true;
		$now_all_protocol = $GLOBALS[ 'all_protocol' ];
		$now_all_items = $GLOBALS[ 'all_protocol_item' ];
		$GLOBALS[ 'all_protocol' ] = array();
		$GLOBALS[ 'all_protocol_item' ] = array();
		$query = '/root/struct';
		tool_protocol_xml_preread_file( $file, $query );
		tool_protocol_xml_read_file( $file, $query );
		if ( !isset( $GLOBALS[ 'all_protocol' ][ $struct_name ] ) )
		{
			show_excp( '文件 '. $file .' 中没有找到struct:'. $struct_name );
		}
		$result = array();
		tool_protocol_xml_get_struct( $struct_name, $result );
		foreach ( $GLOBALS[ 'all_protocol' ] as $tmp_name => $tmp_v )
		{
			if ( isset( $result[ $tmp_name ] ) )
			{
				continue;
			}
			unset( $GLOBALS[ 'all_protocol' ][ $tmp_name ], $GLOBALS[ 'all_protocol_item' ][ $tmp_name ] );
		}
		$GLOBALS[ 'all_protocol' ] = array_merge( $GLOBALS[ 'all_protocol' ], $now_all_protocol );
		$GLOBALS[ 'all_protocol_item' ] = array_merge( $GLOBALS[ 'all_protocol_item' ], $now_all_items );
		unset( $now_all_items, $now_all_protocol );
		unset( $GLOBALS[ 'include_recursion_check' ][ $file .'_'. $struct_name ] );
		return;
	}

	$GLOBALS[ 'recursion_check' ][ $struct_name ] = true;
	$re = array(
		'struct_id'		=> $is_sub ? 0 : $GLOBALS[ 'all_protocol_id_list' ][ $struct_name ],
		'name'			=> $struct_name,
		'module'		=> $module,
		'is_sub'		=> $is_sub,
		'proto_type'	=> 0,		//协议类型 0:暂时不确定 1:request, 2:response 3:both
		'desc'			=> $node->getAttribute( 'desc' ),
		'size'			=> 0,
	);
	//如果是主协议
	if ( !$re[ 'is_sub' ] )
	{
		if ( isset( $GLOBALS[ 'protocol_filter' ][ $module ] ) )
		{
			if ( isset( $GLOBALS[ 'protocol_filter' ][ $module ][ $struct_name ] ) )
			{
				$re[ 'proto_type' ] = $GLOBALS[ 'protocol_filter' ][ $module ][ $struct_name ];
			}
		}
		else
		{
			$node_name = $node->nodeName;
			if ( isset( $GLOBALS[ 'protocol_reverse' ][ $module ] ) )
			{
				$re[ 'proto_type' ] = $GLOBALS[ 'proto_type_list_reverse' ][ $node_name ];
			}
			else
			{
				$re[ 'proto_type' ] = $GLOBALS[ 'proto_type_list' ][ $node_name ];
			}
		}
		//解析大小
		if ( $node->hasAttribute( 'size' ) )
		{
			$size_str = strtolower( $node->getAttribute( 'size' ) );
			if ( 'runtime' === $size_str )
			{
				$re[ 'size' ] = 'runtime';
			}
			else
			{
				if ( 'k' == substr( $size_str, -1 ) )
				{
					$size_str = (int)$size_str * 1024;
				}
				$re[ 'size' ] = abs( (int)$size_str );
			}
		}
		//是否白名单
		if ( $node->hasAttribute( 'white_list' ) )
		{
			$is_white_list = strtolower( $node->getAttribute( 'white_list' ) );
			if ( 'true' === $is_white_list )
			{
				$re[ 'is_write_list' ] = true;
			}
		}
	}
	if( $node->hasAttribute( 'control' ) )
	{
		$re['control'] = $node->getAttribute( 'control' );
		$re['action'] = $node->getAttribute( 'action' );
	}
	$re[ 'is_private' ] = $rank > 1;
	if ( isset( $GLOBALS[ 'all_protocol' ][ $struct_name ] ) && 1 != $rank )
	{
		show_excp( 'module:'. $module .' '. $struct_name .'重复 node_name:'. $node->nodeName );
	}

	$GLOBALS[ 'all_protocol' ][ $struct_name ] = $re;
	$items = $node->childNodes;
	$item_re = array();
	for ( $i = 0; $i < $items->length; ++$i )
	{
		$node = $items->item( $i );
		if ( 'key' != $node->nodeName )
		{
			continue;
		}
		$item_re[ ] = tool_protocol_xml_item( $node, $module, $rank );
	}
	$GLOBALS[ 'all_protocol_item' ][ $struct_name ] = $item_re;
	unset( $GLOBALS[ 'recursion_check' ][ $struct_name ] );
}

/**
 * 分析一项属性
 */
function tool_protocol_xml_item( $node, $module, $rank )
{
	$tmp = array();
	$name = $node->getAttribute( 'name' );
	if ( empty( $name ) )
	{
		show_excp( 'key 缺少 name 属性' );
	}
	$type = $node->getAttribute( 'type' );
	if ( empty( $type ) || !isset( $GLOBALS[ 'valid_type_list' ][ $type ] ) )
	{
		show_excp( $name .'不支持的type:'. $type );
	}
	$tmp[ 'item_name' ] = $name;
	$tmp[ 'type' ] = $type;
	$tmp[ 'desc' ] = $node->getAttribute( 'desc' );
	switch ( $type )
	{
		case 'char':
			$char_len = $node->getAttribute( 'len' );
			if ( !is_numeric( $char_len ) || abs( $char_len > 255 ) )
			{
				show_excp( 'char:'. $name .' 需要指定0~255的长度' );
			}
			$tmp[ 'char_len' ] = abs( $char_len );
		break;
		case 'struct':
			$tmp[ 'sub_id' ] = tool_protocol_xml_struct_sub( $node, $module, $rank + 1 );
		break;
		case 'list':
			$str = 'list';
			$tmp[ 'sub_id' ] = tool_protocol_xml_read_list( $node, $module, $name, $str );
		break;
	}
	return $tmp;
}

/**
 * 读取list
 */
function tool_protocol_xml_read_list( $node, $module, $name, &$sub_id_str )
{
	$list_arr = array();
	$list_node = tool_protocol_xml_get_node( $node, 'list' );
	if ( false === $list_node )
	{
		show_excp( '模块:'. $module .' key:'. $name .' list填写出错' );
	}
	$type = $list_node->getAttribute( 'type' );
	if ( empty( $type ) || !isset( $GLOBALS[ 'valid_type_list' ][ $type ] ) )
	{
		show_excp( '模块:'. $module .' key:'. $name .' list_type:'. $type .' 不支持' );
	}
	switch ( $type )
	{
		case 'char':
			$char_len = $list_node->getAttribute( 'len' );
			if ( !is_numeric( $char_len ) || abs( $char_len > 255 ) )
			{
				show_excp( 'char:'. $name .' 需要指定0~255的长度' );
			}
			$list_arr[ 'char_len' ] = abs( $char_len );
			$sub_id_str .= '_char';
			$list_arr[ 'sub_id' ] = 0;
		break;
		case 'struct':
			//为了代码方便,手动指定名称 list
			$list_node->setAttribute( 'name', 'list' );
			$sub_id = tool_protocol_xml_struct_sub( $list_node, $module, 2 );
			$list_arr[ 'sub_id' ] = $sub_id;
			$sub_id_str .= '_'. $sub_id;
			$list_node->removeAttribute( 'name' );
		break;
		case 'list':
			tool_protocol_xml_read_list( $list_node, $module, $name, $sub_id_str );
			$list_arr[ 'sub_id' ] = $sub_id_str;
			$sub_id_str = 'list_'. $sub_id_str;
		break;
		default:
			$sub_id_str .= '_'. $type;
			$list_arr[ 'sub_id' ] = 0;
		break;
	}
	$list_arr[ 'name' ] = $sub_id_str;
	$list_arr[ 'id' ] = $sub_id_str;
	$list_arr[ 'type' ] = $type;
	$GLOBALS[ 'all_list' ][ $sub_id_str ] = $list_arr;
	return $sub_id_str;
}

/**
 * 获取一个指定的node
 */
function tool_protocol_xml_get_node( $node, $node_name )
{
	$child_node = $node->childNodes;
	$sub_node = false;
	for ( $i = 0; $i < $child_node->length; ++$i )
	{
		if ( $node_name != $child_node->item( $i )->nodeName )
		{
			continue;
		}
		$sub_node = $child_node->item( $i );
		break;
	}
	return $sub_node;
}

/**
 * 子struct检测
 */
function tool_protocol_xml_struct_sub( $node, $module, $rank )
{
	$sub_name = $node->getAttribute( 'struct' );
	if ( empty( $sub_name ) )
	{
		$child_node = $node->childNodes;
		$sub_node = tool_protocol_xml_get_node( $node, 'struct' );
		if ( false == $sub_node )
		{
			show_excp( 'Module:'. $module .' Node:'. $node->nodeName .' 没有填写struct' );
		}
		$need_remove = false;
		if ( !$sub_node->hasAttribute( 'name' ) )
		{
			$need_remove = true;
			$sub_name = $node->getAttribute( 'name' ). '_arr';
			while( isset( $GLOBALS[ 'all_protocol' ][ $sub_name ] ) )
			{
				$sub_name .= '_arr';
			}
			$sub_node->setAttribute( 'name', $sub_name );
		}
		else
		{
			$sub_name = $sub_node->getAttribute( 'name' );
		}
		tool_protocol_xml_read_struct( $sub_node, $module, $rank + 1 );
		if ( $need_remove )
		{
			$sub_node->removeAttribute( 'name' );
		}
	}
	else
	{
		$struct_name = tool_protocol_struct_name( $module, $sub_name );
		if ( !isset( $GLOBALS[ 'all_protocol' ][ $struct_name ] ) )
		{
			show_excp( 'module:'. $module .' 不存在 struct '. $sub_name .' Node:'. $node->nodeName );
		}
		$struct_info = $GLOBALS[ 'all_protocol' ][ $struct_name ];
		if ( true !== $struct_info )
		{
			if ( !$struct_info[ 'is_sub' ] )
			{
				show_excp( 'module:'. $module .' struct '. $sub_name .' 是一个独立的协议，不能当作struct使用' );
			}
			if ( $struct_info[ 'is_private' ] )
			{
				show_excp( '不能使用私有 struct :'. $sub_name );
			}
		}
		if ( isset( $GLOBALS[ 'recursion_check' ][ $struct_name ] ) )
		{
			show_excp( '检测到循环递归:'. $struct_name );
		}
	}
	return tool_protocol_struct_name( $module, $sub_name );
}

/**
 * 协议struct名称
 */
function tool_protocol_struct_name( $module, $name )
{
	return $name;
}

/**
 * 将没使用到的struct移除掉
 */
function tool_protocol_struct_remove_unuse()
{
	foreach ( $GLOBALS[ 'all_protocol' ] as $pid => $rs )
	{
		if ( !is_array( $rs ) )
		{
			unset( $GLOBALS[ 'all_protocol' ][ $pid ] );
		}
		if ( $rs[ 'is_sub' ] )
		{
			continue;
		}
		tool_protocol_struct_remove_struct( $pid, $rs[ 'proto_type' ], 'runtime' === $rs[ 'size' ] );
	}
}

/**
 * 将没使用到的struct移除掉_struct检测
 */
function tool_protocol_struct_remove_struct( $pid, $proto_type, $is_runtime_size )
{
	$GLOBALS[ 'all_protocol' ][ $pid ][ 'proto_type' ] |= $proto_type;
	if ( true == $is_runtime_size )
	{
		$GLOBALS[ 'all_protocol' ][ $pid ][ 'size' ] = 'runtime';
	}
	$items = $GLOBALS[ 'all_protocol_item' ][ $pid ];
	foreach ( $items as $item_rs )
	{
		if ( 'struct' == $item_rs[ 'type' ] )
		{
			tool_protocol_struct_remove_struct( $item_rs[ 'sub_id' ], $proto_type, $is_runtime_size );
		}
		if ( 'list' == $item_rs[ 'type' ] )
		{
			tool_protocol_struct_remove_list( $item_rs[ 'sub_id' ], $proto_type, $is_runtime_size );
		}
	}
}

/**
 * 将没使用到的struct移除掉_list检测
 */
function tool_protocol_struct_remove_list( $list_id, $proto_type, $is_runtime_size )
{
	$list_info = $GLOBALS[ 'all_list' ][ $list_id ];
	if ( 'struct' == $list_info[ 'type' ] )
	{
		tool_protocol_struct_remove_struct( $list_info[ 'sub_id' ], $proto_type, $is_runtime_size );
	}
	elseif ( 'list' == $list_info[ 'type' ] )
	{
		tool_protocol_struct_remove_list( $list_info[ 'sub_id' ], $proto_type, $is_runtime_size );
	}
}