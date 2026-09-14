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
 * backup_topicchoice_stepslib.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_topicchoice_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $topicchoice = new backup_nested_element("topicchoice", ["id"], [
            "name", "intro", "introformat", "allowchange", "showavailability",
            "timeopen", "timeclose", "completionsubmit", "timemodified",
        ]);
        $topics = new backup_nested_element("topics");
        $topic = new backup_nested_element("topic", ["id"], [
            "name", "maxstudents", "sortorder", "active", "timecreated", "timemodified",
        ]);
        $responses = new backup_nested_element("responses");
        $response = new backup_nested_element("response", ["id"], [
            "userid", "timecreated", "timemodified",
        ]);

        $topicchoice->add_child($topics);
        $topics->add_child($topic);
        $topic->add_child($responses);
        $responses->add_child($response);

        $topicchoice->set_source_table("topicchoice", ["id" => backup::VAR_ACTIVITYID]);
        $topic->set_source_table("topicchoice_topics", ["topicchoiceid" => backup::VAR_PARENTID]);
        $response->set_source_table("topicchoice_responses", ["topicid" => backup::VAR_PARENTID]);

        $response->annotate_ids("user", "userid");
        $topicchoice->annotate_files("mod_topicchoice", "intro", null);

        return $this->prepare_activity_structure($topicchoice);
    }
}
