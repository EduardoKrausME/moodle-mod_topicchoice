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
 * index.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");
require_once($CFG->libdir . "/tablelib.php");

$id = required_param("id", PARAM_INT);
$course = $DB->get_record("course", ["id" => $id], "*", MUST_EXIST);

require_course_login($course);

$PAGE->set_url("/mod/topicchoice/index.php", ["id" => $course->id]);
$PAGE->set_title(get_string("modulenameplural", "topicchoice"));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course("topicchoice", $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulenameplural", "topicchoice"));

if (!$instances) {
    echo $OUTPUT->notification(get_string("noinstances", "topicchoice"), "info");
    echo $OUTPUT->footer();
    exit;
}

$table = new flexible_table("mod-topicchoice-index-{$course->id}");
$table->define_columns(["name", "topics", "responses"]);
$table->define_headers([
    get_string("name"),
    get_string("topics", "topicchoice"),
    get_string("responses", "topicchoice"),
]);
$table->define_baseurl($PAGE->url);
$table->set_attribute("class", "generaltable generalbox");
$table->setup();

foreach ($instances as $instance) {
    $topiccount = $DB->count_records("topicchoice_topics", ["topicchoiceid" => $instance->id, "active" => 1]);
    $responsecount = $DB->count_records("topicchoice_responses", ["topicchoiceid" => $instance->id]);
    $table->add_data([
        html_writer::link(new moodle_url("/mod/topicchoice/view.php", ["id" => $instance->coursemodule]),
            format_string($instance->name)),
        $topiccount,
        $responsecount,
    ]);
}
$table->finish_output();

echo $OUTPUT->footer();
