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

        foreach ($courses_and_activities as $course) {
            $activities[] = [
                "cmid" => $course['cmid'],
                "name" => $course['mod'],
                "category" => $course['category']
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
                "class" => "group-" . strtolower($label),
                "content" => $label,
                "colspan" => $columns
            ];

            $users = get_user_details($this->year, $letter);
            $completions = get_user_completions_data($users, $courses_and_activities);

            // for each user in the group, write a row
            foreach($users as $user) {

                // array to contain the completion rows
                $data = [];

                // for each activity, grab the matching completion
                foreach ($activities as $cm) {
                    $complete = 0;
                    // find the user completion record ..
                    foreach ($completions as $completion) {
                        if ($completion['userid'] === intval($user->id) && $completion['cmid'] === $cm['cmid']) {
                            $complete = ($completion['completionstate'] > 0);
                        }
                    }
                    $data[] = [
                        "complete" => $complete,
                        "category" => $cm['category']
                    ];
                }
                $userhtml = $OUTPUT->user_picture($user) .
                            \html_writer::link(new \moodle_url('/user/view.php', ['id' => $user->id]), fullname($user));
 
                $table[] = [
                    "header" => false,         // {{^header}}do user row layout{{/header}}
                    "content" => $userhtml,
                    "class" => "group-" . strtolower($label),
                    "columns" => $data
                ];
            }
        }

        $result = [
            "activities" => $activities,
            "courseheaders" => $courseheaders,
            "table" => $table
        ];

       // echo "<pre>"; var_dump($table); echo "</pre>";

        return $result;

    }

}
