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
 * mod_form.php
 *
 * @package   mod_topicchoice
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/course/moodleform_mod.php");

/**
 * Class mod_topicchoice_mod_form.
 */
class mod_topicchoice_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return mixed Return value.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement("header", "general", get_string("general", "form"));
        $mform->addElement("text", "name", get_string("topicchoicename", "topicchoice"), ["size" => 64]);
        $mform->setType("name", PARAM_TEXT);
        $mform->addRule("name", null, "required", null, "client");
        $mform->addRule("name", get_string("maximumchars", "", 255), "maxlength", 255, "client");
        $this->standard_intro_elements();

        $mform->addElement("html", html_writer::tag("h3", get_string("topics", "topicchoice")));
        $mform->addElement("selectyesno", "allowchange", get_string("allowchange", "topicchoice"));
        $mform->setDefault("allowchange", 1);
        $mform->addHelpButton("allowchange", "allowchange", "topicchoice");

        $mform->addElement("selectyesno", "showavailability", get_string("showavailability", "topicchoice"));
        $mform->setDefault("showavailability", 1);

        $repeatarray = [];
        $repeatarray[] = $mform->createElement("text", "topic", get_string("topic", "topicchoice"));
        $repeatarray[] = $mform->createElement("text", "limit", get_string("maxstudents", "topicchoice"));
        $repeatarray[] = $mform->createElement("hidden", "topicid", 0);

        if ($this->_instance) {
            $repeatno = $DB->count_records("topicchoice_topics", [
                    "topicchoiceid" => $this->_instance,
                    "active" => 1,
                ]) + 2;
        } else {
            $repeatno = 5;
        }

        $repeatoptions = [
            "topic" => ["type" => PARAM_TEXT],
            "limit" => ["type" => PARAM_INT, "default" => 1, "rule" => "numeric"],
            "topicid" => ["type" => PARAM_INT],
        ];

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeatoptions,
            "topic_repeats",
            "topic_add_fields",
            3,
            get_string("addtopics", "topicchoice"),
            true
        );

        if ($mform->elementExists("topic[0]")) {
            $mform->addRule("topic[0]", get_string("atleastonetopic", "topicchoice"), "required", null, "client");
        }

        $mform->addElement("html", html_writer::tag("h3", get_string("availability")));
        $mform->addElement("date_time_selector", "timeopen", get_string("opensat", "topicchoice"), ["optional" => true]);
        $mform->addElement("date_time_selector", "timeclose", get_string("closesat", "topicchoice"), ["optional" => true]);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return mixed Return value.
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if (empty($this->_instance)) {
            return;
        }

        $topics = $DB->get_records(
            "topicchoice_topics",
            ["topicchoiceid" => $this->_instance, "active" => 1],
            "sortorder ASC, id ASC"
        );

        $index = 0;
        foreach ($topics as $topic) {
            $defaultvalues["topic[{$index}]"] = $topic->name;
            $defaultvalues["limit[{$index}]"] = $topic->maxstudents;
            $defaultvalues["topicid[{$index}]"] = $topic->id;
            $index++;
        }
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return mixed Return value.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (!empty($data["timeopen"]) && !empty($data["timeclose"]) && $data["timeclose"] < $data["timeopen"]) {
            $errors["timeclose"] = get_string("closebeforeopen", "topicchoice");
        }

        $topics = $data["topic"] ?? [];
        $limits = $data["limit"] ?? [];
        $topicids = $data["topicid"] ?? [];
        $seen = [];
        $validtopics = 0;

        foreach ($topics as $index => $name) {
            $name = trim((string)$name);
            if ($name === "") {
                continue;
            }

            $validtopics++;
            $key = core_text::strtolower($name);
            if (isset($seen[$key])) {
                $errors["topic[{$index}]"] = get_string("duplicatetopic", "topicchoice");
            }
            $seen[$key] = true;

            $limit = (int)($limits[$index] ?? 0);
            if ($limit < 1) {
                $errors["limit[{$index}]"] = get_string("invalidlimit", "topicchoice");
            }

            $topicid = (int)($topicids[$index] ?? 0);
            if ($topicid > 0 && $this->_instance) {
                $current = $DB->count_records("topicchoice_responses", [
                    "topicchoiceid" => $this->_instance,
                    "topicid" => $topicid,
                ]);
                if ($limit > 0 && $limit < $current) {
                    $errors["limit[{$index}]"] = get_string("limitbelowcurrent", "topicchoice", $current);
                }
            }
        }

        if ($validtopics === 0) {
            $errors["topic[0]"] = get_string("atleastonetopic", "topicchoice");
        }

        return $errors;
    }

    /**
     * Method add_completion_rules.
     *
     * @return mixed Return value.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $element = "completionsubmit" . $suffix;
        $mform->addElement("checkbox", $element, "", get_string("completionsubmit", "topicchoice"));
        $mform->setDefault($element, 1);
        return [$element];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return mixed Return value.
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data["completionsubmit" . $suffix]);
    }

    /**
     * Method data_postprocessing.
     *
     * @param mixed $data Parameter data.
     * @return mixed Return value.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            if (empty($data->{"completionsubmit" . $suffix})) {
                $data->{"completionsubmit" . $suffix} = 0;
            }
        }
    }
}
