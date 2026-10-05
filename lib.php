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
 * lib.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_topicchoice\topic_manager;

/**
 * Returns supported Moodle features.
 *
 * @param string $feature
 * @return bool|string|null
 */
function topicchoice_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        default => null,
    };
}

/**
 * Returns cached course-module information, including custom completion rules.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|null
 */
function topicchoice_get_coursemodule_info($coursemodule) {
    global $DB;

    $topicchoice = $DB->get_record(
        "topicchoice",
        ["id" => $coursemodule->instance],
        "id, name, intro, introformat, completionsubmit"
    );
    if (!$topicchoice) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $topicchoice->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro("topicchoice", $topicchoice, $coursemodule->id, false);
    }

    if ((int)$coursemodule->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata["customcompletionrules"]["completionsubmit"] = (int)$topicchoice->completionsubmit;
    }

    return $info;
}

/**
 * Returns descriptions of the active custom completion rules.
 *
 * @param cm_info|stdClass $cm Course module information.
 * @return array
 */
function topicchoice_get_completion_active_rule_descriptions($cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC ||
            empty($cm->customdata["customcompletionrules"]["completionsubmit"])) {
        return [];
    }

    return [get_string("completiondetail:submit", "topicchoice")];
}

/**
 * Adds a topic choice instance.
 *
 * @param stdClass $data
 * @param mod_topicchoice_mod_form|null $mform
 * @return int
 */
function topicchoice_add_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $id = $DB->insert_record("topicchoice", $data);
    topic_manager::sync_topics($id, $data->topic ?? [], $data->limit ?? [], $data->topicid ?? []);

    return $id;
}

/**
 * Updates a topic choice instance.
 *
 * @param stdClass $data
 * @param mod_topicchoice_mod_form|null $mform
 * @return bool
 */
function topicchoice_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record("topicchoice", $data);
    topic_manager::sync_topics($data->id, $data->topic ?? [], $data->limit ?? [], $data->topicid ?? []);

    return true;
}

/**
 * Deletes a topic choice instance.
 *
 * @param int $id
 * @return bool
 */
function topicchoice_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists("topicchoice", ["id" => $id])) {
        return false;
    }

    $DB->delete_records("topicchoice_responses", ["topicchoiceid" => $id]);
    $DB->delete_records("topicchoice_topics", ["topicchoiceid" => $id]);
    $DB->delete_records("topicchoice", ["id" => $id]);

    return true;
}
