"""
Create initial version for metadata folder files of a dbDIPview package

Input:
	xlsx (see example) or SIARD 
	With SIARD, and xlsx file will be created. Edit it and then rerun the program.
Output:
	createdb.sql
	createdb01.sql
	list.xml
	queries.xml
"""

import csv
import openpyxl
import os.path
from gui import Gui

import myCookies
import fill_createdb
import fill_createdb01
import control_reports

import parse_input as ctaq
import create_list_xml
import loop_siard_files


class XML_query_writer:

	def __init__(self, filename):
		self.name = filename
		#print("Creating ", filename)
		self.f = open(filename, 'w', newline='', encoding="utf8")


	def write(self, s):
		self.f.write(s)


	def close(self):
		self.f.close()
		print("Created", self.name)


	def elements_subselect(self, table, title, notFirstQuery):
		out = """
				</query>"""
		if notFirstQuery:
			out += """
			</subselect>"""
		out += """

				<subselect>
					<title>""" + title + """</title>
					<subtitle>(tabel: """ + table + """)</subtitle>"""

		self.f.write(out)


def getDelimiter(file):
	csv_file = open(file)
	csv_reader = csv.reader(csv_file, 1000, delimiter='\t', quotechar='"')
	row = next(csv_reader)
	numOfColumns = len(row)
	csv_file.close()

	if numOfColumns > 1:
		print(f"	Delimiter: TAB")
		return '\t'
	else:
		print(f"	Delimiter: ,")
		return ','


def getTableTitle(value):
	currentTable = value.split('.')[0]

	bTitleFound = value.find(".DBF - ")
	if bTitleFound > 0:
		title = value[bTitleFound + 7:].lower()
		title = title.capitalize()
	else:
		title = value

	return currentTable, title

gr = Gui()

initialdir, filetype, outputFolder = myCookies.get_cookie()

if filetype == "XLSX":
	filetypes = [("Excel files","*.xlsx"),  ("SIARD files","*.siard"), ("All files","*.*")]
elif filetype == "SIARD":
	filetypes = [("SIARD files","*.siard"), ("Excel files","*.xlsx"),  ("All files","*.*")]
else:
	filetypes = [("All files","*.*"), ("SIARD files","*.siard"), ("Excel files","*.xlsx")]

use_cookie = True
use_siard = False

if use_cookie:
	language = gr.get_language()

	inputFiles = gr.ask_filename(filetypes, "Select input file (.xlsx) or SIARD files", initialdir )
	if not inputFiles:
		quit()

	for f in inputFiles:
		if f.endswith(".siard") or f.endswith(".SIARD"):
			use_siard = True
		gr.addText("Input file: " + f + "\n")

	outputFolder = gr.ask_folder(outputFolder, "Output folder")

	if not isinstance(outputFolder, str):
		quit()
	
	gr.addText("Output folder: " + outputFolder + "\n")
	
	filetype = ""
	inputFile = inputFiles[0]
	inputFolder = os.path.dirname(inputFile)

	if use_siard:
		filetype = "SIARD"
	else:
		if inputFile.endswith(".xlsx"):
			filetype = "XLSX"
		elif inputFile.endswith(".xml"):
			filetype = "XML"
			use_siard = True

	if not filetype:
		print("ERROR. Supported file types are .XLSX, .SIARD")
		quit()

	myCookies.set_cookie(inputFolder, filetype, outputFolder)


create_tables = gr.get_need_to_create_tables()

target_from_siard = inputFolder + "/input_from_siard.xlsx"
if use_siard:
	loop_siard_files.loop_siard_files(inputFiles, target_from_siard, outputFolder)
	inputFile = target_from_siard
	create_tables = False

print("Parsing " + inputFile)
sheets = [0]
my_XML_query_writer =  XML_query_writer(outputFolder+"/queries.xml")
my_createdb_writer =   fill_createdb.CreatedbWriter(outputFolder+"/createdb.sql")
my_createdb01_writer = fill_createdb01.Createdb01Writer(outputFolder+"/createdb01.sql")

my_XML_control_writer = control_reports.ControlReports(inputFile, my_XML_query_writer, language)
my_XML_control_writer.header()

wb = openpyxl.load_workbook(inputFile)
num_of_sheets = len(wb.sheetnames)
wb.close()

sheet_index = 0
while sheet_index < num_of_sheets:
	print(" Excel sheet: " + str(sheet_index) + " *******************")
	ctaq.xlsc2queriesxml(inputFile, sheet_index, my_XML_query_writer, my_createdb_writer, my_createdb01_writer, 1)
	ctaq.xlsc2queriesxml(inputFile, sheet_index, my_XML_query_writer, my_createdb_writer, my_createdb01_writer, 2)
	sheet_index = sheet_index + 1

my_XML_control_writer.parse(my_createdb_writer.get_all_tables())
my_XML_control_writer.tail()

my_XML_query_writer.close()

if create_tables:
		for s in ctaq.foreign_keys:
			my_createdb_writer.print(s)
my_createdb_writer.close()
my_createdb01_writer.close()

create_list_xml.create_list_xml(outputFolder + "/list.xml", ctaq)
if use_siard:
	print("SIARD format has been used for input. Edit the intermediate file: \n  " + \
	   	target_from_siard + \
		"\n  and rerun the program to get better result!")