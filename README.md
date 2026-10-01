# mod_topicchoice

Moodle activity for allocating seminar, project, laboratory or presentation topics with a fixed number of places per
topic.

## How it works

The teacher creates the available topics and defines the maximum number of students for each one. Students choose a
topic while places are available, and a topic automatically stops accepting selections when its capacity is reached.

Selection is protected with Moodle's Lock API so two students cannot take the same final place at the same time.
Teachers can optionally allow students to change their choice and can define opening and closing dates.

## Teacher view

The teacher report shows capacity, occupied places and enrolled students for each topic in real time. Activity completion
can be tied to making a topic selection.

The activity also preserves its configuration and selections through backup and restore and exposes stored user data
through Moodle's Privacy API.
