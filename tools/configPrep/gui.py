import tkinter as tk
from tkinter import filedialog
from tkinter import ttk
import os

class Gui():
	
	language = ""
	create_tables = True
	header = """Input can be:
- an XLSX file, or
- one or more SIARD files, or
- one or more metadata.xml already extracted from SIARD.
If XLSX has been generated from SIARD files, it is advisable that you 
manually edit it and re-run this program with improved content. \n\n"""


	def __init__(self):
		self.root = tk.Tk()
		self.root.title("dbDIPview configuration files creator")
		self.root.geometry('800x650+300+100')   # width,height,position xy)

		self.S = tk.Scrollbar(self.root)

		self.L = tk.Label(self.root, text="queries.xml control reports language (for titles and subtitles)")
		self.L.pack()
		self.options = ["EN", "SL"]
		self.selected_option = tk.StringVar()
		self.selected_option.set(self.options[0])

		self.D = ttk.Combobox(self.root, textvariable=self.selected_option, values=self.options)
		self.D.pack(pady=10)

		#self.create_tables_var = tk.BooleanVar()
		#self.create_tables_var.set(False)
		#self.create_tables = tk.Checkbutton(self.root, text="Create createdb.sql (e.g. when no SIARD is used)", variable=self.create_tables_var)
		#self.create_tables.pack(pady=10)

		self.submit_button = tk.Button(self.root, text="Continue", command=self.submit)
		self.submit_button.pack(pady=10)

		self.T = tk.Text(self.root, height=90, width=100, font=("courier") )
		self.T.config(yscrollcommand=self.S.set)
		self.T.insert(tk.END, Gui.header)
		self.T.pack(side=tk.LEFT, fill=tk.Y)
		
		self.S.config(command=self.T.yview)
		self.S.pack(side=tk.RIGHT, fill=tk.Y)

		self.root.update()
		self.root.mainloop()


	def addText(self, s):
		self.T.insert(tk.END, s)


	def submit(self):
		Gui.language = self.selected_option.get()
		#Gui.create_tables = self.create_tables_var.get()
		self.root.quit() 


	def ask_filename(self, filetypes, title, dir):
		files = tk.filedialog.askopenfilenames(
			filetypes = filetypes,
			title = title,
			initialdir= dir )
		return(files)


	def ask_folder(self, outputFolder, title):
		folder = tk.filedialog.askdirectory(
			initialdir = os.path.dirname(outputFolder),
			title = title)
		if not folder:
				quit()
		return(folder)


	def get_language(self):
		dict =  {
			"EN": 1,
			"SL": 2
		}
		return(dict[Gui.language])


	def get_need_to_create_tables(self):
		return(Gui.create_tables)
