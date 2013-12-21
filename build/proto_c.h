#ifndef YILE_PROTOCOL_CLIENT_H
#define YILE_PROTOCOL_CLIENT_H
#include <stdint.h>
#include <stdlib.h>
#include "yile_protocol.h"
#pragma pack(1)

typedef struct proto_so_php_join_re_t proto_so_php_join_re_t;

typedef struct proto_so_php_join_t proto_so_php_join_t;

typedef struct proto_so_fpm_proxy_t proto_so_fpm_proxy_t;

typedef struct proto_fpm_ping_t proto_fpm_ping_t;

typedef struct proto_fpm_ping_re_t proto_fpm_ping_re_t;

typedef struct proto_fpm_join_t proto_fpm_join_t;

typedef struct proto_fpm_idle_report_t proto_fpm_idle_report_t;
//PHP加入服务器返回
struct proto_so_php_join_re_t{
	uint32_t											result;				//加入结果
};
//连接服务器
struct proto_so_php_join_t{
	int8_t												socket_type;		//连接类型
};
//代理数据包
struct proto_so_fpm_proxy_t{
	uint32_t											role_id;			//用户id
	uint32_t											session_id;			//会话id
	proto_bin_t*										data;				//转发数据包
};
//ping包
struct proto_fpm_ping_t{
	uint32_t											time;				//时间
};
//ping返回包
struct proto_fpm_ping_re_t{
	uint32_t											time;				//时间
};
//fpm进程加入server
struct proto_fpm_join_t{
	uint32_t											pid;				//进程id
	uint16_t											fpm_id;				//PHP进程号
};
//空闲状态通知
struct proto_fpm_idle_report_t{
	uint16_t											fpm_id;				//PHP进程号
};
#pragma pack()

/**
 * 生成 连接服务器
 */
void write_so_php_join( protocol_result_t *all_result, proto_so_php_join_t *data_arr );

/**
 * 生成 ping包
 */
void write_fpm_ping( protocol_result_t *all_result, proto_fpm_ping_t *data_arr );

/**
 * 生成 ping返回包
 */
void write_fpm_ping_re( protocol_result_t *all_result, proto_fpm_ping_re_t *data_arr );

/**
 * 生成 fpm进程加入server
 */
void write_fpm_join( protocol_result_t *all_result, proto_fpm_join_t *data_arr );

/**
 * 生成 空闲状态通知
 */
void write_fpm_idle_report( protocol_result_t *all_result, proto_fpm_idle_report_t *data_arr );

/**
 * 解析 PHP加入服务器返回
 */
proto_so_php_join_re_t *read_so_php_join_re( protocol_packet_t *byte_pack, protocol_result_t *result_pool);

/**
 * 解析 代理数据包
 */
proto_so_fpm_proxy_t *read_so_fpm_proxy( protocol_packet_t *byte_pack, protocol_result_t *result_pool);

/**
 * 解析 ping包
 */
proto_fpm_ping_t *read_fpm_ping( protocol_packet_t *byte_pack, protocol_result_t *result_pool);

/**
 * 解析 ping返回包
 */
proto_fpm_ping_re_t *read_fpm_ping_re( protocol_packet_t *byte_pack, protocol_result_t *result_pool);

/**
 * 解析 fpm进程加入server
 */
proto_fpm_join_t *read_fpm_join( protocol_packet_t *byte_pack, protocol_result_t *result_pool);

/**
 * 解析 空闲状态通知
 */
proto_fpm_idle_report_t *read_fpm_idle_report( protocol_packet_t *byte_pack, protocol_result_t *result_pool);
#ifdef PROTOCOL_DEBUG

/**
 * 打印 PHP加入服务器返回
 */
void print_so_php_join_re( proto_so_php_join_re_t *re );

/**
 * 打印 代理数据包
 */
void print_so_fpm_proxy( proto_so_fpm_proxy_t *re );

/**
 * 打印 ping包
 */
void print_fpm_ping( proto_fpm_ping_t *re );

/**
 * 打印 ping返回包
 */
void print_fpm_ping_re( proto_fpm_ping_re_t *re );

/**
 * 打印 fpm进程加入server
 */
void print_fpm_join( proto_fpm_join_t *re );

/**
 * 打印 空闲状态通知
 */
void print_fpm_idle_report( proto_fpm_idle_report_t *re );
#endif
#endif