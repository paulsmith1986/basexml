#ifndef YILE_PROTOCOL_CLIENT_H
#define YILE_PROTOCOL_CLIENT_H
#include <stdint.h>
#include <stdlib.h>
#include "yile_protocol.h"
#pragma pack(1)

typedef struct proto_main_fpm_proxy_t proto_main_fpm_proxy_t;
//代理数据包
struct proto_main_fpm_proxy_t{
	uint32_t											hash_id;			//hashid
	uint32_t											role_id;			//用户id
	uint32_t											session_id;			//会话id
	proto_bin_t*										data;				//转发数据包
};
#pragma pack()

/**
 * 解析 代理数据包
 */
proto_main_fpm_proxy_t *read_main_fpm_proxy( protocol_packet_t *byte_pack, protocol_result_t *result_pool);
#ifdef PROTOCOL_DEBUG

/**
 * 打印 代理数据包
 */
void print_main_fpm_proxy( proto_main_fpm_proxy_t *re );
#endif
#endif