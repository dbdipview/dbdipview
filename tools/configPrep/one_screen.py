
class XML_query_block:
	""" Creates input for metadata/queries.xml"""

	def __init__(self, pass_num):
		self.pass_num = pass_num
		self.previous_sifrant = "unknown"
		self.schema = "sch_unknown"
		self.firstColumn = True
		self.buffer_header = []
		self.buffer_params = []
		self.buffer = []

		self.links_to_next_screen_column = ""
		self.links_to_next_screen_id = ""
		self.links_to_next_screen_table = ""
		self.links_to_next_screen_columnsToForward = ""


	def write(self, s):
		self.buffer.append(s)


	def close(self):
		self.buffer_header = []
		self.buffer_params = []
		self.buffer = []


	def clear_select(self):
		self.previous_sifrant = "unknown"
		self.firstColumn = True
		self.buffer_header = []
		self.buffer_params = []
		self.buffer = []
		self.links_to_next_screen_column = ""
		self.links_to_next_screen_id = ""
		self.links_to_next_screen_table = ""
		self.links_to_next_screen_columnsToForward = ""


	def elements_id(self, value):

		if self.pass_num == 2:
			h = " hide='true'"
			value += "__1"
		else:
			h = ""
			value += ""

		out = """
			<screen>
				<id""" + h + """>"""+value+"""</id>"""
		self.buffer_header.append(out)


	def elements_select_description(self, description):

		out = """
				<selectDescription>""" + description + """</selectDescription>"""
		self.buffer_header.append(out)


	def elements_set_schema(self, schema):
		self.schema = schema


	def elements_set_title(self, title):

		out = """
				<title>""" + title + """</title>"""
		self.buffer_header.append(out)


	def elements_set_title_subselect(self, title):

		out = """
				<subselect>
					<title>""" + title + """</title>"""
		self.buffer_header.append(out)


	def elements_subtitle(self, table):

		out = """
				<subtitle>""" + table + """</subtitle>"""
		self.buffer_header.append(out)


	def elements_subtitle_subselect(self, table):

		out = """
					<subtitle>""" + table + """</subtitle>"""
		self.buffer_header.append(out)


	def elements_subselect(self, table, title, notFirstQuery):
		out = """
						<title>""" + title + """</title>
						<subtitle>""" + table + """</subtitle>"""

		self.buffer.append(out)


	def elements_param_long(self, label_name, table, column, type, dict, dict_in_column, dict_out_column, ccolumn_ltns_target):

		if label_name == "":
			return ""
		fw3 = ""
		m = ""
		n = ""
		out = ""
		if self.pass_num == 1:
				n = "\n					<name>" + label_name + "</name>"
				m = " size='8' skipNewLine='0'"
		else:
				fw3 = "\n					<forwardToSubqueryName>param_1</forwardToSubqueryName>"
				m = " mandatory='1'"
				n = "\n					<name>" + label_name + "</name>"

		#simple version
		if type.upper().startswith("CHAR"):
			t = "\n					<type>text</type>"
		else:
			t = "\n					<type>integer</type>"

		if dict and dict_in_column and dict_out_column:
			t =     "\n					<select>SELECT \"" + dict_in_column + "\", \"" + dict_out_column + "\"" + \
											" FROM \"" + self.schema + "\".\"" + dict + "\"" + \
											" ORDER BY \"" + dict_out_column + "\"</select>"
			t = t + "\n					<type>combotext</type>"
		#name not needed
	
		if self.pass_num == 1 or ccolumn_ltns_target:
			out = """
				<param""" + m + """>""" + n + """
					<dbtable>""" + table + """</dbtable>
					<dbcolumn>""" + column + """</dbcolumn>""" + t + fw3 + """
				</param>"""

		self.buffer_params.append(out)


	def elements_param_in_subselect(self, table, column):
		out = """
					<param>
						<forwardedParamName>param_1</forwardedParamName>
						<dbtable>"""+table+"""</dbtable>
						<dbcolumn>""" + column + """</dbcolumn>
					</param>"""
		self.buffer_params.append(out)


	def elements_query_start(self):
		out = """
				<query>SELECT"""
		self.buffer.append(out)


	def elements_query_start_subselect(self):
		out = """
					<query>SELECT"""
		self.buffer.append(out)


	def element_field(self, currentTable, current_column, column_as, sifrant, sifrant_in_column, sif_out_column, select_from_joins):

		pad = "\t\t\t\t\t"
		if not self.firstColumn:
			out = ","
		else:
			out = ""
			self.firstColumn = False

		if sifrant != "0" and sifrant != "":
			s_sif_prefix = "["
			s_sif_postfix = "]"
		else:
			s_sif_prefix = ""
			s_sif_postfix = ""

		if column_as:
			sas =  " AS \"" + s_sif_prefix + column_as + s_sif_postfix + "\""
		else:
			sas = ""

		if '"' in current_column:
			out +=       "\n" + pad +                                 current_column        + sas
		else:
			out +=       "\n" + pad + "\"" + currentTable + "\".\"" + current_column + "\"" + sas

		self.buffer.append(out)

		foreign_keys = ""
		sifrant_noex = sifrant
		i = sifrant.find("__")

		if i > -1:
			sifrant_noex = sifrant[0:i]

		if sifrant != "0" and sifrant:
			if column_as:
				sas =  " AS \"" + column_as + "\""
			else:
				sas = ""

			if sif_out_column:
				out = ",\n" + pad + "\"" + sifrant + "\".\"" + sif_out_column + "\"" + sas
				self.buffer.append(out)
			if self.previous_sifrant == sifrant:
				sjoin = " AND \n\t\t\"" + currentTable + "\".\""+ current_column +"\" = \"" + sifrant+"\".\"" + sifrant_in_column + "\""
			else:
				sjoin = "\n" + pad + "LEFT JOIN \"" + self.schema + "\".\"" + sifrant_noex + "\" AS \"" + sifrant + "\""
				sjoin += " ON \n" + pad
				sjoin += "\t\"" + currentTable + "\".\""+ current_column +"\" = \"" + sifrant+"\".\"" + sifrant_in_column + "\""
				self.previous_sifrant = sifrant

				foreign_keys = "ALTER TABLE \"" + self.schema + "\".\"" + currentTable + "\"\n" \
								+ "\tADD CONSTRAINT " +  currentTable + "_" + current_column + "_fkey \n" \
								+ "\tFOREIGN KEY (\"" + current_column + "\")\n" \
								+ "\tREFERENCES \"" + self.schema + "\".\"" + \
									sifrant + "\" (\"" + sifrant_in_column + "\");\n"
			select_from_joins.append(sjoin)
		return foreign_keys


	def select_from(self, currentTable, select_from_joins):
		pad = "\t\t\t\t\t"

		out = "\n" + pad + "FROM \"" + self.schema + "\".\"" + currentTable + "\""

		for s in select_from_joins:
			out += s
		self.buffer.append(out)


	def elements_set_link_to_next_screen(self, c, id, t, ctf):
		self.links_to_next_screen_column = c
		self.links_to_next_screen_id = id
		self.links_to_next_screen_table = t
		self.links_to_next_screen_columnsToForward = ctf


	def elements_subselect_end(self):
		out = """
					</query>
				</subselect>
"""
		self.buffer.append(out)


	def elements_screenend(self, table, id):
	
		if self.pass_num == 1:
			s = """<selectGroup></selectGroup>
				<selectOrder></selectOrder>
"""

			out = """
  				</query>
				""" + s

			if self.links_to_next_screen_columnsToForward:
				out = out + """
				<links_to_next_screen>
					<link>
						<dbcolumnname valueFromColumn=\""""+ self.links_to_next_screen_column + "\"" \
						">" + self.links_to_next_screen_column + \
						"""</dbcolumnname>
						<next_screen_id>""" + self.links_to_next_screen_id + """__1</next_screen_id>
						<dbtable>"""        + self.links_to_next_screen_table + """</dbtable>
						<dbcolumn>"""       + self.links_to_next_screen_columnsToForward + """</dbcolumn>
					</link>
				</links_to_next_screen>
"""
			out = out + """
				<view default="list">
					<columnName newCol="1">the field that starts the next column (when listMC)</columnName>
				</view>
			</screen>
"""
		else:
			out = """
			</screen>
"""
		self.buffer.append(out)


	def elements_query_end_pass2(self):
		out = """
				</query>
				<view default="list">
					<columnName newCol="1">the field that starts the next column (when listMC)</columnName>
				</view>
"""
		self.buffer.append(out)


	def debug(self, s):
		self.buffer.append(s)


	def elements_screen_end_pass2(self):
		out = """
			</screen>
"""
		self.buffer.append(out)


	def flush(self):
		out = ""
		for s in self.buffer_header:
			out = out + s

		params = ""
		for s in self.buffer_params:
			params = params + s

		out = out + params

		for s in self.buffer:
			out = out + s

		return out
