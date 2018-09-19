#!/home/boyd01z/usr/local/bin/python3.6
from fuzzywuzzy import process
import re
import sys
import os

acc_num, host, organism, collected, country, date, identifier = "", "", "", "", "", "", ""
out_acc_file = open("tmp.txt", "w")
unmatched = open("unmatched.txt", "w")
with open(sys.argv[1],"r") as f:
    for idx, line in enumerate(f):
        # Remove all the whitespace from the front of lines.
        line = line.strip()
        # Get the accesion number.
        # Split the line on 'ACCESSION' this way list element [1] is always what we need.
        if line.startswith("VERSION"):
            acc_num = (line.split("VERSION")[1].rstrip().strip())
        # When journal was submitted.
        # Same as with the accession number.
        if line.startswith("JOURNAL"):
            if line.split("JOURNAL")[1].strip().startswith("Submitted"):
                date = (line.split("(")[-1].split(")")[0])

        # Host information. Line will always start with /host= as the whitespace
        # has been removed.
        # This was applied to organism, country, collection date and taxid.
        if line.startswith("/host="):
            host = (line.split("=")[1].replace("\"",""))
        if line.startswith("/organism="):
            organism = (line.split("=")[1].replace("\"",""))
        if line.startswith("/country="):
            country = (line.split("=")[1].replace("\"",""))
        if line.startswith("/collection_date="):
            collected = (line.split("=")[1].replace("\"",""))
        if line.startswith("/db_xref=\"taxon:"):
            identifier = (line.rsplit(":")[-1].replace("\"",""))


        # When a line == // that is the end of information regarding that element.
        # For this reason we can print out all the variables set above and then reset them
        # to empty strings so that they will contanin a value for next time.
        if line.startswith("//"):
            # If there is no information on host or organism do not print a line to file.

            if host == "" or organism == "":
                proceed = False
            else:
                proceed = True


            # If there is no information for a certain variable set it to NA and print it to file.
            if proceed:
                if acc_num == "":
                    acc_num = "NA"
                if country == "":
                    country = "NA"
                if collected == "":
                    collected = "NA"
                if date == "":
                    date = "NA"
                if identifier == "":
                    identifier = "NA"
                print(acc_num+"\t"+ host + "\t" + organism + "\t" + country + "\t" +
                      collected + "\t" + date + "\t" + identifier, file=out_acc_file)

            # Set all variables to empty strings for next element in file.
            acc_num, host, organism, collected, country, date, identifier = "", "", "", "", "", "", ""


#A tupule (list like) variable containing all the elements we dont want to see
# at the start of a string.
unid = ("unidentified", "unclassified", "unspecified", "unknown")

# Homo sapiens & misspelling. Patient and human are also added here
homo_sap = ("homo sapien", "homo-sapien", "homo_sapien", "homa sapien", "homosapien",
            "homo spioens", "homo sapinens", "patient", "human", "child", "h.sapiens")

#Live stock and animals
live_stock = ("chicken", "pig", "cattle", "cow", "sheep", "swine", "calf", "dog",
              "cat")

# patient like list
patient = [" patient"]



tax_dict = {}
with open("taxdump_files/names.dmp", "r") as tax_f:
    for line in tax_f:
        line = line.rstrip().lower().replace("\t","").split("|")
        tax_dict[line[1]] = line[0]
    tax_f.close()

out_file = open(sys.argv[1]+"_acc_table.txt", "w")
with open("tmp.txt","r") as host_f:
    # Keep track of lines in file
    tot_line = 0

    for idex, line in enumerate(host_f):
        tot_line += 1
        # Remove all whitepsace at start and end of line. Make everything lower case
        line = line.strip().rstrip().lower()
        old = line.split("\t")[1]
        # Make a variable to store what we want newline to look like
        newline = line
        newline = newline.split("\t")

        check_line = line.split("\t")[1]
        # If the line starts with any of the elements in unid list, take everything after the first
        # space.
        if check_line.startswith(unid):
            newline[1] = (" ".join(line.split(" ")[1:]))

        # Replace synonyms of human
        # at the start of line
        if check_line.startswith(homo_sap):
            newline[1] = "human"

        # Replace any string which has 'patient' in it to human.
        if any(i in check_line for i in patient):
            newline[1] = "human"

        # Deal with livestock
        # If line ends with anything in livestock list change the new line to the closet
        # match in the list.
        if check_line.endswith(live_stock):
            newline[1] = process.extractOne(check_line, list(live_stock))[0]

        new_check = newline[1].split(" ")
        # Compare the whole line to the dictionary
        if " ".join(new_check) in tax_dict:
            print("\t".join(newline)+"\t"+str(" ".join(new_check))+"\t"+tax_dict[" ".join(new_check)]+"\t"+str(old), file=out_file)

        # Compare the first word to dictionary
        elif new_check[0] in tax_dict:
            print("\t".join(newline)+"\t"+str(new_check[0])+"\t"+tax_dict[new_check[0]]+"\t"+str(old), file=out_file)


        # Compare the first AND second word to dictionary
        elif " ".join(new_check[:2]) in tax_dict:
            print("\t".join(newline)+"\t"+str(" ".join(new_check[:2]))+"\t"+tax_dict[" ".join(new_check[:2])]+"\t"+str(old), file=out_file)


        # Compare the last word in new_check to dictionary
        elif new_check[-1] in tax_dict:
            print("\t".join(newline)+"\t"+str(new_check[-1])+"\t"+tax_dict[new_check[-1]]+"\t"+str(old), file=out_file)


        # Compare the last two words in new_check to dictionary
        elif " ".join(new_check[-2:]) in tax_dict:
            print("\t".join(newline)+"\t"+str(" ".join(new_check[-2:]))+"\t"+tax_dict[" ".join(new_check[-2:])]+"\t"+str(old), file=out_file)


        # Compare all words between the brackets to dictionary.
        elif "".join(re.findall('\(([^)]+)', " ".join(new_check))) in tax_dict:
            print("\t".join(newline)+"\t"+str("".join(re.findall('\(([^)]+)', " ".join(new_check))))
                  +"\t"+tax_dict["".join(re.findall('\(([^)]+)', " ".join(new_check)))]+"\t"+str(old), file=out_file)


        # Compare the first word between brackets to the dictionary
        elif "".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[0] in tax_dict:
            print("\t".join(newline)+"\t"+str("".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[0])
                  +"\t"+tax_dict["".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[0]]+"\t"+str(old), file=out_file)



        # Compare the last word in brackets to the dictionary
        elif "".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[-1] in tax_dict:
            print("\t".join(newline)+"\t"+str("".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[-1])
                  +"\t"+tax_dict["".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[-1]]+"\t"+str(old), file=out_file)


        # Compare two words in the bracks to the dictionary.
        elif " ".join("".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[:2]) in tax_dict:
            print("\t".join(newline)+"\t"+str(" ".join("".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[:2]))
                  + "\t" + tax_dict[" ".join("".join(re.findall('\(([^)]+)', " ".join(new_check))).split(" ")[:2])]+"\t"+str(old), file=out_file)

        else:
            print("\t".join(newline), file=unmatched)


    host_f.close()

del tax_dict
#os.remove("tmp.txt")
