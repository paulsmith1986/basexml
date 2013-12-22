#ifndef PROTO_SO_ENCODE_H
#define PROTO_SO_ENCODE_H
//打包数据并返回
#define php_pack_protocol_data( pack_id, data_arr, pack_name )										\
	char *error_msg = "No protocol data!";															\
	switch( pack_id )																				\
	{																								\
		case 20000:																					\
		{																							\
			if( NULL == data_arr )																	\
			{																						\
				pack_name.error_code = PROTO_PACK_DATA_MISS;										\
			}																						\
			else																					\
			{																						\
				sowrite_so_php_join( &pack_name, data_arr );										\
			}																						\
		}																							\
		break;																						\
		case 26001:																					\
		{																							\
			if( NULL == data_arr )																	\
			{																						\
				pack_name.error_code = PROTO_PACK_DATA_MISS;										\
			}																						\
			else																					\
			{																						\
				sowrite_fpm_ping( &pack_name, data_arr );											\
			}																						\
		}																							\
		break;																						\
		case 26005:																					\
		{																							\
			if( NULL == data_arr )																	\
			{																						\
				pack_name.error_code = PROTO_PACK_DATA_MISS;										\
			}																						\
			else																					\
			{																						\
				sowrite_fpm_ping_re( &pack_name, data_arr );										\
			}																						\
		}																							\
		break;																						\
		case 26002:																					\
		{																							\
			if( NULL == data_arr )																	\
			{																						\
				pack_name.error_code = PROTO_PACK_DATA_MISS;										\
			}																						\
			else																					\
			{																						\
				sowrite_fpm_join( &pack_name, data_arr );											\
			}																						\
		}																							\
		break;																						\
		case 26006:																					\
		{																							\
			if( NULL == data_arr )																	\
			{																						\
				pack_name.error_code = PROTO_PACK_DATA_MISS;										\
			}																						\
			else																					\
			{																						\
				sowrite_fpm_idle_report( &pack_name, data_arr );									\
			}																						\
		}																							\
		break;																						\
		default:																					\
			pack_name.error_code = PROTO_UNKOWN_PACK;												\
			zend_error( E_WARNING, "Unkown pack_id:%d", pack_id );									\
		break;																						\
	}
#endif