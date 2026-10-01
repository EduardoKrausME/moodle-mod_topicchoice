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
 * report_viewed.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_topicchoice\event;

use core\event\base;
use moodle_url;

/**
 * Class report_viewed.
 */
class report_viewed extends base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data["crud"] = "r";
        $this->data["edulevel"] = self::LEVEL_TEACHING;
        $this->data["objecttable"] = "topicchoice";
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string("eventreportviewed", "topicchoice");
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the report for topic choice '{$this->objectid}'.";
    }

    /**
     * Method get_url.
     *
     * @return moodle_url Return value.
     */
    public function get_url(): moodle_url {
        return new moodle_url("/mod/topicchoice/report.php", ["id" => $this->contextinstanceid]);
    }
}
