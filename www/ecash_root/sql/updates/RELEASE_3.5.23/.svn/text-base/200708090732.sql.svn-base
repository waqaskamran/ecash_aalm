#
# Moving 'engine' and 'keywords' from 'vehicle' table to 'demographics'
#


ALTER TABLE `demographics` ADD engine varchar(255);
ALTER TABLE `demographics` ADD keywords varchar(255);

ALTER TABLE `vehicle` DROP `engine`;
ALTER TABLE `vehicle` DROP `keywords`;