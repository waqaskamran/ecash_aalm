## ALREADY RUN ON RC

## Keep document package ids from conflicting with document ids

UPDATE document_list_package SET document_package_id = document_package_id + 10000 WHERE 1;
UPDATE document_package SET document_package_id = document_package_id + 10000 WHERE 1;

ALTER TABLE `ldb_agean`.`document_package` AUTO_INCREMENT = 10018;
