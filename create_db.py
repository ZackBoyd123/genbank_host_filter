#!/home/boyd01z/usr/local/bin/python3.6
import pymysql as mysql
import os
# This will need to be changed to work on the webserver
# doing it local for now

conn = mysql.connect("localhost", "gbfilter_user", "MypW!23", "gb_filter", local_infile=True)
cursor = conn.cursor()


drop_query = """
DROP TABLE IF EXISTS `Taxonomy_name`; 
"""
cursor.execute(drop_query)


create_query = """
CREATE TABLE `Taxonomy_name` (
`tax_id` mediumint(11) unsigned NOT NULL default '0',
`name_txt` varchar(255) NOT NULL default '',
`unique_name` varchar(255) default NULL,
`name_class` varchar(32) NOT NULL default '',
KEY `tax_id` (`tax_id`),
KEY `name_class` (`name_class`),
KEY `name_txt` (`name_txt`)
) ENGINE=InnoDB;
"""
cursor.execute(create_query)


load_query = """
LOAD DATA INFILE '/home/boyd01z/gb_hostfilter/taxdump_files/names.dmp'
INTO TABLE Taxonomy_name
FIELDS TERMINATED BY '\t|\t'
LINES TERMINATED BY '\t|\n'
(tax_id, name_txt, unique_name, name_class);
"""
cursor.execute(load_query)

drop_node_query = """
DROP TABLE IF EXISTS `node`;
"""
cursor.execute(drop_node_query)

create_node_query = """
CREATE TABLE `node` (
  `tax_id` mediumint(11) unsigned NOT NULL default '0',
  `parent_tax_id` mediumint(8) unsigned NOT NULL default '0',
  `rank` varchar(32) default NULL,
  `embl_code` varchar(16) default NULL,
  `division_id` smallint(6) NOT NULL default '0',
  `inherited_div_flag` tinyint(4) NOT NULL default '0',
  `genetic_code_id` smallint(6) NOT NULL default '0',
  `inherited_GC_flag` tinyint(4) NOT NULL default '0',
  `mitochondrial_genetic_code_id` smallint(4) NOT NULL default '0',
  `inherited_MGC_flag` tinyint(4) NOT NULL default '0',
  `GenBank_hidden_flag` smallint(4) NOT NULL default '0',
  `hidden_subtree_root_flag` tinyint(4) NOT NULL default '0',
  `comments` varchar(255) default NULL,
  PRIMARY KEY  (`tax_id`),
  KEY `parent_tax_id` (`parent_tax_id`)
) ENGINE=InnoDB;
"""
cursor.execute(create_node_query)

load_node_query = """
LOAD DATA INFILE '/home/boyd01z/gb_hostfilter/taxdump_files/nodes.dmp'
INTO TABLE node 
FIELDS TERMINATED BY '\t|\t' 
LINES TERMINATED BY '\t|\n' 
(tax_id, parent_tax_id,rank,embl_code,division_id,inherited_div_flag,genetic_code_id,inherited_GC_flag,
mitochondrial_genetic_code_id,inherited_MGC_flag,GenBank_hidden_flag,hidden_subtree_root_flag,comments);
"""
cursor.execute(load_node_query)

conn.commit()
conn.close()

