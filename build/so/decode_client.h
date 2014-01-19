#ifndef PROTOCOL_DECODE_DATA_H
#define PROTOCOL_DECODE_DATA_H
#define is_decode_error() 0 != _proto_read_result
//代理数据包 读取网络层数据转换成原始数据
#define read_and_decode_main_fpm_proxy( fd, var_name )												\
	char TCP_MAIN_FPM_PROXY[ 6144 ];																\
	protocol_packet_t _tmp_buff_pack;																\
	_tmp_buff_pack.pos = 0;																			\
	_tmp_buff_pack.max_pos = sizeof( packet_head_t );												\
	_tmp_buff_pack.pool_size = 6144;																\
	_tmp_buff_pack.is_resize = 0;																	\
	_tmp_buff_pack.data = TCP_MAIN_FPM_PROXY;														\
	yile_net_get_data( fd, &_tmp_buff_pack );														\
	int _proto_read_result = 0;																		\
	proto_main_fpm_proxy_t *var_name = NULL;														\
	if ( 0 == _tmp_buff_pack.max_pos )																\
	{																								\
		_proto_read_result = PROTO_READ_NET_DATA_ERROR;												\
	}																								\
	else																							\
	{																								\
		packet_head_t *pack_head = ( packet_head_t* )&TCP_MAIN_FPM_PROXY[ 0 ];						\
		if ( 60000 != pack_head->pack_id )															\
		{																							\
			_proto_read_result = PROTO_READ_PACK_ID_ERROR;											\
		}																							\
		else																						\
		{																							\
			_tmp_buff_pack.pos = sizeof( packet_head_t );											\
			char READ_MAIN_FPM_PROXY[ 6144 ];														\
			protocol_result_t _tmp_result_pack;														\
			_tmp_result_pack.pos = 0;																\
			_tmp_result_pack.str = READ_MAIN_FPM_PROXY;												\
			_tmp_result_pack.error_code = 0;														\
			_tmp_result_pack.max_pos = 6144;														\
			var_name = read_main_fpm_proxy( &_tmp_buff_pack, &_tmp_result_pack );					\
			if( _tmp_result_pack.error_code > 0 )													\
			{																						\
				_proto_read_result = _tmp_result_pack.error_code;									\
			}																						\
		}																							\
	}																								\
	if( _tmp_buff_pack.is_resize )																	\
	{																								\
		free( _tmp_buff_pack.data );																\
	}

#endif
