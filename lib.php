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
 * @package   local_classreport
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die;

// get array of group names
// in B => Blue format
function get_group_names_array() {
    $data = get_config('local_classreport', 'groupnames');
    if (empty($data)) throw new invalid_state_exception('Group names have not been configured.');
    $data = explode(PHP_EOL, trim($data));
    $values = [];
    array_walk($data, function ($value) use (&$values) {
        if (empty($value)) return;
        $keyname = strtoupper(substr($value,0,1));
        $values[$keyname] = $value;
    }, $data);
    return $values;
}

// look up users in a year and group, return useful user fields
function get_user_details($year, $group) {
    global $DB;
    $fields = \user_picture::fields();
    $sql = "
        SELECT {$fields}
        FROM {user}
        WHERE department = '{$year}{$group}'
        ORDER BY lastname, firstname
    ";
    return $DB->get_records_sql($sql);
}

// return an ordered array containing the course and activities for enrolments in a year
// ordering: category -> course -> activity
function get_courses_for_year($year) {
    global $DB;

    $modnames = get_config('local_classreport', 'modnames');
    if (empty($modnames)) throw new invalid_state_exception('Module names have not been configured.');
    $modnames = explode(',', trim($modnames));

    $sql = "
        SELECT c.id,c.category,c.fullname,c.startdate FROM {course} c
        INNER JOIN {course_categories} t ON c.category = t.id
        WHERE c.visible = 1
        AND t.visible = 1
        AND c.id IN (
            SELECT m.courseid FROM {user_enrolments} e
            INNER JOIN {enrol} m ON e.enrolid = m.id
            WHERE e.userid IN (
                SELECT id FROM {user}
                WHERE department LIKE '{$year}_'
                AND deleted = 0
                AND suspended = 0
            )
        )
        ORDER BY t.sortorder, c.sortorder
    ";
    if ($courses = $DB->get_records_sql($sql)) {
        $rows = [];
        foreach ($courses as $row) {
            $modinfo = get_fast_modinfo($row);
            foreach ($modinfo->get_cms() as $cmid => $cm) {
                if ($cm->uservisible && $cm->available && in_array($cm->modname, $modnames)) {
                    // $url = new moodle_url("/mod/{$cm->modname}/view.php",["id"=>$cmid]);
                    $rows[] = [
                        "id" => $cm->course,
                        "cmid" => (int) $cm->id,
                        "category" => (int) $row->category,
                        "courseid" => (int) $row->id,
                        "fullname" => $row->fullname,
                        "startdate" => (int) $row->startdate,
                        "mod" => $cm->modname,
                        "activity" => $cm->name
                    //    "url" => $url->out()
                    ];
                }
            }
        }
        return $rows;
    }
    return false;
}

// look up records for users in coursemodules we know about; no record = no completion
function get_user_completions_data($users, $all_courses) {
    global $DB;
    $cmids = implode(',',array_values(array_column($all_courses,"cmid")));
    $userids = implode(',',array_values(array_column((array) $users,"id")));
    $sql = "
        SELECT id, userid, completionstate, coursemoduleid FROM {course_modules_completion}
        WHERE userid IN ($userids)
        AND coursemoduleid IN ($cmids)
    ";
    unset($userids);
    unset($cmids);
    $records = [];
    foreach($DB->get_records_sql($sql) as $row) {
        $records[] = [
            "userid" => (int) $row->userid,
            "completionstate" => (int) $row->completionstate,
            "cmid" => (int) $row->coursemoduleid
        ];
    }
    return $records;
}
