#ifndef YGP_PROTOCOL_SO_H
#define YGP_PROTOCOL_SO_H
#include <stdint.h>
#include <stdlib.h>
#include "yile_proto.h"
#include "php.h"
#include "proto_size.h"
#pragma pack(1)

typedef struct proto_so_fpm_ping_t proto_so_fpm_ping_t;

typedef struct proto_so_fpm_ping_re_t proto_so_fpm_ping_re_t;

typedef struct proto_so_fpm_join_t proto_so_fpm_join_t;

typedef struct proto_so_fpm_idle_report_t proto_so_fpm_idle_report_t;

typedef struct proto_so_fpm_proxy_t proto_so_fpm_proxy_t;
//ping包
struct proto_so_fpm_ping_t{
	uint32_t											time;				//时间
};
//ping返回包
struct proto_so_fpm_ping_re_t{
	uint32_t											time;				//时间
};
//fpm进程加入server
struct proto_so_fpm_join_t{
	uint32_t											pid;				//进程id
	uint16_t											fpm_id;				//PHP进程号
};
//空闲状态通知
struct proto_so_fpm_idle_report_t{
	uint16_t											fpm_id;				//PHP进程号
};
//数据代理
struct proto_so_fpm_proxy_t{
	int32_t												session_id;			//会话id
	proto_bin_t*										data;				//代理数据
};
#pragma pack()

/**
 * 生成 ping包
 */
void sowrite_fpm_ping( protocol_result_t *all_result, HashTable *data_hash );

/**
 * 生成 ping返回包
 */
void sowrite_fpm_ping_re( protocol_result_t *all_result, HashTable *data_hash );

/**
 * 生成 fpm进程加入server
 */
void sowrite_fpm_join( protocol_result_t *all_result, HashTable *data_hash );

/**
 * 生成 空闲状态通知
 */
void sowrite_fpm_idle_report( protocol_result_t *all_result, HashTable *data_hash );

/**
 * 生成 数据代理
 */
void sowrite_fpm_proxy( protocol_result_t *all_result, HashTable *data_hash );

/**
 * 解析 ping包
 */
void soread_fpm_ping( protocol_packet_t *byte_pack, zval *result_arr );

/**
 * 解析 ping返回包
 */
void soread_fpm_ping_re( protocol_packet_t *byte_pack, zval *result_arr );

/**
 * 解析 fpm进程加入server
 */
void soread_fpm_join( protocol_packet_t *byte_pack, zval *result_arr );

/**
 * 解析 空闲状态通知
 */
void soread_fpm_idle_report( protocol_packet_t *byte_pack, zval *result_arr );

/**
 * 解析 数据代理
 */
void soread_fpm_proxy( protocol_packet_t *byte_pack, zval *result_arr );
#endif