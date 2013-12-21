#include "proto_c.h"

/**
 * 生成 连接服务器
 */
void write_so_php_join( protocol_result_t *all_result, proto_so_php_join_t *data_arr )
{
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.pack_id = 20000;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	yile_result_push_data( all_result, data_arr, sizeof( proto_so_php_join_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 ping包
 */
void write_fpm_ping( protocol_result_t *all_result, proto_fpm_ping_t *data_arr )
{
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.pack_id = 26001;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	yile_result_push_data( all_result, data_arr, sizeof( proto_fpm_ping_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 ping返回包
 */
void write_fpm_ping_re( protocol_result_t *all_result, proto_fpm_ping_re_t *data_arr )
{
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.pack_id = 26005;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	yile_result_push_data( all_result, data_arr, sizeof( proto_fpm_ping_re_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 fpm进程加入server
 */
void write_fpm_join( protocol_result_t *all_result, proto_fpm_join_t *data_arr )
{
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.pack_id = 26002;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	yile_result_push_data( all_result, data_arr, sizeof( proto_fpm_join_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 空闲状态通知
 */
void write_fpm_idle_report( protocol_result_t *all_result, proto_fpm_idle_report_t *data_arr )
{
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.pack_id = 26006;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	yile_result_push_data( all_result, data_arr, sizeof( proto_fpm_idle_report_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 解析 PHP加入服务器返回
 */
proto_so_php_join_re_t *read_so_php_join_re( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_so_php_join_re_t *re_struct = NULL;
	if( byte_pack->max_pos - byte_pack->pos != sizeof( proto_so_php_join_re_t ) )
	{
		result_pool->error_code = PROTO_ERROR_SIZEERROR;
	}
	else
	{
		re_struct = (proto_so_php_join_re_t*)&byte_pack->data[ byte_pack->pos ];
	}
	return re_struct;
}

/**
 * 解析 代理数据包
 */
proto_so_fpm_proxy_t *read_so_fpm_proxy( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_so_fpm_proxy_t *re_struct = NULL;
	if( NULL == re_struct )
	{
		if( result_pool->pos + sizeof( proto_so_fpm_proxy_t ) > result_pool->max_pos )
		{
			result_pool->error_code = PROTO_ERROR_OVERFLOW;
			return NULL;
		}
		re_struct = (proto_so_fpm_proxy_t*)&result_pool->str[ result_pool->pos ];
		result_pool->pos += sizeof( proto_so_fpm_proxy_t );
	}
	result_copy( byte_pack, &re_struct->role_id, sizeof( uint32_t ), result_pool );
	result_copy( byte_pack, &re_struct->session_id, sizeof( uint32_t ), result_pool );
	re_struct->data = read_bytes( byte_pack, NULL, result_pool );
	return re_struct;
}

/**
 * 解析 ping包
 */
proto_fpm_ping_t *read_fpm_ping( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_fpm_ping_t *re_struct = NULL;
	if( byte_pack->max_pos - byte_pack->pos != sizeof( proto_fpm_ping_t ) )
	{
		result_pool->error_code = PROTO_ERROR_SIZEERROR;
	}
	else
	{
		re_struct = (proto_fpm_ping_t*)&byte_pack->data[ byte_pack->pos ];
	}
	return re_struct;
}

/**
 * 解析 ping返回包
 */
proto_fpm_ping_re_t *read_fpm_ping_re( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_fpm_ping_re_t *re_struct = NULL;
	if( byte_pack->max_pos - byte_pack->pos != sizeof( proto_fpm_ping_re_t ) )
	{
		result_pool->error_code = PROTO_ERROR_SIZEERROR;
	}
	else
	{
		re_struct = (proto_fpm_ping_re_t*)&byte_pack->data[ byte_pack->pos ];
	}
	return re_struct;
}

/**
 * 解析 fpm进程加入server
 */
proto_fpm_join_t *read_fpm_join( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_fpm_join_t *re_struct = NULL;
	if( byte_pack->max_pos - byte_pack->pos != sizeof( proto_fpm_join_t ) )
	{
		result_pool->error_code = PROTO_ERROR_SIZEERROR;
	}
	else
	{
		re_struct = (proto_fpm_join_t*)&byte_pack->data[ byte_pack->pos ];
	}
	return re_struct;
}

/**
 * 解析 空闲状态通知
 */
proto_fpm_idle_report_t *read_fpm_idle_report( protocol_packet_t *byte_pack, protocol_result_t *result_pool)
{
	proto_fpm_idle_report_t *re_struct = NULL;
	if( byte_pack->max_pos - byte_pack->pos != sizeof( proto_fpm_idle_report_t ) )
	{
		result_pool->error_code = PROTO_ERROR_SIZEERROR;
	}
	else
	{
		re_struct = (proto_fpm_idle_report_t*)&byte_pack->data[ byte_pack->pos ];
	}
	return re_struct;
}
#ifdef PROTOCOL_DEBUG

/**
 * 打印 PHP加入服务器返回
 */
void print_so_php_join_re( proto_so_php_join_re_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "so_php_join_re\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[result] = > ", prefix_char );
	printf( "%u\n", re->result );
	printf( "%s)\n", prefix_char );
}

/**
 * 打印 代理数据包
 */
void print_so_fpm_proxy( proto_so_fpm_proxy_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "so_fpm_proxy\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[role_id] = > ", prefix_char );
	printf( "%u\n", re->role_id );
	printf( "    %s[session_id] = > ", prefix_char );
	printf( "%u\n", re->session_id );
	printf( "    %s[data] = > ", prefix_char );
	printf( "[Blob %d]\n", re->data->len );
	printf( "%s)\n", prefix_char );
}

/**
 * 打印 ping包
 */
void print_fpm_ping( proto_fpm_ping_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "fpm_ping\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[time] = > ", prefix_char );
	printf( "%u\n", re->time );
	printf( "%s)\n", prefix_char );
}

/**
 * 打印 ping返回包
 */
void print_fpm_ping_re( proto_fpm_ping_re_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "fpm_ping_re\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[time] = > ", prefix_char );
	printf( "%u\n", re->time );
	printf( "%s)\n", prefix_char );
}

/**
 * 打印 fpm进程加入server
 */
void print_fpm_join( proto_fpm_join_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "fpm_join\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[pid] = > ", prefix_char );
	printf( "%u\n", re->pid );
	printf( "    %s[fpm_id] = > ", prefix_char );
	printf( "%d\n", re->fpm_id );
	printf( "%s)\n", prefix_char );
}

/**
 * 打印 空闲状态通知
 */
void print_fpm_idle_report( proto_fpm_idle_report_t *re )
{
	int rank = 0;
	char prefix_char[ MAX_LIST_RECURSION * 4 + 1 ];
	yile_printf_tab_string( prefix_char, rank );
	printf( "fpm_idle_report\n" );
	printf( "%s(\n", prefix_char );
	printf( "    %s[fpm_id] = > ", prefix_char );
	printf( "%d\n", re->fpm_id );
	printf( "%s)\n", prefix_char );
}
#endif
