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
 * Classreport rendrer.
 *
 * @package   local_classreport
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_classreport\output;
defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;

class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the class report.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_tabs(tabs $tabs) {
        return $this->render_from_template('local_classreport/tabs', $tabs->export_for_template($this));
    }

    /**
     * Return the main content for the class report.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_filter(filter $filter) {
        return $this->render_from_template('local_classreport/filter', $filter->export_for_template($this));
    }

    /**
     * Return the main content for the class report.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) {
        $template = ($main->export) ? 'local_classreport/sheet' : 'local_classreport/table';
        return $this->render_from_template($template, $main->export_for_template($this));
    }
}