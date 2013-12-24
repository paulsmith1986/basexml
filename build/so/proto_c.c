#include "proto_c.h"

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
	result_copy( byte_pack, &re_struct->session_id, sizeof( uint32_t ), result_pool );
	re_struct->data = read_bytes( byte_pack, NULL, result_pool );
	return re_struct;
}
#ifdef PROTOCOL_DEBUG

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
	printf( "    %s[session_id] = > ", prefix_char );
	printf( "%u\n", re->session_id );
	printf( "    %s[data] = > ", prefix_char );
	printf( "[Blob %d]\n", re->data->len );
	printf( "%s)\n", prefix_char );
}
#endif
