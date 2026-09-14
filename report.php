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
 * report.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");
require_once($CFG->libdir . "/tablelib.php");

$id = required_param("id", PARAM_INT);

$cm = get_coursemodule_from_id("topicchoice", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
$topicchoice = $DB->get_record("topicchoice", ["id" => $cm->instance], "*", MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability("mod/topicchoice:viewreport", $context);

$PAGE->set_url("/mod/topicchoice/report.php", ["id" => $cm->id]);
$PAGE->set_title(get_string("report", "topicchoice"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$manager = new \mod_topicchoice\choice_manager($topicchoice, $cm, $course);
$topics = $manager->get_topics(true);
$counts = $manager->get_counts();

$event = \mod_topicchoice\event\report_viewed::create([
    "objectid" => $topicchoice->id,
    "context" => $context,
]);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("reportfor", "topicchoice", format_string($topicchoice->name)));

$summary = new flexible_table("mod-topicchoice-summary-{$cm->id}");
$summary->define_columns(["topic", "max", "selected", "available", "status"]);
$summary->define_headers([
    get_string("topic", "topicchoice"),
    get_string("maxstudents", "topicchoice"),
    get_string("selected", "topicchoice"),
    get_string("available", "topicchoice"),
    get_string("status"),
]);
$summary->define_baseurl($PAGE->url);
$summary->set_attribute("class", "generaltable generalbox");
$summary->setup();

foreach ($topics as $topic) {
    $selected = $counts[$topic->id] ?? 0;
    $available = max(0, $topic->maxstudents - $selected);
    if (!$topic->active) {
        $status = get_string("inactive", "topicchoice");
    } else if ($selected >= $topic->maxstudents) {
        $status = get_string("full", "topicchoice");
    } else {
        $status = get_string("open", "topicchoice");
    }
    $summary->add_data([
        format_string($topic->name),
        $topic->maxstudents,
        $selected,
        $available,
        $status,
    ]);
}
$summary->finish_output();

$rows = $manager->get_report_rows();
echo $OUTPUT->heading(get_string("participants", "topicchoice"), 3);

$table = new flexible_table("mod-topicchoice-participants-{$cm->id}");
$table->define_columns(["student", "topic", "time"]);
$table->define_headers([
    get_string("student", "topicchoice"),
    get_string("topic", "topicchoice"),
    get_string("selectedat", "topicchoice"),
]);
$table->define_baseurl($PAGE->url);
$table->set_attribute("class", "generaltable generalbox");
$table->setup();

foreach ($rows as $row) {
    $userurl = new moodle_url("/user/view.php", ["id" => $row->userid, "course" => $course->id]);
    $table->add_data([
        html_writer::link($userurl, fullname($row)),
        format_string($row->topicname),
        userdate($row->timemodified),
    ]);
}
$table->finish_output();

if (!$rows) {
    echo $OUTPUT->notification(get_string("noresponses", "topicchoice"), "info");
}

echo $OUTPUT->footer();
