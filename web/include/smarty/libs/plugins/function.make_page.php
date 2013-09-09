<?php
function smarty_function_make_page($arg, $template)
{
	$re = '<span class="red">第'. $arg[ 'page' ] .'页 共'. $arg[ 'page_count' ] .'页</span>';
	if ( 1 == $arg[ 'page_count' ] )
	{
		return $re;
	}
	$query = implode( '&', $arg[ 'query' ] );
	$url = '/index.php?c=admin&'. $query .'&page=';
	$re .= '<span><a href="'. $url .'1">首页</a></span>';
	if ( 1 != $arg[ 'page' ] )
	{
		$re .= '<span><a href="'. $url . ( $arg[ 'page' ] - 1 ) .'">上一页</a></span>';
	}
	if ( $arg[ 'page' ] != $arg[ 'page_count' ] )
	{
		$re .= '<span><a href="'. $url . ( $arg[ 'page' ] + 1 ) .'">下一页</a></span>';
	}
	$re .= '<span><a href="'. $url . $arg[ 'page_count' ] .'">尾页</a></span>';
	$re .= '<span>跳转：<input type="text" size="5" id="page_go_to" value="'. $arg[ 'page' ] .'"/> <input type="button" value="GO" onclick="page_go_to(\''. $url .'\')"></span>';
	return $re;
}
?>