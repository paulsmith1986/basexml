#ifndef PROTOCOL_ENCODE_DATA_H
#define PROTOCOL_ENCODE_DATA_H

//连接服务器 将原始数据转换成tcp包数据
#define encode_so_php_join( pack_name, data )														\
	char WRITE_SO_PHP_JOIN[ PROTO_SIZE_SO_PHP_JOIN ];												\
	protocol_result_t pack_name;																	\
	pack_name.pos = 0;																				\
	pack_name.error_code = 0;																		\
	pack_name.is_resize = 0;																		\
	pack_name.str = WRITE_SO_PHP_JOIN;																\
	pack_name.max_pos = PROTO_SIZE_SO_PHP_JOIN;														\
	write_so_php_join( &pack_name, data )

//ping包 将原始数据转换成tcp包数据
#define encode_fpm_ping( pack_name, data )															\
	char WRITE_FPM_PING[ PROTO_SIZE_FPM_PING ];														\
	protocol_result_t pack_name;																	\
	pack_name.pos = 0;																				\
	pack_name.error_code = 0;																		\
	pack_name.is_resize = 0;																		\
	pack_name.str = WRITE_FPM_PING;																	\
	pack_name.max_pos = PROTO_SIZE_FPM_PING;														\
	write_fpm_ping( &pack_name, data )

//ping返回包 将原始数据转换成tcp包数据
#define encode_fpm_ping_re( pack_name, data )														\
	char WRITE_FPM_PING_RE[ PROTO_SIZE_FPM_PING_RE ];												\
	protocol_result_t pack_name;																	\
	pack_name.pos = 0;																				\
	pack_name.error_code = 0;																		\
	pack_name.is_resize = 0;																		\
	pack_name.str = WRITE_FPM_PING_RE;																\
	pack_name.max_pos = PROTO_SIZE_FPM_PING_RE;														\
	write_fpm_ping_re( &pack_name, data )

//fpm进程加入server 将原始数据转换成tcp包数据
#define encode_fpm_join( pack_name, data )															\
	char WRITE_FPM_JOIN[ PROTO_SIZE_FPM_JOIN ];														\
	protocol_result_t pack_name;																	\
	pack_name.pos = 0;																				\
	pack_name.error_code = 0;																		\
	pack_name.is_resize = 0;																		\
	pack_name.str = WRITE_FPM_JOIN;																	\
	pack_name.max_pos = PROTO_SIZE_FPM_JOIN;														\
	write_fpm_join( &pack_name, data )

//空闲状态通知 将原始数据转换成tcp包数据
#define encode_fpm_idle_report( pack_name, data )													\
	char WRITE_FPM_IDLE_REPORT[ PROTO_SIZE_FPM_IDLE_REPORT ];										\
	protocol_result_t pack_name;																	\
	pack_name.pos = 0;																				\
	pack_name.error_code = 0;																		\
	pack_name.is_resize = 0;																		\
	pack_name.str = WRITE_FPM_IDLE_REPORT;															\
	pack_name.max_pos = PROTO_SIZE_FPM_IDLE_REPORT;													\
	write_fpm_idle_report( &pack_name, data )

#endif
