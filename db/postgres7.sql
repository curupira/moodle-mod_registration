CREATE TABLE prefix_registration (
id SERIAL primary key,
course integer NOT NULL default '0',
name text NOT NULL default '',
intro text NOT NULL default '',
introformat integer NOT NULL default '1',
number integer NOT NULL default '0',
room text NOT NULL default '',
timedue integer NOT NULL default '0',
timeavailable integer NOT NULL default '0',
grade integer NOT NULL default '0',
timemodified integer NOT NULL default '0',
allowqueue integer NOT NULL default '0'
);

CREATE INDEX prefix_registration_course_idx ON prefix_registration (course);

CREATE TABLE prefix_registration_submissions (
id SERIAL primary key,
registration integer NOT NULL default '0',
userid integer NOT NULL default '0',
timecreated integer NOT NULL default '0',
timemodified integer NOT NULL default '0',
grade integer NOT NULL default '0',
comment text NOT NULL default '',
teacher integer NOT NULL default '0',
timemarked integer NOT NULL default '0',
mailed integer NOT NULL default '0'
);

CREATE INDEX prefix_registration_submissions_idx ON prefix_registration_submissions (registration);
CREATE INDEX prefix_registration_submissions_userid_idx ON prefix_registration_submissions (userid);
CREATE INDEX prefix_registration_submissions_mailed_idx ON prefix_registration_submissions (mailed);
CREATE INDEX prefix_registration_submissions_timemarked_idx ON prefix_registration_submissions (timemarked);


INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'view', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'add', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'update', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'view submission', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'upload', 'registration', 'name');
