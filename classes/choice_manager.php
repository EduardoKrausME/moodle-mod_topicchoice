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
 * choice_manager.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_topicchoice;

use completion_info;
use context_module;
use core\lock\lock_config;
use mod_topicchoice\event\response_created;
use mod_topicchoice\event\response_updated;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Class choice_manager.
 */
class choice_manager {
    /**
     * Property instance.
     *
     * @var stdClass
     */
    private stdClass $instance;
    /**
     * Property cm.
     *
     * @var stdClass
     */
    private stdClass $cm;
    /**
     * Property course.
     *
     * @var stdClass
     */
    private stdClass $course;

    /**
     * Method __construct.
     *
     * @param stdClass $instance Parameter instance.
     * @param stdClass $cm Parameter cm.
     * @param stdClass $course Parameter course.
     */
    public function __construct(stdClass $instance, stdClass $cm, stdClass $course) {
        $this->instance = $instance;
        $this->cm = $cm;
        $this->course = $course;
    }

    /**
     * Method get_topics.
     *
     * @param bool $includeinactive Parameter includeinactive.
     * @return array Return value.
     */
    public function get_topics(bool $includeinactive = false): array {
        global $DB;

        $params = ["topicchoiceid" => $this->instance->id];
        if (!$includeinactive) {
            $params["active"] = 1;
        }

        return $DB->get_records("topicchoice_topics", $params, "sortorder ASC, id ASC");
    }

    /**
     * Method get_counts.
     *
     * @return array Return value.
     */
    public function get_counts(): array {
        global $DB;

        $sql = "SELECT topicid, COUNT(1) AS total
                  FROM {topicchoice_responses}
                 WHERE topicchoiceid = :topicchoiceid
              GROUP BY topicid";
        $records = $DB->get_records_sql($sql, ["topicchoiceid" => $this->instance->id]);
        $counts = [];
        foreach ($records as $record) {
            $counts[(int)$record->topicid] = (int)$record->total;
        }
        return $counts;
    }

    /**
     * Method get_user_response.
     *
     * @param int $userid Parameter userid.
     * @return ?stdClass Return value.
     */
    public function get_user_response(int $userid): ?stdClass {
        global $DB;

        $record = $DB->get_record("topicchoice_responses", [
            "topicchoiceid" => $this->instance->id,
            "userid" => $userid,
        ]);
        return $record ?: null;
    }

    /**
     * Method get_availability_state.
     *
     * @return array Return value.
     */
    public function get_availability_state(): array {
        $now = time();
        if (!empty($this->instance->timeopen) && $now < $this->instance->timeopen) {
            return [
                "open" => false,
                "message" => get_string("notopenyet", "topicchoice", userdate($this->instance->timeopen)),
            ];
        }
        if (!empty($this->instance->timeclose) && $now > $this->instance->timeclose) {
            return [
                "open" => false,
                "message" => get_string("closed", "topicchoice", userdate($this->instance->timeclose)),
            ];
        }
        return ["open" => true, "message" => ""];
    }

    /**
     * Method get_view_data.
     *
     * @param int $userid Parameter userid.
     * @param bool $canchoose Parameter canchoose.
     * @return array Return value.
     */
    public function get_view_data(int $userid, bool $canchoose): array {
        $topics = $this->get_topics(false);
        $counts = $this->get_counts();
        $response = $this->get_user_response($userid);
        $items = [];

        foreach ($topics as $topic) {
            $selected = $counts[$topic->id] ?? 0;
            $available = max(0, $topic->maxstudents - $selected);
            $chosen = $response && (int)$response->topicid === (int)$topic->id;
            $full = $selected >= $topic->maxstudents;
            $changeblocked = $response && !$this->instance->allowchange && !$chosen;
            $disabled = !$canchoose || $full || $chosen || $changeblocked;

            if ($chosen) {
                $buttontext = get_string("selected", "topicchoice");
            } else if ($full) {
                $buttontext = get_string("full", "topicchoice");
            } else if ($response) {
                $buttontext = get_string("changeto", "topicchoice");
            } else {
                $buttontext = get_string("choose", "topicchoice");
            }

            $items[] = [
                "id" => $topic->id,
                "name" => format_string($topic->name),
                "selectedcount" => $selected,
                "maxstudents" => $topic->maxstudents,
                "available" => $available,
                "showavailability" => !empty($this->instance->showavailability),
                "chosen" => $chosen,
                "full" => $full,
                "disabled" => $disabled,
                "buttontext" => $buttontext,
            ];
        }

        $current = null;
        if ($response) {
            foreach ($topics as $topic) {
                if ((int)$topic->id === (int)$response->topicid) {
                    $current = format_string($topic->name);
                    break;
                }
            }
            if ($current === null) {
                global $DB;
                $currenttopic = $DB->get_record("topicchoice_topics", [
                    "id" => $response->topicid,
                    "topicchoiceid" => $this->instance->id,
                ]);
                if ($currenttopic) {
                    $current = format_string($currenttopic->name);
                }
            }
        }

        return [
            "hascurrent" => $current !== null,
            "current" => $current,
            "currentlabel" => get_string("yourchoice", "topicchoice"),
            "items" => $items,
            "action" => (new moodle_url("/mod/topicchoice/choose.php"))->out(false),
            "cmid" => $this->cm->id,
            "sesskey" => sesskey(),
            "availabilitylabel" => get_string("availabilitylabel", "topicchoice"),
            "placeslabel" => get_string("places", "topicchoice"),
        ];
    }

    /**
     * Method choose.
     *
     * @param int $topicid Parameter topicid.
     * @param int $userid Parameter userid.
     * @return void Return value.
     */
    public function choose(int $topicid, int $userid): void {
        global $DB;

        $availability = $this->get_availability_state();
        if (!$availability["open"]) {
            throw new moodle_exception("notavailable", "topicchoice");
        }

        $factory = lock_config::get_lock_factory("mod_topicchoice_selection");
        $userlock = $factory->get_lock("activity:{$this->instance->id}:user:{$userid}", 10);
        if (!$userlock) {
            throw new moodle_exception("locktimeout", "topicchoice");
        }

        $topiclock = null;
        try {
            $topiclock = $factory->get_lock("activity:{$this->instance->id}:topic:{$topicid}", 10);
            if (!$topiclock) {
                throw new moodle_exception("locktimeout", "topicchoice");
            }

            $topic = $DB->get_record("topicchoice_topics", [
                "id" => $topicid,
                "topicchoiceid" => $this->instance->id,
                "active" => 1,
            ], "*", MUST_EXIST);

            $response = $this->get_user_response($userid);
            if ($response && (int)$response->topicid === $topicid) {
                return;
            }
            if ($response && empty($this->instance->allowchange)) {
                throw new moodle_exception("changeblocked", "topicchoice");
            }

            $selected = $DB->count_records("topicchoice_responses", [
                "topicchoiceid" => $this->instance->id,
                "topicid" => $topicid,
            ]);
            if ($selected >= $topic->maxstudents) {
                throw new moodle_exception("topicfull", "topicchoice");
            }

            $now = time();
            if ($response) {
                $response->topicid = $topicid;
                $response->timemodified = $now;
                $DB->update_record("topicchoice_responses", $response);
                $event = response_updated::create([
                    "objectid" => $response->id,
                    "context" => context_module::instance($this->cm->id),
                    "relateduserid" => $userid,
                    "other" => ["topicid" => $topicid],
                ]);
            } else {
                $response = (object)[
                    "topicchoiceid" => $this->instance->id,
                    "topicid" => $topicid,
                    "userid" => $userid,
                    "timecreated" => $now,
                    "timemodified" => $now,
                ];
                $response->id = $DB->insert_record("topicchoice_responses", $response);
                $event = response_created::create([
                    "objectid" => $response->id,
                    "context" => context_module::instance($this->cm->id),
                    "relateduserid" => $userid,
                    "other" => ["topicid" => $topicid],
                ]);
            }
            $event->add_record_snapshot("topicchoice", $this->instance);
            $event->add_record_snapshot("topicchoice_responses", $response);
            $event->trigger();

            if (!empty($this->instance->completionsubmit)) {
                $completion = new completion_info($this->course);
                $completion->update_state($this->cm, COMPLETION_COMPLETE, $userid);
            }
        } finally {
            if ($topiclock) {
                $topiclock->release();
            }
            $userlock->release();
        }
    }

    /**
     * Method get_report_rows.
     *
     * @return array Return value.
     */
    public function get_report_rows(): array {
        global $DB;

        $sql = "SELECT r.id, r.userid, r.timemodified, t.name AS topicname,
                       u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {topicchoice_responses} r
                  JOIN {topicchoice_topics} t ON t.id = r.topicid
                  JOIN {user} u ON u.id = r.userid
                 WHERE r.topicchoiceid = :topicchoiceid
              ORDER BY t.sortorder ASC, u.lastname ASC, u.firstname ASC";
        return $DB->get_records_sql($sql, ["topicchoiceid" => $this->instance->id]);
    }
}
