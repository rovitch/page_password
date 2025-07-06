CREATE TABLE pages (
	tx_pagepassword_enable              smallint(5) DEFAULT '0' NOT NULL,
	tx_pagepassword_extend_to_subpages  smallint(5) DEFAULT '0' NOT NULL,
	tx_pagepassword_password            varchar(255) DEFAULT '',
	tx_pagepassword_password_changed_at int(11) DEFAULT 0,
);
