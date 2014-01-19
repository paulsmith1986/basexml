#ifndef PROTO_SO_DECODE_H
#define PROTO_SO_DECODE_H
//解包数据成php数组
#define add_proto_map_unpack_func()																	\
{
	add_proto_unpack_map( 26001, soread_fpm_ping );													\
	add_proto_unpack_map( 26005, soread_fpm_ping_re );												\
	add_proto_unpack_map( 26002, soread_fpm_join );													\
	add_proto_unpack_map( 26006, soread_fpm_idle_report );											\
	add_proto_unpack_map( 26007, soread_fpm_proxy );												\
}
#endif