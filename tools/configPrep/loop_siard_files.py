import pandas as pd
import parse_siard
import os
import re


def loop_siard_files(inputFiles, target_file, output_folder):
	""" loop through all SIARD files and create an excel output """

	id_postfix = 'A'

	for file in inputFiles:
		if os.path.isfile(file) and (file.endswith(".xml") or file.endswith(".siard") or file.endswith(".SIARD")):
			print("Parsing " + file)
			#xp = parse_siard.worker(file, output_folder, file[:len(file)-4], id_postfix)
			xp = parse_siard.SiardWorker(file, output_folder, id_postfix)
			id_postfix = chr(ord(id_postfix) + 1)
			xp.parse()

	try:
		#with pd.ExcelWriter(os.path.join(output_folder, target_file)) as writer:
		with pd.ExcelWriter(target_file) as writer:
			for file in inputFiles:        #os.listdir(output_folder):
				file = os.path.basename(file) + "___.csv"
				file = os.path.join(output_folder, file)
				if os.path.isfile(file) and file.endswith(".csv"):
					print("Merging " + file)
					try:
						df = pd.read_csv(file, sep='\t')
						sheet_name = os.path.splitext(os.path.basename(file))[0]
						sheet_name = re.sub(r"\.siard$", "", sheet_name, flags=re.IGNORECASE)
						df.to_excel(writer, sheet_name=sheet_name, header=False, index=False) # index=False skip first column
						os.remove(file)
					except pd.errors.EmptyDataError:
						print(f"WARNING: CSV file is empty:{file}")
					except Exception as e:
						print(f"Error processing {file}: {e}")

				else:
					print("ERROR: missing " + file)
					quit()

			# Create one more sheet as placeholder for dictionaries
			# They need to be moved here manually before next run of the program
			sheet_data = {'1': ['Id:', 'SelectDescription:'],
						  '2': ['Dict', 'Move the dictionaries here and then manually remove them from queries.xml']}
			new_df = pd.DataFrame(sheet_data)
			sheet_name = "Dictionaries"
			new_df.to_excel(writer, sheet_name=sheet_name, header=False, index=False)

	except PermissionError:
		print(f"No permission for {output_folder}.")
	except Exception as e:
		print(f"Unknown error: {e}")
