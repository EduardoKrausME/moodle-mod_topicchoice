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
 * topic_manager.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_topicchoice;

/**
 * Class topic_manager.
 */
class topic_manager {
    /**
     * Method sync_topics.
     *
     * @param int $topicchoiceid Parameter topicchoiceid.
     * @param array $names Parameter names.
     * @param array $limits Parameter limits.
     * @param array $ids Parameter ids.
     * @return void Return value.
     */
    public static function sync_topics(int $topicchoiceid, array $names, array $limits, array $ids): void {
        global $DB;

        $existing = $DB->get_records("topicchoice_topics", ["topicchoiceid" => $topicchoiceid]);
        $kept = [];
        $sortorder = 0;

        foreach ($names as $index => $rawname) {
            $name = trim((string)$rawname);
            if ($name === "") {
                continue;
            }

            $limit = max(1, (int)($limits[$index] ?? 1));
            $topicid = (int)($ids[$index] ?? 0);
            $now = time();

            if ($topicid > 0 && isset($existing[$topicid])) {
                $topic = $existing[$topicid];
                $topic->name = $name;
                $topic->maxstudents = $limit;
                $topic->sortorder = $sortorder;
                $topic->active = 1;
                $topic->timemodified = $now;
                $DB->update_record("topicchoice_topics", $topic);
                $kept[$topicid] = true;
            } else {
                $topic = (object)[
                    "topicchoiceid" => $topicchoiceid,
                    "name" => $name,
                    "maxstudents" => $limit,
                    "sortorder" => $sortorder,
                    "active" => 1,
                    "timecreated" => $now,
                    "timemodified" => $now,
                ];
                $newid = $DB->insert_record("topicchoice_topics", $topic);
                $kept[$newid] = true;
            }
            $sortorder++;
        }

        foreach ($existing as $topic) {
            if (isset($kept[$topic->id])) {
                continue;
            }

            if ($DB->record_exists("topicchoice_responses", ["topicid" => $topic->id])) {
                $topic->active = 0;
                $topic->timemodified = time();
                $DB->update_record("topicchoice_topics", $topic);
            } else {
                $DB->delete_records("topicchoice_topics", ["id" => $topic->id]);
            }
        }
    }
}
