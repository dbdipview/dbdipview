class CreatedbWriter:
	"""	Create file: metadata/createdb.sql """

	def __init__(self, filename):
		self.name = filename
		self.comma = False
		#print("Creating", filename)
		self.f = open(filename, 'w', newline='', encoding="utf8")
		self.tables_added = []
		self.columns_added = []
		self.current_table_skip = False
		self.table_started = False
		hdr = \
"""--- This file has been generated by an automation tool configPrep
--- The content must be deleted if the database will be created using SIARD file(s)
"""
		self.print(hdr)
		

	def close(self):
		self.f.close()
		print("Created", self.name)


	def is_table_active(self):
		if self.current_table_skip:
			return False
		else:
			return True


	def begin_table(self, schema, table):
		table = "\"" + schema + "\".\"" + table + "\""
		if table in self.tables_added:
			self.current_table_skip = True
		else:
			self.f.write("\nCREATE TABLE " + table + " (\n")
			self.comma = False
			self.tables_added.append(table)
			self.current_table_skip = False
			self.table_started = True


	def end_table(self):
		self.columns_added = []
		if self.current_table_skip:
			return
		if self.table_started:
			self.f.write(");\n")
			self.table_started = False


	def get_all_tables(self):
		return self.tables_added


	def one_column(self, col, col_as, column_type, pk):
		if self.current_table_skip:
			return

		if col in self.columns_added or col == '*':
			print("   Skipping column: " + col)
			return
		self.columns_added.append(col)

		end = ""
		column_type = column_type.upper()
		if col_as.upper().startswith("DATE ") or col_as.upper().startswith("DATUM "):
			column_type = "DATE"

		if column_type == "DECIMAL" or column_type.startswith("INT"):
			column_type = "DECIMAL"
		elif column_type.startswith("NUMERIC"): 
			pass
		elif column_type.startswith("CHAR "):    #e.g. Char 10
			column_type = column_type.replace("CHAR ", "VARCHAR(")
			end = ")"
		elif column_type.startswith("VARCHAR("): 
			pass
		elif column_type.startswith("CHARACTER VARYING"): 
			pass
		elif column_type == "DATE": 
			pass
		elif column_type == "TIMESTAMP": 
			pass
		elif column_type:
			print("This type is not implemented yet: " + column_type)

		if self.comma == True:
			self.f.write("    , ")
		else:
			self.f.write("      ")
			self.comma = True

		if pk:
			spk = " PRIMARY KEY"
		else:
			spk = ""

		self.print("\"" + col + "\"  " + column_type + end + spk)


	def print(self, text):
		if text:
			self.f.write(text + "\n")
