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

// render the page using templates
$renderer = $PAGE->get_renderer('local_classreport');

echo $OUTPUT->header();
$renderable = new \local_classreport\output\tabs($year);
echo $renderer->render($renderable);
$renderable = new \local_classreport\output\filter();
echo $renderer->render($renderable);
$renderable = new \local_classreport\output\main($year, $sort);
echo $renderer->render($renderable);
echo $OUTPUT->footer();