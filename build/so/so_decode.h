#ifndef PROTO_SO_DECODE_H
#define PROTO_SO_DECODE_H
//解包数据成php数组
#define php_unpack_protocol_data( pack_id, data_arr, tmp_result )									\
	switch( pack_id )																				\
	{																								\
		case 20001:																					\
		{																							\
			soread_so_php_join_re( data_arr, tmp_result );											\
		}																							\
		break;																						\
		case 60000:																					\
		{																							\
			soread_so_fpm_proxy( data_arr, tmp_result );											\
		}																							\
		break;																						\
		case 26001:																					\
		{																							\
			soread_fpm_ping( data_arr, tmp_result );												\
		}																							\
		break;																						\
		case 26005:																					\
		{																							\
			soread_fpm_ping_re( data_arr, tmp_result );												\
		}																							\
		break;																						\
		case 26002:																					\
		{																							\
			soread_fpm_join( data_arr, tmp_result );												\
		}																							\
		break;																						\
		case 26006:																					\
		{																							\
			soread_fpm_idle_report( data_arr, tmp_result );											\
		}																							\
		break;																						\
		case 26007:																					\
		{																							\
			soread_fpm_proxy( data_arr, tmp_result );												\
		}																							\
		break;																						\
		default:																					\
			zend_error( E_WARNING, "Unkown pack_id:%d", pack_id );									\
		break;																						\
	}
#endif