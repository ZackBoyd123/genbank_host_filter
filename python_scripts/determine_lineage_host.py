#!/home/boyd01z/usr/local/bin/python3.6
import shutil
import pymysql as mysql
import sys

# This will need to be changed to work on the webserver
# doing it local for now

conn = mysql.connect("localhost", "gbfilter_user", "MypW!23", "gb_filter", local_infile=True)
cursor = conn.cursor()

if sys.argv[1] == "virus":
    file_out = "viral_lineage.txt"
    tip_node = 10239
else:
    file_out = "cellular_lineage.txt"
    tip_node = 131567

sys.stdout = open(file_out, "w")
#print("Parent_Tax_Id\tChild_Tax_id")
def sub_recurse(taxid, parent):
    print(str(parent)+"\t"+str(taxid))
    query = """
    SELECT `tax_id` FROM `node` WHERE `parent_tax_id` = {tax}
    """.format(tax=taxid)
    cursor.execute(query)
    for j in cursor.fetchall():
        sub_recurse(j[0], parent)



def recurse(taxid):
    init_query = """
    SELECT `tax_id` FROM `node` WHERE `parent_tax_id` = {tax}
    """.format(tax=taxid)
    cursor.execute(init_query)
    parents = []
    for j in cursor.fetchall():
        sub_recurse(j[0], taxid)
        parents.append(j[0])

    for i in parents:
        recurse(i)

# Change this number to do the recursion treating it as the first node
recurse(tip_node)
shutil.move(file_out, "DB_Uploads/"+str(file_out))
