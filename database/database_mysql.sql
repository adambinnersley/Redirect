CREATE TABLE IF NOT EXISTS `redirects` (
  `uri` varchar(255) NOT NULL,
  `redirect` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  UNIQUE KEY `requested_url` (`uri`)
);