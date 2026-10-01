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
 * choose.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use mod_topicchoice\choice_manager;

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$topicid = required_param("topicid", PARAM_INT);

require_sesskey();

$cm = get_coursemodule_from_id("topicchoice", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
$topicchoice = $DB->get_record("topicchoice", ["id" => $cm->instance], "*", MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability("mod/topicchoice:choose", $context);

$manager = new choice_manager($topicchoice, $cm, $course);
$manager->choose($topicid, $USER->id);

redirect(
    new moodle_url("/mod/topicchoice/view.php", ["id" => $cm->id]),
    get_string("choicesaved", "topicchoice"),
    null,
    notification::NOTIFY_SUCCESS
);
