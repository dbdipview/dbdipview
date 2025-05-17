#!/usr/bin/env python
"""
Checks if files in the selected folder are UTF-8
"""

import codecs
import os.path
import tkinter as tk
from tkinter import filedialog


def check_folder(path):
	print("Checking folder "+path)
	corrupted = []
	for dirpath, _, filenames in os.walk(path):
		for filename in filenames:
			if filename.lower().endswith(('.csv', '.tsv', '.utf8', '.txt')):
				img_path = os.path.join(dirpath, filename)
				if check_file(img_path):
					print(f"{filename} ok")
				else:
					print(f"{filename} invalid utf-8 !")
			else:
				print(f"...skipping {filename}")
	return corrupted

def check_file(filename):
	try:
		f = codecs.open(filename, encoding='utf-8', errors='strict')
		for line in f.readlines():
			pass
		return True
	except UnicodeDecodeError:
		return False

initialdir =  ""
outputFolder = ""

initialdir = tk.filedialog.askdirectory(
	initialdir = os.path.dirname(outputFolder),
	title = "Input folder")

check_folder(initialdir)
