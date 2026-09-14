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
 * view.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);

$cm = get_coursemodule_from_id("topicchoice", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
$topicchoice = $DB->get_record("topicchoice", ["id" => $cm->instance], "*", MUST_EXIST);

require_course_login($course, true, $cm);
require_capability("mod/topicchoice:view", context_module::instance($cm->id));

$context = context_module::instance($cm->id);
$manager = new \mod_topicchoice\choice_manager($topicchoice, $cm, $course);

$event = \mod_topicchoice\event\course_module_viewed::create([
    "objectid" => $topicchoice->id,
    "context" => $context,
]);
$event->add_record_snapshot("course", $course);
$event->add_record_snapshot("topicchoice", $topicchoice);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url("/mod/topicchoice/view.php", ["id" => $cm->id]);
$PAGE->set_title(format_string($topicchoice->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($topicchoice->name));

if (trim((string)$topicchoice->intro) !== "") {
    echo $OUTPUT->box(format_module_intro("topicchoice", $topicchoice, $cm->id), "generalbox mod_introbox", "topicchoiceintro");
}

if (has_capability("mod/topicchoice:viewreport", $context)) {
    echo $OUTPUT->single_button(
        new moodle_url("/mod/topicchoice/report.php", ["id" => $cm->id]),
        get_string("report", "topicchoice"),
        "get"
    );
}

$availability = $manager->get_availability_state();
if (!$availability["open"]) {
    echo $OUTPUT->notification($availability["message"], "info");
}

$canchoose = has_capability("mod/topicchoice:choose", $context);
$data = $manager->get_view_data($USER->id, $canchoose && $availability["open"]);

echo $OUTPUT->render_from_template("mod_topicchoice/topic_list", $data);

echo $OUTPUT->footer();
