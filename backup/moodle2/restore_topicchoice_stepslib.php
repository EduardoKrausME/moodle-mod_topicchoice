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
 * restore_topicchoice_stepslib.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_topicchoice_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element("topicchoice", "/activity/topicchoice");
        $paths[] = new restore_path_element("topicchoice_topic", "/activity/topicchoice/topics/topic");
        if ($this->get_setting_value("userinfo")) {
            $paths[] = new restore_path_element("topicchoice_response", "/activity/topicchoice/topics/topic/responses/response");
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_topicchoice.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_topicchoice($data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record("topicchoice", $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Method process_topicchoice_topic.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_topicchoice_topic($data): void {
        global $DB;

        $data = (object)$data;
        $data->topicchoiceid = $this->get_new_parentid("topicchoice");
        $newitemid = $DB->insert_record("topicchoice_topics", $data);
        $this->set_mapping("topicchoice_topic", $data->id, $newitemid);
    }

    /**
     * Method process_topicchoice_response.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_topicchoice_response($data): void {
        global $DB;

        $data = (object)$data;
        $data->topicchoiceid = $this->get_new_parentid("topicchoice");
        $data->topicid = $this->get_new_parentid("topicchoice_topic");
        $data->userid = $this->get_mappingid("user", $data->userid);
        if ($data->userid) {
            $DB->insert_record("topicchoice_responses", $data);
        }
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files("mod_topicchoice", "intro", null);
    }
}
