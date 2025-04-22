"""
Use settings from the last session
"""

import os.path
from pathlib import Path

cookiefile = "cookie.dat"

def get_cookie():
	my_file = Path(cookiefile)
	mydir = ""
	myft = ""
	output_folder = '.'

	if my_file.is_file():
		with open(cookiefile) as f:
			dir = f.readline()
			mydir = dir.rstrip()
			if not mydir:
				mydir = os.path.expanduser('~/')

			filetypeLine = f.readline()
			myft = filetypeLine.rstrip()
			if not myft in ("TXT", "CSV", "XLSX", "XLS", "SIARD"):
				myft = "any"

			recordsfolder = f.readline()
			output_folder = recordsfolder.rstrip()
			if not os.path.isdir(output_folder):
				output_folder = '.'

	return(mydir, myft, output_folder)


def set_cookie(dir, filetyp, output_folder):
	with open(cookiefile, "w") as myfile:
		#print(dir)
		print(dir, file=myfile)
		#print(filetyp)
		print(filetyp, file=myfile)
		#print(output_folder)
		print(output_folder, file=myfile)
