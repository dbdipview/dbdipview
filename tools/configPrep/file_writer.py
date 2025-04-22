class FileWriter:

	def __init__(self, filename):
		self.name = filename
		#print("Creating", filename)
		self.f = open(filename, 'w', newline='', encoding="utf8")


	def close(self):
		self.f.close()
		print("Created", self.name)


	def print(self, text):
		#print(text)
		self.f.write(text + "\n")
