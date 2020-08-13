<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Classreport plugin.
 *
 * @package   local_classreport
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_classreport\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class to compile appropriate data for table renderer.
 *
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

	protected $year;
	protected $groups;
    protected $sortorder;

    /**
     * main constructor.
     *
     * @param int $year - number between 7 and 12
     * @param array $groups - group names
     */
    public function __construct($year, $sort) {
        $this->year = $year;
        $this->sortorder = $sort;
        $this->groups = get_group_names_array();
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
    	global $DB, $OUTPUT;

    	$table = [];
        $courseheaders = [];
        $activities = [];

        $courses_and_activities = get_courses_for_year($this->year);
        $columns = count($courses_and_activities);
        $lastcourse = 0;
        foreach ($courses_and_activities as $course) {
            $classname = 'course-'.$course['id'];
            if ($lastcourse !== $course['id']) $classname .= ' first';
            $lastcourse = $course['id'];
            $activities[] = [
                "cmid" => $course['cmid'],
                "mod" => $course['mod'],
                "name" => $course['activity'],
                "category" => $course['category'],
                "classname" => $classname,
                "course" => $course['id']
            ];
            if (!array_key_exists($course['fullname'], $courseheaders)) {
                $courseheaders[$course['fullname']] = [
                    "name" => $course['fullname'],
                    "startdate" => $course['startdate'],
                    "colspan" => 0,
                    "id" => $course['courseid'],
                    "category" => $course['category']
                ];
            }
            $courseheaders[$course['fullname']]['colspan'] = $courseheaders[$course['fullname']]['colspan'] + 1;
        }
        $courseheaders = array_values($courseheaders); // convert hash to index for mustache iteratability

        foreach ($this->groups as $letter => $label) {

            // for each group, write a row with the group name
            $table[] = [
                "header" => true,             // {{#header}}do group row layout{{/header}}              
                "class" => "group-" . strtolower(trim($label)),
                "content" => trim($label),
                "colspan" => $columns
            ];

            $users = get_user_details($this->year, $letter);
            $completions = get_user_completions_data($users, $courses_and_activities);

            // for each user in the group, write a row
            foreach($users as $user) {

                // array to contain the completion rows
                $data = [];
                $total_complete = 0;
                // for each activity, grab the matching completion
                foreach ($activities as $cm) {
                    $complete = false;
                    // find the user completion record ..
                    foreach ($completions as $completion) {
                        if ($completion['userid'] === intval($user->id) && $completion['cmid'] === $cm['cmid']) {
                            $complete = boolval($completion['completionstate'] > 0);
                        }
                    }
                    $data[] = [
                        "complete" => $complete,
                        "category" => $cm['category'],
                        "classname" => $cm['classname'],
                        "course" => $cm['course']
                    ];
                }
                $userhtml = $OUTPUT->user_picture($user) .
                            \html_writer::link(new \moodle_url('/user/view.php', ['id' => $user->id]), fullname($user));
 
                $table[] = [
                    "header" => false,         // {{^header}}do user row layout{{/header}}
                    "content" => $userhtml,
                    "level" => $user->institution,
                    "class" => "group-" . strtolower($label),
                    "columns" => $data
                ];
            }
        }

        // now we have to shim in a 'done' column to the data
        // doing this here as arrays is much faster than incurring the db penalty doing it earlier

        // courses span one more column than they have displayed activities for to account for the 'done' column
        $courseheader_count = count($courseheaders);
        foreach ($courseheaders as &$patch) {
            $patch['colspan']++;
        }

        // identify the last activity for each course and tag the record (we render the extra cell inside the template)
        $activity_count = count($activities);
        foreach ($activities as $index => $curr) {
            if ($index > 0) {
                $modindex = $index - 1;
                $prev = $activities[$modindex];
                if ($activities[$modindex]['course'] !== $curr['course'] || $index === $activity_count-1) {
                    if ($index === $activity_count-1) $modindex = $index; // captures last record
                    $activities[$modindex]["last"] = true;
                }
            }
        }

        // this is basically a SUM(completed) GROUP BY(course) across each row
        // we end up tagging the last record per course group with a boolean state which is picked up in the template
        foreach ($table as $table_index => $row) {
            if ($row['header'] === true) {
                $table[$table_index]['colspan'] = $table[$table_index]['colspan'] + $courseheader_count; // account for one extra 'done' column per course
                continue;
            }
            $data = $row['columns'];
            $data_count = count($data);
            $last_course = $data[0]['course'];
            $course_count = 0;
            $compl = 0;
            foreach ($data as $data_index => $curr) {
                $record_index = ($data_index===$data_count-1) ? $data_index : $data_index - 1;
                if ($last_course === $curr['course']) {
                    // continue aggregating current course group
                    $course_count += 1;
                    $compl += ($curr['complete'] === true) ? 1 : 0;
                    if ($data_index===$data_count-1) {
                        // mark as last record in group, as it is the last in the series
                        $table[$table_index]['columns'][$record_index]['done'] = boolval($compl === $course_count);
                        $table[$table_index]['columns'][$record_index]['last'] = true;
                    }

                } else {
                    // mark as last record in group
                    $table[$table_index]['columns'][$record_index]['done'] = boolval($compl === $course_count);
                    $table[$table_index]['columns'][$record_index]['last'] = true;

                    // but also start counting again
                    $compl = ($curr['complete'] === true) ? 1 : 0;
                    $course_count = 1;
                    $last_course = $curr['course'];
                }
            }
        }

        // here is our final data to send to the renderer
        $result = [
            "courseheaders" => $courseheaders,
            "activities" => $activities,
            "table" => $table
        ];

        return $result;

    }

}
