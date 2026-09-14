# mod_topicchoice

Moodle activity for allocating seminar, project, laboratory or presentation topics with a fixed number of places per topic.

## Main features

- Teacher creates any number of topics and defines the maximum number of students for each topic.
- Topics automatically stop accepting selections when they reach capacity.
- Concurrency-safe selection using Moodle's Lock API, preventing two users from taking the same final place.
- Optional student topic changes.
- Optional open and close dates.
- Real-time teacher report with capacity, occupied places and enrolled students.
- Activity completion when the student selects a topic.
- Privacy API and backup/restore support.

## Compatibility

Moodle 4.5 or later.
