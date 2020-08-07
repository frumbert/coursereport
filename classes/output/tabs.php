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
 * Class to compile appropriate data for tab renderer.
 *
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tabs implements renderable, templatable {

	protected $year;

    /**
     * main constructor.
     *
     * @param int $year - number between 7 and 12
     */
    public function __construct($year) {
        $this->year = $year;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $tabs = []; 
        for ($y=7;$y<13;$y++) {
            $url = (new \moodle_url("/local/classreport/index.php", ["year" => $y]))->out();
            $tabs[] = [
                "year" => $y,
                "active" => ($this->year === $y) ? "active" : "",
                "url" => $url
            ];
        }
        return ["tabs" => $tabs];
    }

}
