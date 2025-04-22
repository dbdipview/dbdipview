import os.path
import re
import os

class CsvWriter:
	"""	Write into a csv file """

	def __init__(self, input_file, output_folder):
		self.out_dir = output_folder   #os.path.dirname(output_folder)
		self.out_file = re.sub(" ", "_", os.path.basename(input_file))
		self.out_file_path = os.path.join(self.out_dir, self.out_file + "___.csv")
		#print("Queue: output filepath" + self.out_file_path)
		if  os.path.exists(self.out_dir):
			self.filename = self.out_file_path
			self.f = open(self.out_file_path, "w", encoding='utf-8')
		else:
			print("ERROR, please create: " + self.out_dir)
			exit()


	def get_output_file(self):
		return(self.out_file_path)


	def out(self, line):
		self.f.write(line + "\n")


	def close(self):
		self.f.close()
