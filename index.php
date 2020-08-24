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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once('lib.php');

// querystring params
$year = optional_param('year', 7, PARAM_INT);
$year = min(max($year, 7), 12); // boundary check 
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$download = optional_param('download',0,PARAM_INT) === 1;

// internals
$params = ["year" => $year, "sort" => $sort];
$redirecturl = $CFG->wwwroot.'/local/classreport/index.php';
$pagetitle = get_string('pluginname', 'local_classreport');
$context = context_system::instance();

// require authentication and capability
require_login();
require_capability('local/classreport:view', $context);

// set up page
$PAGE->set_url('/local/classreport/index.php', $params);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->requires->css('/local/classreport/css.php');

// render the page using templates
$renderer = $PAGE->get_renderer('local_classreport');
$report = new \local_classreport\output\main($year, $sort, $download);

if (0 && $download) {

	// generate the report data for use in the spreadsheet
	$data = $report->export_for_template($renderer);
	/*
	header('content-type: text/plain');
	var_dump();
	exit(0);

	[courseheaders] = [name, startdate, colspan, id, category]
	[activities] = [cmid, mod, name, category, classname, course, last]
	[table] =  [header, class, content, colspan]
			or [header, content, level, class, columns = [
						complete, category, classname, course, done, last
					]
				]
	*/

	require_once("$CFG->libdir/excellib.class.php");
	$filename = clean_filename("classreport_".date_format(date_create("now"),"YmdHis")).'.xls';
    $workbook = new \MoodleExcelWorkbook("-");
    $workbook->send($filename);
    for ($i=7;$i<13;$i++) {
	    $sheet = $workbook->add_worksheet('Year '.$i);
	    $sheet->write(0,0,'Name'); // row, column, token, format=null
	    $sheet->write(0,1,'Level');
	    $sheet->write(0,2,'Group');
	    $ranges = [];

	    // header row
	    for ($j=0,$k=3;$j<count($data['courseheaders']);$j++) {
	    	$cell = $data['courseheaders'][$j]['name'];
	    	$span = $data['courseheaders'][$j]['colspan'];
	    	$sheet->write(0,$k,$cell);
	    	$k = $k + $span;
	    	$ranges[] = $span;
	    }
	    // merge the header cells
	    // for ($j=0;$j<count($ranges);$j++) {
	    // 	$sheet->merge_cells(0,3+$j,0,3+$j+$ranges[$j]);
	    // }

	    // activity names
	    for ($j=0;$j<count($data['activities']);$j++) {
	    	$cell = $data['activities'][$j]['name'];
	    	$sheet->write(1,$j+3,$cell);
	    }

	    // table data
	    for ($j=0;$j<count($data['table']);$j++) {

	    }

	}
    $workbook->close();
	exit(0);
}


// set the page action button
$params["download"] = "1";
$dllink = html_writer::link(
	new moodle_url('/local/classreport/index.php', $params), get_string('download'),
	['title' => get_string('download'),'class' => 'btn btn-secondary']
);
$PAGE->set_button($dllink);


if ($download) {
	$filename = clean_filename("classreport_".date_format(date_create("now"),"YmdHis")).'.xls';
	header('Content-type: application/excel');
	header('Content-Disposition: attachment; filename='.$filename);
	echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Sheet 1</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>

<body>';
	echo $renderer->render_main($report);
	echo '</body></html>';
} else {
echo $OUTPUT->header();
$renderable = new \local_classreport\output\tabs($year);
echo $renderer->render_tabs($renderable);
$renderable = new \local_classreport\output\filter($params);
echo $renderer->render_filter($renderable);
echo $renderer->render_main($report);
echo $OUTPUT->footer();
}