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
function get_user_details($year, $group, $order = 'lastname, firstname') {
    global $DB;
    $fields = \user_picture::fields('u',['institution']);
    $sql = "
        SELECT {$fields}
        FROM {user} u
        WHERE u.department = '{$year}{$group}'
        AND u.deleted = 0
        AND u.suspended = 0
        ORDER BY {$order}
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
            WHERE e.status = 0
            AND e.userid IN (
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
                        "id" => (int) $cm->course,
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

    // some part of the lookup failed, return catchable state
    return null;
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

/**
 * Get icon mapping for font-awesome.
 *
 * @return  array
 */
// function local_classreport_get_fontawesome_icon_map() {
//     return [
//         'i/checkedcircle' => 'fa-circle',
//         'i/uncheckedcircle' => 'fa-circle-o',
//         'i/valid' => 'fa-check',
//         'i/invalid' => 'fa-times'
//     ];
// }

function local_classreport_get_compiled_css() {
    $data = get_config('local_classreport', 'scss');
    if (empty($data)) return '';
    $result = '';
    $fn = new core_scss();
    try {
        $result = $fn->compile($data);
    } catch (Leafo\ScssPhp\Exception\ParserException $e) {
        //
    } catch (Leafo\ScssPhp\Exception\CompilerException $e) {
        //
    }
    return $result;
}

// you can convert html tables to excel sheets directly, with some formatting
// see https://stackoverflow.com/a/60430576
function createAndDownloadExcelWorksheet($sort, $filename) {
global $CFG;

    // MOODLE 3.8 removed PHPExcel and replaced it with PHPSpreadsheet
    // there's enough different to need to fork codepaths
    if (file_exists($CFG->libdir . '/phpexcel/PHPExcel.php')) {
        createAndDownloadExcel_pre38($sort, $filename);
    } else {
        createAndDownloadExcel_38plus($sort, str_replace('.xls','.xlsx', $filename));
    }

}

function createAndDownloadExcel_pre38($sort, $filename) {
global $PAGE, $CFG;
    require_once($CFG->libdir . '/phpexcel/PHPExcel.php');

    $objOutput = new PHPExcel();
    $renderer = $PAGE->get_renderer('local_classreport');

    for ($year=7;$year<13;$year++) {

        // create a new spreadsheet and select the first sheet
        $objPHPExcel = new PHPExcel();
        $asheet = $objPHPExcel->setActiveSheetIndex(0);
        $asheet->setTitle("Year " . $year);

        // render table using template
        $report = new \local_classreport\output\main($year, $sort, true);
        $table = $renderer->render_main($report);

        // save the html to a temp file
        $tmpfile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($tmpfile, $table);

        // read the html back in as a sheet
        $excelHTMLReader = PHPExcel_IOFactory::createReader('HTML');
        $excelHTMLReader->loadIntoExisting($tmpfile, $objPHPExcel);
        unlink($tmpfile);

        // append this sheet to the overall spreadsheet
        $objOutput->addExternalSheet($asheet, $year - 7);

    }

    // set which tab is selected by default
    $objOutput->setActiveSheetIndex(0);

    // send to browser as an attachment
    header('Content-type: application/excel');
    header("Content-Disposition:attachment;filename={$filename}");

    // send to php output stream directly
    $objWriter = PHPExcel_IOFactory::createWriter($objOutput, 'Excel2007');
    $objWriter->save('php://output');

}

function createAndDownloadExcel_38plus($sort, $filename) {
global $PAGE, $CFG;
    require_once($CFG->libdir . '/excellib.class.php');

    $objOutput = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $renderer = $PAGE->get_renderer('local_classreport');

    for ($year=7;$year<13;$year++) {

        // create a new spreadsheet and select the first sheet
        $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $asheet = $excel->getActiveSheet(); // $excel->setActiveSheetIndex(1);
        $asheet->setTitle("Year " . $year);

        // render table using template
        $report = new \local_classreport\output\main($year, $sort, true);
        $table = $renderer->render_main($report);
        $table = "<!doctype html><html><body>{$table}</body></html>";

        // save the html to a temp file
        $tmpfile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($tmpfile, $table);

        // read the html back in as a sheet
        $excelHTMLReader = new \PhpOffice\PhpSpreadsheet\Reader\Html;
        $excelHTMLReader->loadIntoExisting($tmpfile, $excel);
        unlink($tmpfile);

        // append this sheet to the overall spreadsheet
        $objOutput->addExternalSheet($asheet, $year - 6);

    }

    // set which tab is selected by default
    $objOutput->setActiveSheetIndex(1);

    // send to browser as an attachment
    header('Content-type: application/excel');
    header("Content-Disposition:attachment;filename={$filename}");

    // send to php output stream directly
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objOutput);
    $writer->save('php://output');

}

// for reporting on the error condition when iterating through
function findAndReturnErrorReason($year) {
global $DB;

    $num = $DB->count_records_sql("
        SELECT COUNT('x') FROM {course_categories}
        WHERE visible=1
    ");
    if ($num === 0) {
        return 'No categories are visible.';
    }

    $num = $DB->count_records_sql("
        SELECT COUNT('x') FROM {course}
        WHERE visible=1
    ");
    if ($num === 0) {
        return 'No courses are visible.';
    }

    $num = $DB->count_records_sql("
        SELECT COUNT('x') FROM {user}
        WHERE department LIKE '{$year}_'
        AND deleted = 0
        AND suspended = 0
    ");
    if ($num === 0) {
        return 'No active users (not deleted or suspended) are in this year.';
    }

    $num = $DB->count_records_sql("
        SELECT COUNT(m.courseid) FROM {user_enrolments} e
        INNER JOIN {enrol} m ON e.enrolid = m.id
    ");
    if ($num === 0) {
        return 'There are no user enrolments at all.';
    }

    $num = $DB->count_records_sql("
            SELECT COUNT(m.courseid) FROM {user_enrolments} e
            INNER JOIN {enrol} m ON e.enrolid = m.id
            WHERE e.userid IN (
                SELECT id FROM {user}
                WHERE department LIKE '{$year}_'
                AND deleted = 0
                AND suspended = 0
            )
    ");
    if ($num === 0) {
        return 'There are no users enrolled in courses for this year.';
    }


    return 'Setup data for user/courses/enrolments is in an invalid state.';
}