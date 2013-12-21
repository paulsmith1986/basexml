#include "task_client.h"
//主任务线程
int do_request_task ( protocol_packet_t *tmp_pack )
{
	protocol_result_t read_result_pool;
	memset( &read_result_pool, 0, sizeof( protocol_result_t ) );
	packet_head_t *pack_head = ( packet_head_t* )tmp_pack->data;
	switch( pack_head->pack_id )
	{
		case 20001: //PHP加入服务器返回
		{
			read_result_pool.str = NULL;
			proto_so_php_join_re_t *req_data = read_so_php_join_re( tmp_pack, &read_result_pool );
			if( read_result_pool.error_code > 0 )
			{
				return read_result_pool.error_code;
			}
			request_so_php_join_re( req_data );
		}
		break;
		case 60000: //代理数据包
		{
			char char_so_fpm_proxy[ PROTO_SIZE_SO_FPM_PROXY ];
			read_result_pool.str = char_so_fpm_proxy;
			read_result_pool.max_pos = sizeof( char_so_fpm_proxy );
			proto_so_fpm_proxy_t *req_data = read_so_fpm_proxy( tmp_pack, &read_result_pool );
			if( read_result_pool.error_code > 0 )
			{
				return read_result_pool.error_code;
			}
			request_so_fpm_proxy( req_data );
		}
		break;
	}
	return 0;
}
