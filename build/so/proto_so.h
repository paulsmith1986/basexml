#ifndef YGP_PROTOCOL_SO_H
#define YGP_PROTOCOL_SO_H
#include <stdint.h>
#include <stdlib.h>
#include "yile_proto.h"
#include "php.h"
#include "proto_size.h"
#pragma pack(1)

typedef struct proto_so_so_php_join_re_t proto_so_so_php_join_re_t;

typedef struct proto_so_so_php_join_t proto_so_so_php_join_t;

typedef struct proto_so_so_fpm_proxy_t proto_so_so_fpm_proxy_t;

typedef struct proto_so_fpm_ping_t proto_so_fpm_ping_t;

typedef struct proto_so_fpm_ping_re_t proto_so_fpm_ping_re_t;

typedef struct proto_so_fpm_join_t proto_so_fpm_join_t;

typedef struct proto_so_fpm_idle_report_t proto_so_fpm_idle_report_t;
//PHP加入服务器返回
struct proto_so_so_php_join_re_t{
	uint32_t											result;				//加入结果
};
//连接服务器
struct proto_so_so_php_join_t{
	int8_t												socket_type;		//连接类型
};
//代理数据包
struct proto_so_so_fpm_proxy_t{
	uint32_t											role_id;			//用户id
	uint32_t											session_id;			//会话id
	proto_bin_t*										data;				//转发数据包
};
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
#pragma pack()

/**
 * 生成 连接服务器
 */
void sowrite_so_php_join( protocol_result_t *all_result, HashTable *data_hash );

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
 * 解析 PHP加入服务器返回
 */
void soread_so_php_join_re( protocol_packet_t *byte_pack, zval *result_arr );

/**
 * 解析 代理数据包
 */
void soread_so_fpm_proxy( protocol_packet_t *byte_pack, zval *result_arr );

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
#endif