<!DOCTYPE html>
<html>
<head>
	<title>协议管理工具</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="static/css/main.css"/>
	<script type='text/javascript' src="static/js/tool.js"></script>
</head>
<body>
<div id="head">
	<span class="item"><a href="index.php">首页</a></span>
	<span class="item"><a href="index.php?a=proto">生成协议</a></span>
	<span class="item"><a href="index.php?a=proto_process">进程生成协议</a></span>
</div>
<hr/>
<div>
	{{if ('run' == $code_type)}}
	<form action="index.php?a=run_code" method="post">
	<div>
		<textarea style="width:90%;height:300px;margin:0 5%;" name="code" id="run_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
	</div>
	<div style="text-align:center"><input type="submit" value="执行代码"></div>
	</form>
	{{elseif ('simulate' == $code_type)}}
		<form action="index.php?a=pack_data&proto={{$proto}}" method="post">
		<div>
			<textarea style="width:70%;height:300px;margin:0 15%;" name="code" id="pack_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
		</div>
		<div style="text-align:center"><input type="submit" value="打包数据"></div>
		</form>
	{{elseif ('unpack' == $code_type)}}
		<form action="index.php?a=unpack_data" method="post">
		<div>
			<textarea style="width:70%;height:300px;margin:0 15%;" name="code" id="unpack_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
		</div>
		<div style="text-align:center"><input type="submit" value="解包数据"></div>
		</form>
	{{/if}}
</div>
<div style="width:90%;margin: 0 auto;word-break: break-all; word-wrap:break-word;">
	{{if !empty( $error )}}
	<div style="background-color:red;font-size:15px;">
		<pre class="error_msg" style="padding:2%;">{{$error}}</pre>
	</div>
	{{/if}}
	{{if !empty( $notice )}}
	<div style="background-color:yellow;font-size:15px;">
		<pre class="notice_msg" style="padding:2%;">{{$notice}}</pre>
	</div>
	{{/if}}

	{{if !empty( $debug )}}
	<div style="background-color:#EBFBE6;font-size:15px;">
		<pre class="notice_msg" style="padding:2%;">{{$debug}}</pre>
	</div>
	{{/if}}
</div>