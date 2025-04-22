"""
Checks width of all columns in a CSV file
Also displays value of a column with largest length
Check number of '"' in a column, should be even
E.g. width=3, text="abc"
Also prepares input for configPrep tool, but you need to replace ; with TABs.

Analyzes the width of each column in a CSV file.
Identifies and displays the value with the maximum length in each column.
Verifies that the number of double quote characters ('"') is even in each column.
Prepares the data for the configPrep tool (replace ; to tab).
"""

import csv
import os.path
import tkinter as tk
from tkinter import filedialog


def check_files(filename, has_header):
	corrupted = []
	print("File: " + os.path.basename(filename))
	print("Header line: " + ("Yes" if has_header else "No"))
	check_width(filename, has_header)
	return corrupted


def check_delimiter(filename):
	ff = open(filename, encoding='utf-8')
	delimiters = ['\t', ',', ';', '|']
	for deli in delimiters:
		csv_reader = csv.reader(ff, delimiter=deli, quotechar='"')
		one_row = csv_reader.__next__()
		num_of_columns = len(one_row)
		if num_of_columns > 1:
			break
	return(deli)


def print_one_row(one_row):
	for one_column in one_row:
		print(one_column)


def check_width(filename, has_header):

	header = []
	deli = check_delimiter(filename)
	if deli == '\t':
		sdeli= "TAB"
	else:
		sdeli = deli
	print("Delimiter = " + sdeli)

	with open(filename, encoding='utf-8') as csvfile:
		longest_value_id = [0] * 100
		longest_value_line = [0] * 100
		columns = [0] * 100
		values = [0] * 100
		integers = [0] * 100
		i = 0
		while i < 100:
			integers[i] = True
			i = i + 1

		num_of_columns = 0
		current_line_num = -1

		csv_reader = csv.reader(csvfile, delimiter=deli, quotechar='"', quoting=csv.QUOTE_ALL)   #doublequote=False
		for one_row in csv_reader:
			current_line_num = current_line_num  + 1
			if current_line_num == 0:
				if has_header:
					header = one_row
					continue
				else:
					header = [""] * len(one_row)
			i = 0
			if num_of_columns == 0:
				num_of_columns = len(one_row)
				print("Line " + str(current_line_num) + ", columns: " + str(num_of_columns))
			else:
				if num_of_columns != len(one_row):
					print("Line " + str(current_line_num) + \
						", ERROR, number of detected columns is " + str(len(one_row)))
					print("        In this line:")
					print_one_row(one_row)
					exit()

			for one_column_value in one_row:
				length = len(one_column_value)
				if length > columns[i]:
					columns[i] = length
					values[i] = one_column_value
					longest_value_id[i] = one_row[0]
					longest_value_line[i] = current_line_num 
				if one_column_value != "":
					if one_column_value.isdigit()==False and integers[i] == True:
						integers[i] = False

					x = one_column_value.count('\"')
					if sdeli != "TAB" and x > 0 and x % 2 == 1:
						print("Line " + str(current_line_num) + ", ERROR, odd number of '\"' characters")
						print("        In this line:")
						print(one_column_value)
						exit()

				#DISPLAY A CERTAIN LINE (or set -1 to disable)
				if current_line_num == -1:
					print(f"***Line {current_line_num}, column={i}, value: {str(one_column_value)[0:30]}")

				#DISPLAY A CERTAIN COLUMN for each lines (or set -1 to disable)
				if i == -1:
					print(f"###Line {current_line_num}, value: {str(one_column_value)[0:30]}")

				i = i + 1
	
	print("Number of lines: " + str(current_line_num))
	print("=== Max. column width ===")
	i = 0
	my_char_prefix=''
	my_char='A'
	while i < num_of_columns:
		print(header[i] + ":" + my_char_prefix + str(my_char) + " "+ str(i+1) + ": " + str(columns[i]))
		i = i + 1
		my_char = chr(ord(my_char) + 1)
		if my_char == '[':
			my_char_prefix='A'
			my_char='A'


	print("=== Values in column with max. width (to check for possible error), ID=1st column ===")
	i = 0
	while i < num_of_columns:
		if integers[i] == True:
			iint = "Int "
		else:
			iint = "CHAR"

		print(str(i+1).ljust(2, ' ') + " " + \
			iint + \
			", W=" + str(columns[i]).ljust(5, ' ') + \
			": ID=" + str(longest_value_id[i]) + \
			", text=" + str(values[i])[0:70].replace("\n"," "))

		i = i + 1

	print("=== BEGIN Table columns for configPrep ===")
	print("SelectDescription:")
	print("Title:")
	print("Subtitle:")
	print("Schema:")
	print("Table:")
	print("CSV file:;" + os.path.basename(filename))
	print("")

	i = 0
	while i < num_of_columns:
		if integers[i] == True:
			iint = "Int "
		else:
			iint = "CHAR"

		print(";" + str(header[i]) + ";" + str(header[i]) + ";" + iint + " " + str(columns[i]))
		i = i + 1

	print("")
	print("=== END Table columns for configPrep ===")

initialdir =  ""
output_folder = ""

initialdir = tk.filedialog.askopenfilename(
	initialdir = os.path.dirname(output_folder),
	title = "Select a CSV file")

if not initialdir:
	quit()

has_header = tk.messagebox.askyesno("Decision", "Is first line a header line?")

check_files(initialdir, has_header)
