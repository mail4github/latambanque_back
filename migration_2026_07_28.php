<?php

$q = <<<EOT

ALTER TABLE `users` 
CHANGE COLUMN `text2` `text2` LONGBLOB NULL DEFAULT NULL;

EOT;

tep_db_query($q);

?>