#include "proto_so.h"

/**
 * 生成 连接服务器
 */
void sowrite_so_php_join( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 20000;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	proto_so_so_php_join_t proto_so_so_php_join;
	read_int_from_hash( proto_so_so_php_join, socket_type );
	yile_result_push_data( all_result, &proto_so_so_php_join, sizeof( proto_so_so_php_join_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 ping包
 */
void sowrite_fpm_ping( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 26001;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	proto_so_fpm_ping_t proto_so_fpm_ping;
	read_int_from_hash( proto_so_fpm_ping, time );
	yile_result_push_data( all_result, &proto_so_fpm_ping, sizeof( proto_so_fpm_ping_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 ping返回包
 */
void sowrite_fpm_ping_re( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 26005;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	proto_so_fpm_ping_re_t proto_so_fpm_ping_re;
	read_int_from_hash( proto_so_fpm_ping_re, time );
	yile_result_push_data( all_result, &proto_so_fpm_ping_re, sizeof( proto_so_fpm_ping_re_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 fpm进程加入server
 */
void sowrite_fpm_join( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 26002;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	proto_so_fpm_join_t proto_so_fpm_join;
	read_int_from_hash( proto_so_fpm_join, pid );
	read_int_from_hash( proto_so_fpm_join, fpm_id );
	yile_result_push_data( all_result, &proto_so_fpm_join, sizeof( proto_so_fpm_join_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 空闲状态通知
 */
void sowrite_fpm_idle_report( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 26006;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	proto_so_fpm_idle_report_t proto_so_fpm_idle_report;
	read_int_from_hash( proto_so_fpm_idle_report, fpm_id );
	yile_result_push_data( all_result, &proto_so_fpm_idle_report, sizeof( proto_so_fpm_idle_report_t ) );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 生成 数据代理
 */
void sowrite_fpm_proxy( protocol_result_t *all_result, HashTable *data_hash )
{
	zval **tmp_data;
	all_result->pos = 0;
	packet_head_t packet_info;
	packet_info.size = 0;
	packet_info.pack_id = 26007;
	yile_result_push_data( all_result, NULL, sizeof( packet_head_t ) );
	int32_t tmp_var_int32_t;
	read_int_from_hash_var( proto_so_fpm_proxy, tmp_var_int32_t, session_id );
	yile_result_push_data( all_result, &tmp_var_int32_t, sizeof( tmp_var_int32_t ) );
	read_bytes_from_hash( proto_so_fpm_proxy, data );
	packet_info.size = all_result->pos - sizeof( packet_head_t );
	memcpy( all_result->str, &packet_info, sizeof( packet_head_t ) );
}

/**
 * 解析 PHP加入服务器返回
 */
void soread_so_php_join_re( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_so_php_join_re_t *tmp_struct;
	set_data_pointer( byte_pack, sizeof( proto_so_so_php_join_re_t ), tmp_struct, proto_so_so_php_join_re_t );
	add_assoc_long( result_arr, "result", tmp_struct->result );
}

/**
 * 解析 代理数据包
 */
void soread_so_fpm_proxy( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_so_fpm_proxy_t tmp_struct;
	php_result_copy( byte_pack, &tmp_struct.hash_id, sizeof( tmp_struct.hash_id ) );
	add_assoc_long( result_arr, "hash_id", tmp_struct.hash_id );
	bytes_len_t len_data;
	char *vc_data;
	php_result_copy( byte_pack, &len_data, sizeof( bytes_len_t ) );
	set_data_pointer( byte_pack, len_data, vc_data, char );
	add_assoc_stringl( result_arr, "data",vc_data, len_data, 1 );
}

/**
 * 解析 ping包
 */
void soread_fpm_ping( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_fpm_ping_t *tmp_struct;
	set_data_pointer( byte_pack, sizeof( proto_so_fpm_ping_t ), tmp_struct, proto_so_fpm_ping_t );
	add_assoc_long( result_arr, "time", tmp_struct->time );
}

/**
 * 解析 ping返回包
 */
void soread_fpm_ping_re( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_fpm_ping_re_t *tmp_struct;
	set_data_pointer( byte_pack, sizeof( proto_so_fpm_ping_re_t ), tmp_struct, proto_so_fpm_ping_re_t );
	add_assoc_long( result_arr, "time", tmp_struct->time );
}

/**
 * 解析 fpm进程加入server
 */
void soread_fpm_join( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_fpm_join_t *tmp_struct;
	set_data_pointer( byte_pack, sizeof( proto_so_fpm_join_t ), tmp_struct, proto_so_fpm_join_t );
	add_assoc_long( result_arr, "pid", tmp_struct->pid );
	add_assoc_long( result_arr, "fpm_id", tmp_struct->fpm_id );
}

/**
 * 解析 空闲状态通知
 */
void soread_fpm_idle_report( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_fpm_idle_report_t *tmp_struct;
	set_data_pointer( byte_pack, sizeof( proto_so_fpm_idle_report_t ), tmp_struct, proto_so_fpm_idle_report_t );
	add_assoc_long( result_arr, "fpm_id", tmp_struct->fpm_id );
}

/**
 * 解析 数据代理
 */
void soread_fpm_proxy( protocol_packet_t *byte_pack, zval *result_arr )
{
	proto_so_fpm_proxy_t tmp_struct;
	php_result_copy( byte_pack, &tmp_struct.session_id, sizeof( tmp_struct.session_id ) );
	add_assoc_long( result_arr, "session_id", tmp_struct.session_id );
	bytes_len_t len_data;
	char *vc_data;
	php_result_copy( byte_pack, &len_data, sizeof( bytes_len_t ) );
	set_data_pointer( byte_pack, len_data, vc_data, char );
	add_assoc_stringl( result_arr, "data",vc_data, len_data, 1 );
}
