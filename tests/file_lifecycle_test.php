<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Tests for multichoiceset file lifecycle handling.
 *
 * @package    qtype_multichoiceset
 * @copyright  2026 PD Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_multichoiceset;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/type/multichoiceset/questiontype.php');

/**
 * Test subclass exposing protected file cleanup.
 */
class testable_qtype_multichoiceset extends \qtype_multichoiceset {

    /**
     * Expose delete_files() for testing.
     *
     * @param int $questionid Question ID.
     * @param int $contextid Context ID.
     */
    public function test_delete_files(int $questionid, int $contextid): void {
        $this->delete_files($questionid, $contextid);
    }
}

/**
 * Tests for files owned by multichoiceset questions.
 */
final class file_lifecycle_test extends \advanced_testcase {

    /**
     * Hint files should move with the question and be deleted during cleanup.
     */
    public function test_hint_file_move_and_delete(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $oldcategory = $generator->create_question_category();
        $newcategory = $generator->create_question_category();

        $question = $generator->create_question(
                'multichoiceset',
                'two_of_four',
                ['category' => $oldcategory->id]
        );

        $hint = new \stdClass();
        $hint->questionid = $question->id;
        $hint->hint = 'Hint with a file';
        $hint->hintformat = FORMAT_HTML;
        $hint->shownumcorrect = 0;
        $hint->clearwrong = 0;
        $hint->options = 1;
        $hint->id = $DB->insert_record('question_hints', $hint);

        $fs = get_file_storage();

        $fileinfo = [
            'contextid' => $oldcategory->contextid,
            'component' => 'question',
            'filearea' => 'hint',
            'itemid' => $hint->id,
            'filepath' => '/',
            'filename' => 'hint.txt',
        ];

        $fs->create_file_from_string($fileinfo, 'Hint attachment');

        $this->assertTrue($fs->file_exists(
                $oldcategory->contextid,
                'question',
                'hint',
                $hint->id,
                '/',
                'hint.txt'
        ));

        $qtype = new testable_qtype_multichoiceset();

        $qtype->move_files(
                $question->id,
                $oldcategory->contextid,
                $newcategory->contextid
        );

        $this->assertFalse($fs->file_exists(
                $oldcategory->contextid,
                'question',
                'hint',
                $hint->id,
                '/',
                'hint.txt'
        ));

        $this->assertTrue($fs->file_exists(
                $newcategory->contextid,
                'question',
                'hint',
                $hint->id,
                '/',
                'hint.txt'
        ));

        $qtype->test_delete_files(
                $question->id,
                $newcategory->contextid
        );

        $this->assertFalse($fs->file_exists(
                $newcategory->contextid,
                'question',
                'hint',
                $hint->id,
                '/',
                'hint.txt'
        ));
    }
}