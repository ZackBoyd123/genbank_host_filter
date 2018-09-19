#!/bin/bash

# Python script directory
python_home=/home/boyd01z/gb_hostfilter/python_scripts


# Get the tax dump files 
wget ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz

# Make tax dump directory and extract files into it
mkdir taxdump_files
tar -zxvf taxdump.tar.gz -C taxdump_files/
rm -rf taxdump.tar.gz

#Download relevant files
wget ftp://ftp.ncbi.nih.gov/genbank/daily-nc/nc*.flat.gz -P nc_sequences/

# Download viral and phage sequences to relevant directories
wget ftp://ftp.ncbi.nih.gov/genbank/gbvrl*.seq.gz -P viral_sequences
wget ftp://ftp.ncbi.nih.gov/genbank/gbphg*.seq.gz -P phage_sequences

# Unzip them. Convert the files and cat the contents to corresponding files. 
for i  in $(ls | grep _sequences); do 
	# For each file in the directory: $j in $i 
	for j in $(ls $i); do
		gunzip $i"/"$j
		$python_home"/file_parser.py" $i"/"${j%.gz}
		cat $i"/"${j%.gz}"_acc_table.txt" >> Sequences.txt
		cat $i"/"${j%.gz}"_unmatched.txt" >> Unmatched.txt
	done
done

# Create files for lineages of cellular and virus
python $python_home"/determine_lineage_host.py" virus ; python $python_home"/determine_lineage_host.py" not_virus

# Upload these files to the DB
python $python_home"/create_db_2.py"

# Split the Sequence file into all parents for each node. 
python $python_home"/upload_parasite_host.py"
python $python_home"/upload_parasite_virus.py"


