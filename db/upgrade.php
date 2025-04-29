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
 * All or nothing multiple choice question type upgrade code.
 *
 * @package    qtype_multichoiceset
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Converts the multichoiceset info and writes it into the question.xml
 *
 * @param int $oldversion the old (i.e. current) version of Moodle
 */
function xmldb_qtype_multichoiceset_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011010400) {

        // Define field correctfeedbackformat to be added to question_multichoiceset.
        $table = new xmldb_table('question_multichoiceset');
        $field = new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2',
                null, XMLDB_NOTNULL, null, '0', 'correctfeedback');

        // Conditionally launch add field correctfeedbackformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field incorrectfeedbackformat to be added to question_multichoiceset.
        $field = new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2',
                null, XMLDB_NOTNULL, null, '0', 'incorrectfeedback');

        // Conditionally launch add field incorrectfeedbackformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // In the past, the correctfeedback, partiallycorrectfeedback,
        // incorrectfeedback columns were assumed to contain content of the same
        // form as questiontextformat. If we are using the HTML editor, then
        // convert FORMAT_MOODLE content to FORMAT_HTML.

        // Because this question type was updated later than the core types,
        // the available/relevant version dates make it hard to differentiate
        // early 2.0 installs from 1.9 updates, hence the extra check for
        // the presence of oldquestiontextformat.
        $table = new xmldb_table('question');
        $field = new xmldb_field('oldquestiontextformat');
        if ($dbman->field_exists($table, $field)) {
            $rs = $DB->get_recordset_sql('
                    SELECT qm.*, q.oldquestiontextformat
                    FROM {question_multichoiceset} qm
                    JOIN {question} q ON qm.question = q.id');
            foreach ($rs as $record) {
                if ($CFG->texteditors !== 'textarea' && $record->oldquestiontextformat == FORMAT_MOODLE) {
                    $record->correctfeedback = text_to_html($record->correctfeedback, false, false, true);
                    $record->correctfeedbackformat = FORMAT_HTML;
                    $record->incorrectfeedback = text_to_html($record->incorrectfeedback, false, false, true);
                    $record->incorrectfeedbackformat = FORMAT_HTML;
                } else {
                    $record->correctfeedbackformat = $record->oldquestiontextformat;
                    $record->incorrectfeedbackformat = $record->oldquestiontextformat;
                }
                $DB->update_record('question_multichoiceset', $record);
            }
            $rs->close();
        }
        // Record that qtype_multichoiceset savepoint reached.
        upgrade_plugin_savepoint(true, 2011010400, 'qtype', 'multichoiceset');
    }

    // Add new shownumcorrect field. If this is true, then when the user gets a
    // multiple-response question partially correct, tell them how many choices
    // they got correct alongside the feedback.
    if ($oldversion < 2011011200) {

        // Define field shownumcorrect to be added to question_multichoiceset.
        $table = new xmldb_table('question_multichoiceset');
        $field = new xmldb_field('shownumcorrect', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'answernumbering');

        // Launch add field shownumcorrect.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Record that qtype_multichoiceset savepoint reached.
        upgrade_plugin_savepoint(true, 2011011200, 'qtype', 'multichoiceset');
    }

    // Moodle v2.1.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.6.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013110500) {
        // Find duplicate rows before they break the2013110504 step below.
        $problemids = $DB->get_recordset_sql("
                SELECT question, MIN(id) AS recordidtokeep
                  FROM {question_multichoiceset}
              GROUP BY question
                HAVING COUNT(1) > 1
                ");
        foreach ($problemids as $problem) {
            $DB->delete_records_select('question_multichoiceset',
                    'question = ? AND id > ?',
                    [$problem->question, $problem->recordidtokeep]);
        }
        $problemids->close();

        // Record that qtype_multichoiceset savepoint reached.
        upgrade_plugin_savepoint(true, 2013110500, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2013110501) {

        // Define table question_multichoiceset to be renamed to qtype_multichoiceset_options.
        $table = new xmldb_table('question_multichoiceset');

        // Launch rename table for question_multichoiceset.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'qtype_multichoiceset_options');
        }

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2013110501, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2013110502) {

        // Define key question (foreign) to be dropped form qtype_multichoiceset_options.
        $table = new xmldb_table('qtype_multichoiceset_options');
        $key = new xmldb_key('question', XMLDB_KEY_FOREIGN, ['question'], 'question', ['id']);

        // Launch drop key question.
        $dbman->drop_key($table, $key);

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2013110502, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2013110503) {

        // Rename field question on table qtype_multichoiceset_options to questionid.
        $table = new xmldb_table('qtype_multichoiceset_options');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field question.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionid');
        }

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2013110503, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2013110504) {

        // Define key questionid (foreign-unique) to be added to qtype_multichoiceset_options.
        $table = new xmldb_table('qtype_multichoiceset_options');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, ['questionid'], 'question', ['id']);

        // Launch add key questionid.
        $dbman->add_key($table, $key);

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2013110504, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2013110505) {

        // Define field answers to be dropped from qtype_multichoiceset_options.
        $table = new xmldb_table('qtype_multichoiceset_options');
        $field = new xmldb_field('answers');

        // Conditionally launch drop field answers.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2013110505, 'qtype', 'multichoiceset');
    }

    if ($oldversion < 2015040100) {

        // Fix wrong component for combined feedback files.
        $params = ['component' => 'qtype_multichoiceset', 'filearea1' => 'correctfeedback', 'filearea2' => 'incorrectfeedback'];
        $sql = "component = :component AND (filearea = :filearea1 OR filearea = :filearea2)";
        $DB->set_field_select('files', 'component', 'question', $sql, $params);

        // Record that qtype_multichoiceset savepoint was reached.
        upgrade_plugin_savepoint(true, 2015040100, 'qtype', 'multichoiceset');
    }

    // Moodle v2.8.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.9.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.0.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.

    // Add a new checkbox for the question author to decide
    // Whether standard instruction ('Select one or more:') is displayed.
    $dbman = $DB->get_manager();
    $newversion = 2020111700;
    if ($oldversion < $newversion) {

        // Define field showstandardinstruction to be added to qtype_multichoiceset_options.
        $table = new xmldb_table('qtype_multichoiceset_options');
        $field = new xmldb_field('showstandardinstruction', XMLDB_TYPE_INTEGER, '2',
            null, XMLDB_NOTNULL, null, '1', 'shownumcorrect');

        // Conditionally launch add field showstandardinstruction.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Multichoice savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'multichoiceset');
    }
    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}


