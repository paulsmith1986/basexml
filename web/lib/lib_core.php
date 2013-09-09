<?php
$SMARTY_OBJECT = null;						//全局smarty对象变量
$GLOBALS[ 'OUT_DATA' ] = array(
	'ERROR'			=> array(),
	'DEBUG_STR'		=> array(),
	'NOTICE'		=> array()

);
//PHP自定义错误处理函数
set_error_handler( 'error_handle' );

//PHP异常退出处理函数
register_shutdown_function( 'shutdown_function' );
/**
 * 获取传入参数的方法
 * @param string $var_name 参数名称
 * @param string $var_type 参数类型
 * @param bool $is_musg 是否是必须要的参数
 * @return mixed 参数值
 */
function get_var ( $var_name, $var_type = 'int', $var_must = true )
{
	$value = null;
	if ( isset( $_REQUEST[ $var_name ] ) )
	{
		$value = $_POST[ $var_name ];
	}
	if ( null === $value )
	{
		if ( $var_must )
		{
			throw new Exception( 'NO ARG:'. $var_name, 10000 );
		}
		else
		{
			return $value;	//可以不传递的参数，如果不传就为null;
		}
	}
	switch ( $var_type )
	{
		case 'string':		//字符统一addslashes处理
			$re = addslashes( $value );
		break;
		case 'int':			//数字全部int处理，并且不能传递小于0的数字
			$re = (int)$value;
			if ( $re < 0 )
			{
				$re = 0;
			}
		break;
	}
	return $re;
}

/**
 * 指定control和action 返回处理结果
 * @param	$control_name	string	controlner名
 * @param	$action_name	string	action名
 * @return mixed
 */
function action_dispatch ( $control_name, $action_name )
{
	$control_file = ROOT_PATH . 'web/control/' . $control_name . '_control.php';
	require_once $control_file;
	$func_name = $control_name . '_' . $action_name .'_control';
	$func_name();
}

/**
 * 显示界面
 */
function view_html ( $tpl, $data = array(  ) )
{
	$smar_obj = get_tpl();
	foreach ( $data as $key => $item )
	{
		$smar_obj->assign( $key, $item );
	}
	$GLOBALS[ 'tpl' ] = $tpl;
}

/**
* 获取Smarty对象
* @param		bool		$is_create		当为null的时候，是否自动创建
* @return		object
*/
function &get_tpl()
{
	global $SMARTY_OBJECT;
	if ( null == $SMARTY_OBJECT )
	{
		$GLOBALS[ 'tpl' ] = 'error';
		$SMARTY_OBJECT = new Smarty();
		$SMARTY_OBJECT->left_delimiter = '{{';
		$SMARTY_OBJECT->right_delimiter = '}}';
		$SMARTY_OBJECT->assign( 'code_type', 'run' );
		$SMARTY_OBJECT->template_dir = ROOT_PATH .'web/tpl/';
		$SMARTY_OBJECT->compile_dir = ROOT_PATH . "web/tpl/templates_c/";
	}
	return $SMARTY_OBJECT;
}
//处理异常
function do_with_excp( $excep_obj )
{
	//整理出要打印或者记录日志的格式
	$err_level = $excep_obj->getCode();
	$log_msg = parse_exception( $excep_obj, "\n" ) . "\n\n";
	$log_msg .= "\n====PARAMS====\n\nget=>". $_SERVER[ 'QUERY_STRING' ];
	if ( !empty( $_POST ) )
	{
		 $log_msg .= "\n\npost=>". json_encode( $_POST );
	}
	$log_msg .=  "\n\n\n===end====<br/>";
	$GLOBALS[ 'OUT_DATA' ][ 'ERROR' ][] = $log_msg;
}

/*
 * 提取出异常错误里的详细信息
 * @param		object		$excep_obj		异常对象
 * @param		string		$imp			换行符
 */
function parse_exception ( $excep_obj, $imp = "\n" )
{
	$show_msg = array(  );
	$show_msg[] = '# 错误时间 =>'. date( 'Y-m-d H:i:s' );
	$show_msg[] = '# 错误消息 =>'. $excep_obj->getMessage();
	$show_msg[] = '# 错误位置 =>'. $excep_obj->getFile() .':'. $excep_obj->getLine() .':'. $excep_obj->getCode();
	$etrac = $excep_obj->getTrace();
	$total_eno = count( $etrac ) - 1;
	$eno = 0;
	foreach ( $etrac as $eno => $each_trace )
	{
		$show_msg[] = '================================================================';
		$tmp = '第'. ( $total_eno - $eno ) .'步 文件:'. $each_trace[ 'file' ] .' ('. $each_trace[ 'line' ] .'行)';
		$tmp .= $imp. $imp .'函数名：';
		if ( isset( $each_trace[ 'class' ] ) )
		{
			$tmp .= $each_trace[ 'class' ] .'->';
		}
		$tmp .= $each_trace['function'] .'()';
		if ( isset( $each_trace[ 'args' ] ) && !empty( $each_trace[ 'args' ] ) )
		{
			$tmp_arg = array(  );
			foreach ( $each_trace[ 'args' ] as $ano => $a_arg )
			{
				$atmp = $imp .'@参数_'. $ano .'( '. gettype( $a_arg ) .' ) = ';
				if ( !is_numeric( $a_arg ) && !is_string( $a_arg ) )
				{
					if ( is_object( $a_arg) )
					{
						$a_arg = 'OBJECT';
					}
					else
					{
						$a_arg = json_encode( $a_arg );
					}
				}
				if ( strlen( $a_arg ) > 100 )
				{
					$a_arg = '内容较多';
				}
				$atmp .= $a_arg;
				$tmp_arg[] = $atmp. '';
			}
			$tmp .= $imp . implode( $imp, $tmp_arg );
		}
		$show_msg[] = $tmp;
	}
	return implode( $imp, $show_msg );
}

/**
 * 错误提示
 */
function show_error ( $msg )
{
	$GLOBALS[ 'OUT_DATA' ][ 'ERROR' ][] = $msg;
	page_end();
	die();
}

/**
 * 报出异常
 */
function show_excp( $msg )
{
	throw new Exception( $msg );
}

/**
 * 页面结束
 */
function page_end ()
{
	$smar_obj = get_tpl();
	//捕捉输出
	$echo_str = ob_get_contents();
	ob_clean();
	if ( !empty( $echo_str ) )
	{
		$GLOBALS[ 'OUT_DATA' ][ 'DEBUG_STR' ][] = $echo_str;
	}
	$smar_obj->assign( 'error', join( "\n", $GLOBALS[ 'OUT_DATA' ][ 'ERROR' ] ) );
	if ( isset( $_POST[ 'code' ] ) )
	{
		$smar_obj->assign( 'code', $_POST[ 'code' ] );
	}
	$smar_obj->assign( 'debug', join( "\n", $GLOBALS[ 'OUT_DATA' ][ 'DEBUG_STR' ] ) );
	$smar_obj->assign( 'notice', join( "\n", $GLOBALS[ 'OUT_DATA' ][ 'NOTICE' ] ) );
	$smar_obj->display( $GLOBALS[ 'tpl' ] .'.tpl' );
}

/**
 * 自定义错误处理函数
 * @param int $error_no 错误编号
 * @param string $error_str 错误描述
 * @param string $err_file 错误文件
 * @param int $err_line 错误位置行号
 * @return void
 */
function error_handle ( $error_no, $error_str, $err_file, $err_line )
{
	switch ( $error_no )
	{
		case E_WARNING:
		case E_USER_WARNING:
			$str = 'PHP Warning';
		break;
		case E_NOTICE:
		case E_USER_NOTICE:
			$str = 'PHP Notice';
		break;
		case E_ERROR:
		case E_USER_ERROR:
		case E_COMPILE_ERROR:
			$str = 'PHP Fatal error';
		break;
		case E_PARSE:
			$str = 'Parse error';
		break;
		default:
			$str = 'PHP Error[error_no:' . $error_no .']';
		break;
	}
	$GLOBALS[ 'OUT_DATA' ][ 'NOTICE' ][] = $str .': '. $error_str .' in '. $err_file .' on line '. $err_line;
}

/**
 * 异常处理函数
 * @return void
 */
function shutdown_function ( )
{
	$last_err = error_get_last();
	if ( !empty( $last_err ) )
	{
		error_handle( $last_err[ 'type' ], $last_err[ 'message' ], $last_err[ 'file' ], $last_err[ 'line' ] );
		$GLOBALS[ 'OUT_DATA' ][ 'ERROR' ][] = $last_err[ 'message' ];
		if ( E_ERROR == $last_err[ 'type' ] )
		{
			page_end();
		}
	}
}
