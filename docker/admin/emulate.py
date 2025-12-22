#!/usr/bin/python3

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By

import time

options = webdriver.FirefoxOptions()
options.add_argument("--headless")
driver = webdriver.Firefox(options=options)
driver.get("http://tudo-app/login.php")

u_input = driver.find_element(By.NAME, "username")
p_input = driver.find_element(By.NAME, "password")
u_input.send_keys("admin")
p_input.send_keys("admin")
p_input.send_keys(Keys.RETURN)

time.sleep(5)

driver.close()