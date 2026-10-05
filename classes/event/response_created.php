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
 * response_created.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_topicchoice\event;

use core\event\base;
use moodle_url;

/**
 * Class response_created.
 */
class response_created extends base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data["crud"] = "c";
        $this->data["edulevel"] = self::LEVEL_PARTICIPATING;
        $this->data["objecttable"] = "topicchoice_responses";
    }

    /**
     * Returns the mapping for the response object id when restoring logs.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            "db" => "topicchoice_responses",
            "restore" => self::NOT_MAPPED,
        ];
    }

    /**
     * Returns mappings for values stored in the other event data.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            "topicid" => [
                "db" => "topicchoice_topics",
                "restore" => "topicchoice_topic",
            ],
        ];
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string("eventresponsecreated", "topicchoice");
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' created topic choice response " .
            "'{$this->objectid}' for user '{$this->relateduserid}'.";
    }

    /**
     * Method get_url.
     *
     * @return moodle_url Return value.
     */
    public function get_url(): moodle_url {
        return new moodle_url("/mod/topicchoice/view.php", ["id" => $this->contextinstanceid]);
    }
}
