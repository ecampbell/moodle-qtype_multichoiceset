<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace qtype_multichoiceset;

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade tests for qtype_multichoiceset.
 *
 * @package    qtype_multichoiceset
 * @copyright  2026 Park Derochie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upgrade_test extends \advanced_testcase {

    /**
     * Test recovery when questions survive but the qtype options table is missing.
     */
    public function test_recover_missing_options_table(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
	require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/question/type/multichoiceset/db/upgrade.php');

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();

        $question = $generator->create_question(
                'multichoiceset',
                'two_of_four',
                ['category' => $category->id]
        );

        // Capture standard Moodle data that must survive recovery unchanged.
        $answersbefore = $DB->get_records(
                'question_answers',
                ['question' => $question->id],
                'id ASC'
        );

        $this->assertCount(4, $answersbefore);

        $fractionsbefore = array_map(
                static fn($answer) => (float) $answer->fraction,
                array_values($answersbefore)
        );

        $this->assertSame([1.0, 0.0, 1.0, 0.0], $fractionsbefore);

        // Reproduce the production failure: questions and answers survive,
        // but the qtype-specific options table is missing.
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('qtype_multichoiceset_options');

        $this->assertTrue($dbman->table_exists($table));

        $dbman->drop_table($table);

        $this->assertFalse($dbman->table_exists($table));

	// Reproduce the plugin version recorded on the affected production
        // installation so upgrade_plugin_savepoint() sees a real upgrade.
	$DB->set_field(
                'config_plugins',
                'value',
                '2022081100',
                [
                    'plugin' => 'qtype_multichoiceset',
                    'name' => 'version',
                ]
	);

        // Run the recovery upgrade from the version recorded on the affected
        // production installation.
        xmldb_qtype_multichoiceset_upgrade(2022081100);

        $this->assertTrue($dbman->table_exists($table));

        $options = $DB->get_record(
                'qtype_multichoiceset_options',
                ['questionid' => $question->id],
                '*',
                MUST_EXIST
        );

        // Recovered historical questions use conservative historical defaults.
        $this->assertSame(0, (int) $options->layout);
        $this->assertSame(1, (int) $options->shuffleanswers);
        $this->assertSame('', $options->correctfeedback);
        $this->assertSame(0, (int) $options->correctfeedbackformat);
        $this->assertSame('', $options->incorrectfeedback);
        $this->assertSame(0, (int) $options->incorrectfeedbackformat);
        $this->assertSame('abc', $options->answernumbering);
        $this->assertSame(0, (int) $options->shownumcorrect);
        $this->assertSame(1, (int) $options->showstandardinstruction);

        // Standard question/answer data must remain unchanged.
        $answersafter = $DB->get_records(
                'question_answers',
                ['question' => $question->id],
                'id ASC'
        );

        $this->assertSame(
                array_keys($answersbefore),
                array_keys($answersafter)
        );

        foreach ($answersbefore as $answerid => $before) {
            $after = $answersafter[$answerid];

            $this->assertSame($before->answer, $after->answer);
            $this->assertEquals($before->fraction, $after->fraction);
            $this->assertSame($before->feedback, $after->feedback);
        }

        // The recovered historical row stays enabled for the standard
        // instruction, while the schema default for future rows is disabled.
        $field = new \xmldb_field('showstandardinstruction');
        $columns = $DB->get_columns('qtype_multichoiceset_options');

        $this->assertArrayHasKey($field->getName(), $columns);
        $this->assertEquals(0, $columns[$field->getName()]->default_value);
        $this->assertSame(1, (int) $options->showstandardinstruction);
    }
}
