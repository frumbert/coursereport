# Hillbrook Custom Class Reporting plugin

This plugin was developd by tim.stclair@gmail.com / tim@mylearningspace.com.au August 2020 for Hillbrook Anglican College, QLD Australia
www.mylearningspace.com.au | info@mylearningspace.com.au | 1300 438 567 (GET LMS)

## Objective

The objective of this development is to create a custom plugin within the Moodle site that facilitates a live
view of the required data as well as securing the access using roles within Moodle.

## Features
This is a local plugin compatible with Moodle 3.5 and higher, with the following
features:

* Role-based access. Users with a specific role will be able to view the report
* Extensible styles. Administrators will be able to extend the plugin styles to add custom colouring
information for each stream, course or activity (via css).
* Sticky table header columns: The ability to scroll through many courses horizontally whilst maintaining
the name of the related user is important.
* Tabs will be like how the currently spreadsheet uses sheets to break up users by year.
* Ticks and empties will be used to denote the completion state of users. Additionally, cells will be able
to have their appearance changed based on their data type (stream / course / activity / user). This will
use CSS formatting.

# Spec

Other details of the way the plugin will present data are:

* Usernames will be linked to their Moodle profile page in the same way as existing activity completion
report pages (photo + linked name).
* User email and class information will not be shown on each row as it is redundant data accessible
through the user profile link
* Streams will be assumed to be Moodle categories even though the current site doesn’t have
this method of course organization.
* Course ‘Available’ column will not be shown on each row as it is redundant data represented
elsewhere.
* Filtering or Sorting within columns will not be implemented.
* Column and row order will be:
* Groups and Users will use alphabetic ordering (top to bottom with groups).
* Streams and Courses will use alphabetic ordering (left to right, courses within streams).
* Activities within courses will use intrinsic ordering (as shown within course).
* The course columns will feature the course full name and course start date.
* The course activity columns will use labels based on a lookup table as activity names do not have a
short variant; using the full activity name may cause overflow issues.
* Courses that have no enrolments for the users in the currently selected year will not show on that
report page.
* Report will not have filter or export to file capability.

# Reference
## Streams and Courses

There are currently around 10 active courses with 6 more in development. Courses to be shown on reports
are those with enrolled users. The site does not make use of course start and end dates, or the visibility flag
on the record. Courses are not currently properly categories into their streams (but could be). Categorising
courses will assist will styling of streams.

## Users and Groups

User enrolment and group details are synchronised through LDAP for this site. Courses have groups created
but the membership data is not currently kept up to date. User level, year and class group data is stored in
the Department and Institution fields on a user record and is shown on activity reports. Students’ current Year
and Group are stored in a single field in the form 10B or 7Y – a number followed by a group designator (Blue,
Green, Red, White, Yellow), and Level (1 or 2) is stored as plain text. User custom attributes are not required in
this report.

## Activities

Courses typically have two or more activities which are marked as completed either by the user (in case of
‘acknowledgement’ type statements) or automatically (for example SCORM activities). Currently all courses
contain at least one SCORM activity. Courses do not make use of overall course completion rules.

Courses typically have two or more activities which are marked as completed either by the user (in case of
‘acknowledgement’ type statements) or automatically (for example SCORM activities). Currently all courses
contain at least one SCORM activity. Courses do not make use of overall course completion rules.

## Colour and Symbols

The spreadsheet currently makes use of colour to denote the different course streams, as well as to highlight
activities that are not yet completed.

Activities use a tick and cross system to denote completion or incomplete activites. The phrases “Yes” denotes
an overall course completion, or empty represents an incomplete state

Class groupings use their designated colour as a spatial highlight within the spreadsheet

## Licence

GPL3