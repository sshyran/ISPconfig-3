ALTER TABLE `dns_rr` CHANGE `data` `data` TEXT NOT NULL DEFAULT ''
ALTER TABLE `ftp_user` ADD `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `dl_bandwidth` ;
