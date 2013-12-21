#ifndef YILE_PROTOCOL_REQESUT_TASK_H
#define YILE_PROTOCOL_REQESUT_TASK_H
#include "proto_bin.h"
#include "encode_client.h"
#include "decode_client.h"
#include "proto_size.h"
//尝试释放读取网络数据时申请的内存
#define try_free_proto_pack( tmp_pack ) if( tmp_pack.is_resize ) free( tmp_pack.data )
//主任务线程
int do_request_task ( protocol_packet_t *tmp_pack );

/**
 * pack_id: 20001 PHP加入服务器返回
 */
void request_so_php_join_re( proto_so_php_join_re_t *req_pack );

/**
 * pack_id: 60000 代理数据包
 */
void request_so_fpm_proxy( proto_so_fpm_proxy_t *req_pack );
#endif
