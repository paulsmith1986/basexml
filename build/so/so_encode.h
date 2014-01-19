#ifndef PROTO_SO_ENCODE_H
#define PROTO_SO_ENCODE_H
//打包数据并返回
#define add_proto_map_pack_func()																	\
{
	add_proto_pack_map( 26001, sowrite_fpm_ping );													\
	add_proto_pack_map( 26005, sowrite_fpm_ping_re );												\
	add_proto_pack_map( 26002, sowrite_fpm_join );													\
	add_proto_pack_map( 26006, sowrite_fpm_idle_report );											\
	add_proto_pack_map( 26007, sowrite_fpm_proxy );													\
}
#endif