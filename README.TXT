Registration mod - register for dates

Features 2.0.0 (Marc-Robin Wendt)

Tested with Moodle 2.4 (January 23, 2013)

* Deleted users should be noticed

* Administration options for ID-Number and max. count of students per registration

Features 1.4 (Marc-Robin Wendt)

Tested with Moodle 1.9.13 (August 1, 2011)

* New: Registration-Period can be different from Registration-Date.

* New: Location/Room-Field.

* Improved queue with detailed notifications.

* Listing of students with more detailed information.

* Bug removed: People could change registration-html-form and register for closed dates.

* All group functions removed.

* Couple of minor changes.

Features 1.3
Tested with Moodle 1.8.2+ (August 1, 2007)
Tested with Moodle 1.7+ (January 21, 2007)

* Reset course feature implemented

* Roles

Features 1.2 
Tested with Moodle 1.5.4+, 1.6.1 (July 31, 2006)

* Teacher can register/unregister students directly.
	
* If the date is fully occupied, students can sign in to queue and wait
  if there will be someone that signs out. In that case, the first in 
  the queue will be notified by mail.

Upgrade to utf8 does not work - scripts are renamed. It needs to be done manually
afterwards. Does not concern new installations in 1.6+.

Features 1.1 (with help of Richard Haywood)

* The listing of students with details of their registration(s).
	
* Allow people to add multiple registrations to one course, however they can
  only make one reservation in each course block.

* Expanded the reservation listing page to include details of how many people
  are already registered in the course.

* and a couple of other minor alterations. 

Features 1.0
Tested with Moodle 1.5.3+ (May 2006)

- Teachers can create some examination dates.  They define date, time, and 
  place of the examination. Furthermore they define maximum number of students
  and maximum number of points. After the registration date, they define the 
  points for each student. These can be printed with or without student names.

- Each student can register/unregister only himself/herself.

- Each student can be registered only on one date.

- The submission for the examination is closed at 00:00 on the day of the examination.

- Each student can see only his/her total evaluation based on the results of examination.

- To students, student IDs are shown if defined instead of student names.

see also the paper:
http://www.kirp.chtf.stuba.sk/publicatione_info.php?id_pub=315

Installation:
copy the whole registration to moodle/mod/
move lang to moodle/lang (moodledata/lang in 1.6)
install by visiting htttp:your_moodle/admin/

Latest version can be found at
moodle.org -> Modules and Themes

Enjoy,
L. Cirka / M. Fikar / M.-R. Wendt

January 2008:
Maintainer change: R. Haywood

