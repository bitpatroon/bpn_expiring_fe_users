#
# Table structure for table 'tx_bpnexpiringfeusers_config'
#
CREATE TABLE tx_bpnexpiringfeusers_config
(
	uid             int(11)                  NOT NULL auto_increment,
	pid             int(11)    DEFAULT 0     NOT NULL,
	tstamp          int(11)    DEFAULT 0     NOT NULL,
	crdate          int(11)    DEFAULT 0     NOT NULL,
	cruser_id       int(11)    DEFAULT 0     NOT NULL,
	deleted         tinyint(4) DEFAULT 0     NOT NULL,
	hidden          tinyint(4) DEFAULT '1'   NOT NULL,
	testmode        tinyint(4) DEFAULT '1'   NOT NULL,
	limiter         int(11)    DEFAULT '100' NOT NULL,
	title           tinytext,
	excludesummer   tinyint(4) DEFAULT 0     NOT NULL,
	sysfolder       text,
	memberOf        text,
	andor           varchar(10)              NOT NULL DEFAULT 'AND',
	noMemberOf      text,
	andor_not       varchar(10)              NOT NULL DEFAULT 'AND',
	expiringGroup   tinytext,
	groupsToRemove  text,
	condition1      int(11)    DEFAULT 0     NOT NULL,
	condition2      int(11)    DEFAULT 0     NOT NULL,
	condition3      int(11)    DEFAULT 0     NOT NULL,
	condition4      int(11)    DEFAULT 0     NOT NULL,
	condition5      int(11)    DEFAULT 0     NOT NULL,
	condition6      int(11)    DEFAULT 0     NOT NULL,
	condition7      int(11)    DEFAULT 0     NOT NULL,
	condition8      int(11)    DEFAULT 0     NOT NULL,
	condition20     int(11)    DEFAULT 0     NOT NULL,
	days            int(11)    DEFAULT '365' NOT NULL,
	todo            int(11)    DEFAULT 0     NOT NULL,
	email_test      tinytext,
	email_fromName  tinytext,
	email_from      tinytext,
	email_bcc       tinytext,
	email_subject   tinytext,
	email_text      text,
	expires_in      int(11)    DEFAULT 0     NOT NULL,
	reactivate_link tinyint(3) DEFAULT 0     NOT NULL,
	extend_by       int(11)    DEFAULT '365' NOT NULL,
	page            text,
	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE = InnoDB;



#
# Table structure for table 'tx_bpnexpiringfeusers_log'
#
CREATE TABLE tx_bpnexpiringfeusers_log
(
	uid      int(11)              NOT NULL auto_increment,
	crdate   int(11)    DEFAULT 0 NOT NULL,
	job      int(11)    DEFAULT 0 NOT NULL,
	fe_user  int(11)    DEFAULT 0 NOT NULL,
	deleted  tinyint(4) DEFAULT 0 NOT NULL,
	testmode tinyint(4) DEFAULT 0 NOT NULL,
	action   tinytext,
	msg      text,

	PRIMARY KEY (uid),
	KEY job (job),
	KEY fe_user (fe_user)
) ENGINE = InnoDB;
