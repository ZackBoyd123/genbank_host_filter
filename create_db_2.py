#!/home/boyd01z/usr/local/bin/python3.6
import pymysql as mysql
import os
# This will need to be changed to work on the webserver
# doing it local for now

conn = mysql.connect("localhost", "gbfilter_user", "MypW!23", "gb_filter", local_infile=True)
cursor = conn.cursor()

drop_cellular = """
DROP TABLE IF EXISTS `host_lineage`;
"""
cursor.execute(drop_cellular)

create_cellular = """
CREATE TABLE `host_lineage` (
`parent_tax_id` mediumint(11) unsigned NOT NULL default '0',
`child_tax_id` mediumint(11) unsigned NOT NULL default '0',
PRIMARY KEY `pk`(`parent_tax_id`, `child_tax_id`),
KEY `parent_tax_id`(`parent_tax_id`),
KEY `child_tax_id`(`child_tax_id`)
);
"""
cursor.execute(create_cellular)

load_cellular = """
LOAD DATA INFILE '/home/boyd01z/gb_hostfilter/DB_Uploads/cellular_lineage.txt'
INTO TABLE `host_lineage` 
FIELDS TERMINATED BY '\t'
LINES TERMINATED BY '\n'
(parent_tax_id, child_tax_id);
"""
cursor.execute(load_cellular)

drop_viral = """
DROP TABLE IF EXISTS `virus_lineage`;
"""
cursor.execute(drop_viral)
create_viral = """
CREATE TABLE `virus_lineage` (
`parent_tax_id` mediumint(11) unsigned NOT NULL default '0',
`child_tax_id` mediumint(11) unsigned NOT NULL default '0',
PRIMARY KEY `pk`(`parent_tax_id`, `child_tax_id`),
KEY `parent_tax_id`(`parent_tax_id`),
KEY `child_tax_id`(`child_tax_id`)
);
"""
cursor.execute(create_viral)

load_viral = """
LOAD DATA INFILE '/home/boyd01z/gb_hostfilter/DB_Uploads/viral_lineage.txt'
INTO TABLE `virus_lineage`
FIELDS TERMINATED BY '\t'
LINES TERMINATED BY '\n'
(parent_tax_id, child_tax_id);
"""
cursor.execute(load_viral)

conn.commit()
conn.close()

