#!/home/boyd01z/usr/local/bin/python3.6
import pymysql as mysql
import shutil
# This will need to be changed to work on the webserver
# doing it local for now

conn = mysql.connect("localhost", "gbfilter_user", "MypW!23", "gb_filter", local_infile=True)
cursor = conn.cursor()

out_file = open("Sequence_lineage.txt", "w")
with open("Sequences.txt", "r") as file:
    for line in file:
        query = """
        SELECT `parent_tax_id` FROM `host_lineage` WHERE `child_tax_id` = {val};
        """.format(val=int(line.split("\t")[-2]))
        cursor.execute(query)
        print(line.rstrip(), file=out_file)
        for i in cursor.fetchall():
            print("\t".join(line.split("\t")[:8])
                  + "\t" + str(i[0])
                  + "\t" + line.split("\t")[-1].rstrip(), file=out_file)


drop_para_query = """
DROP TABLE IF EXISTS `parasite_lineage`;
"""
cursor.execute(drop_para_query)

create_para_query = """
CREATE TABLE `parasite_lineage` (
`accession_number` VARCHAR(255) NOT NULL,
`host` VARCHAR(255) NOT NULL,
`organism` VARCHAR(255) NOT NULL,
`county` VARCHAR(255) NOT NULL,
`colle_date` VARCHAR(255) NOT NULL,
`sub_date` VARCHAR(255) NOT NULL,
`taxonomy_id` VARCHAR(255) NOT NULL,
`host_converted` VARCHAR(255) NOT NULL,
`host_taxid` mediumint(12) NOT NULL default '0', 
`old_host` VARCHAR(255) NOT NULL,
KEY `accession_number` (`accession_number`),
KEY `host` (`host`),
KEY `organism` (`organism`),
KEY `taxonomy_id` (`taxonomy_id`),
KEY `host_taxid` (`host_taxid`)
);
"""
cursor.execute(create_para_query)

#shutil.move("Sequence_lineage.txt", "DB_Uploads/Sequence_lineage.txt")
load_para_query = """
LOAD DATA INFILE '/home/boyd01z/gb_hostfilter/DB_Uploads/Sequence_lineage.txt'
INTO TABLE parasite_lineage
FIELDS TERMINATED BY '\t' 
LINES TERMINATED BY '\n' 
(accession_number, host, organism, county, colle_date, sub_date,taxonomy_id,host_converted,host_taxid,old_host);
"""
cursor.execute(load_para_query)
conn.commit()
conn.close()
