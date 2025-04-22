import csv_output as fl
from datetime import date
import xml.etree.ElementTree as ET
import zipfile

class SiardWorker:
	"""Simple parser of SIARD metadata XML file
	Input: SIARD file or metadata.xml
	Output: CSV file
	"""

	current_states = ["init","schemas","schema","tables","table","columns","column","x1","x2","x3"]
	current_state = 0


	def __init__(self, input_xml_or_siard_file, output_folder, id_postfix):
		if input_xml_or_siard_file.endswith(".xml"):
			self.input_xml_file = input_xml_or_siard_file
			self.input_siard_file = ""
		else:
			self.input_xml_file = ""
			self.input_siard_file = input_xml_or_siard_file

		self.id_postfix = id_postfix
		#print(f"  worker Input file={input_xml_or_siard_file}")
		#print(f"  worker Output folder={output_folder}")
		self.out_engine = fl.CsvWriter(input_xml_or_siard_file, output_folder)
		self.output_file = self.out_engine.get_output_file()
		self.today = date.today()
		self.dateYYYYMMDD = self.today.strftime("%Y-%m-%d")
		self.dateDDMMYYYY = self.today.strftime("%d.%m.%Y")


	def get_output_file(self):
		return self.output_file


	def utf8_converter(self, bom_file):
		#Remove UTF-8 BOM
		s = open(bom_file, mode='r', encoding='utf-8-sig').read()
		open(bom_file, mode='w', encoding='utf-8').write(s)
		return 0


	def get_xml_name(self, xml_string):
		try:
			root = ET.fromstring(xml_string)
			element_value = root.text
			return element_value
		except ET.ParseError:
			print("Error parsing XML: Invalid XML format")
			return None


	def parse(self):

		if self.input_xml_file:
			file1 = open(self.input_xml_file, 'r', encoding='utf-8')
			Lines = file1.readlines()
		else:
			try:
				zip_ref = zipfile.ZipFile(self.input_siard_file, 'r')
				file1 = zip_ref.open("header/metadata.xml", 'r')
				content = file1.read()
				Lines = content.decode('utf-8').splitlines()
			except KeyError:
				raise KeyError(f"Metadata file not found in the SIARD file.")
				exit()

		count = 0
		table_name_known = False
		foreign_keys_element = False
		views_element = False
		select_id = 1

		current_schema = ""
		current_column = ""
		for linen in Lines:
			line = linen.rstrip('\n')
			count += 1
			line_stripped = line.strip()
			if (views_element or foreign_keys_element):
				#print("*", end="")
				pass
			#print("Current state=" + self.current_states[self.current_state] + "   " + line_stripped)
			
			if   line_stripped.startswith("<schemas>"):
				self.current_state += 1
			elif line_stripped.startswith("</schemas>"):
				self.current_state -= 1
			elif line_stripped.startswith("<schema>"):
				self.current_state += 1
			elif line_stripped.startswith("<name>") and \
				self.current_states[self.current_state] == "schema" and \
				not foreign_keys_element and \
				not views_element:
				current_schema = self.get_xml_name(line_stripped)
				if 	select_id == 1:
					self.out_engine.out(""      + "\t" + "" + 10*"\t")
					self.out_engine.out("Id:"   + "\t" + str(select_id)+self.id_postfix + 10*"\t")
					self.out_engine.out("SelectDescription:" + "\t" + "" + 10*"\t")
				select_id += 1
				self.out_engine.out(""                   + "\t" + "" + 10*"\t")
				#print("SCHEMA:" + self.get_xml_name(line_stripped))
				#print("     " + current_schema + "   " + line_stripped)
			elif line_stripped.startswith("</schema>"):
				self.current_state -= 1
			elif line_stripped.startswith("<tables>"):
				self.current_state += 1
			elif line_stripped.startswith("</tables>"):
				self.current_state -= 1
			elif line_stripped.startswith("<table>"):
				self.current_state += 1
				table_name_known = False
			elif line_stripped.startswith("<name>") and \
				self.current_states[self.current_state] == "table" and \
				not table_name_known and \
				not foreign_keys_element and \
				not views_element:
				table_name_known = True
				self.out_engine.out("Title:"    + "\t" + "[" + self.get_xml_name(line_stripped) + "]" + 10*"\t")
				self.out_engine.out("Subtitle:" + "\t" + ""                                           + 10*"\t")
				self.out_engine.out("Schema:"   + "\t" + current_schema + 10*"\t")
				self.out_engine.out("Table:"    + "\t" + self.get_xml_name(line_stripped)             + 10*"\t")
				self.out_engine.out("CSV file:" + "\t" + ""                                           + 10*"\t")

				self.out_engine.out("\t" + 
					"db column" + "\t" + 
					"column name" + "\t" + 
					"type" + "\t" + 
					"primary" + "\t" + 
					"dictionary table" + "\t" + 
					"dictionary key" + "\t" + 
					"dictionary value" + "\t" + 
					"search field label" + "\t" + 
					"link_to_next_column" + "\t" + 
					"comment on column")

			elif line_stripped.startswith("</table>"):
				self.out_engine.out(""          + "\t" + "" + 10*"\t")
				self.current_state -= 1
			elif line_stripped.startswith("<columns>") and \
				not views_element: 
				self.current_state += 1
			elif line_stripped.startswith("</columns>") and \
				not views_element: 
				self.current_state -= 1
			elif line_stripped.startswith('<column>') and \
				not foreign_keys_element and \
				not views_element:
				self.current_state += 1
			elif line_stripped.startswith('<name>') and \
				self.current_states[self.current_state] =="column" and \
				not foreign_keys_element and \
				not views_element:
				current_column = self.get_xml_name(line_stripped)
			elif line_stripped.startswith('<type>') and self.current_states[self.current_state] =="column":
				self.out_engine.out("\t" + 
					current_column + "\t" + 
					"" + "\t" + 
					self.get_xml_name(line_stripped) + "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					""+ "\t" + 
					"")
			elif line_stripped.startswith("</column>") and \
				not foreign_keys_element and \
				not views_element:
				self.current_state -= 1
			elif line_stripped.startswith("<foreignKeys>") or \
				line_stripped.startswith("<candidateKeys>") or \
				line_stripped.startswith("<checkConstraints>") or \
				line_stripped.startswith("<primaryKey>")  or \
				line_stripped.startswith("<views>"):
				foreign_keys_element = True
			elif line_stripped.startswith("</foreignKeys>") or \
				line_stripped.startswith("</candidateKeys>") or \
				line_stripped.startswith("</checkConstraints>") or \
				line_stripped.startswith("</primaryKey>")  or \
				line_stripped.startswith("</views>"):
				foreign_keys_element = False
			elif line_stripped.startswith("<views>"):
				views_element = True
			elif line_stripped.startswith("</views>"):
				views_element = False

		self.out_engine.close()
		self.utf8_converter(self.get_output_file())
