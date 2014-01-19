#include "task_client.h"
//主任务线程
int do_request_task ( protocol_packet_t *tmp_pack )
{
	protocol_result_t read_result_pool;
	memset( &read_result_pool, 0, sizeof( protocol_result_t ) );
	packet_head_t *pack_head = ( packet_head_t* )tmp_pack->data;
	switch( pack_head->pack_id )
	{
		case 60000: //代理数据包
		{
			char char_main_fpm_proxy[ PROTO_SIZE_MAIN_FPM_PROXY ];
			read_result_pool.str = char_main_fpm_proxy;
			read_result_pool.max_pos = sizeof( char_main_fpm_proxy );
			proto_main_fpm_proxy_t *req_data = read_main_fpm_proxy( tmp_pack, &read_result_pool );
			if( read_result_pool.error_code > 0 )
			{
				return read_result_pool.error_code;
			}
			request_main_fpm_proxy( req_data );
		}
		break;
	}
	return 0;
}
