#ifndef PROTOCOL_POOL_SIZE_HEAD
#define PROTOCOL_POOL_SIZE_HEAD
//ping包 解析时占内存
#define PROTO_SIZE_FPM_PING sizeof( proto_fpm_ping_t ) + sizeof( packet_head_t )
//ping返回包 解析时占内存
#define PROTO_SIZE_FPM_PING_RE sizeof( proto_fpm_ping_re_t ) + sizeof( packet_head_t )
//fpm进程加入server 解析时占内存
#define PROTO_SIZE_FPM_JOIN sizeof( proto_fpm_join_t ) + sizeof( packet_head_t )
//空闲状态通知 解析时占内存
#define PROTO_SIZE_FPM_IDLE_REPORT sizeof( proto_fpm_idle_report_t ) + sizeof( packet_head_t )

#endif
