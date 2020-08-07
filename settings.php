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


if ($hassiteconfig) {

	$ADMIN->add('reports', new admin_externalpage('local_classreport', get_string('showreport', 'local_classreport'),new moodle_url('/local/classreport/index.php')));
	
	$settings = new admin_settingpage('local_classreport_settings',get_string('pluginname', 'local_classreport'));
	$ADMIN->add('localplugins',$settings);
	$settings->add(new admin_setting_configtextarea('local_classreport/groupnames',
		get_string('groupnames', 'local_classreport'),
		get_string('groupnamesdesc', 'local_classreport'),
		"Blue\nGreen\nRed\nWhite\nYellow",
		PARAM_RAW,
		30, 8)
	);

	if ($all_modules = $DB->get_fieldset_select('modules', 'name', '')) {
		$all_modules = array_combine($all_modules, $all_modules); // keys now match values;
	    $settings->add(new admin_setting_configmultiselect('local_classreport/modnames',
	    	get_string('modnames', 'local_classreport'),
	    	get_string('modnamesdesc', 'local_classreport'),
	        ["scorm","page","feedback","assign","quiz"],
	    	$all_modules));
	}

}


	
