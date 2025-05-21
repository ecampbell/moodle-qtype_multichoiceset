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
 * This file contains tests that walk mutichoice questions through various behaviours.
 *
 * Note, there are already lots of tests of the multichoice type in the behaviour
 * tests. (Search for test_question_maker::make_a_multichoice.) This file only
 * contains a few additional tests for problems that were found during testing.
 *
 * @package    qtype_multichoiceset
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/multichoiceset/tests/helper.php');

/**
 * Unit tests for the mutiple choice all or nothingquestion type.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class walkthrough_test extends qbehaviour_walkthrough_test_base {

    /**
     * Get deferred feedback test data.
     *
     * @return void
     */
    public function test_deferredfeedback_feedback_multichoiceset(): void {
        // Create a multichoiceset question.
        $mc = $dd = test_question_maker::make_question('multichoiceset');
        $mc->shuffleanswers = false;

        $this->start_attempt_at_question($mc, 'deferredfeedback', 2);
        $this->process_submission($mc->get_correct_response());
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_mc_checkbox_expectation('choice0', false, true),
                $this->get_contains_mc_checkbox_expectation('choice1', false, false),
                $this->get_contains_mc_checkbox_expectation('choice2', false, true),
                $this->get_contains_mc_checkbox_expectation('choice3', false, false),
                $this->get_contains_correct_expectation(),
                new question_pattern_expectation('/class="r0 correct"/'),
                new question_pattern_expectation('/class="r1"/'));
    }

    /**
     * Get deferred feedback test data.
     *
     * @return void
     */
    public function test_deferredfeedback_resume_multichoiceset_right_right(): void {

        // Create a multichoiceset question.
        $mc = $dd = test_question_maker::make_question('multichoiceset');
        $mc->shuffleanswers = false;

        $this->start_attempt_at_question($mc, 'deferredfeedback', 2);
        $this->process_submission($mc->get_correct_response());
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_mc_checkbox_expectation('choice0', false, true),
                $this->get_contains_mc_checkbox_expectation('choice1', false, false),
                $this->get_contains_mc_checkbox_expectation('choice2', false, true),
                $this->get_contains_mc_checkbox_expectation('choice3', false, false),
                $this->get_contains_correct_expectation(),
                new question_pattern_expectation('/class="r0 correct"/'),
                new question_pattern_expectation('/class="r1"/'));

        // Save the old attempt.
        $oldqa = $this->quba->get_question_attempt($this->slot);

        // Reinitialise.
        $this->setUp();
        $this->quba->set_preferred_behaviour('deferredfeedback');
        $this->slot = $this->quba->add_question($mc, 2);
        $this->quba->start_question_based_on($this->slot, $oldqa);

        // Verify.
        $this->check_current_state(question_state::$complete);
        $this->check_output_contains_lang_string('notchanged', 'question');
        $this->check_current_mark(null);
        $this->check_current_output(
               $this->get_contains_mc_checkbox_expectation('choice0', true, true),
                $this->get_contains_mc_checkbox_expectation('choice1', true, false),
                $this->get_contains_mc_checkbox_expectation('choice2', true, true),
                $this->get_contains_mc_checkbox_expectation('choice3', true, false));

        // Now resubmit.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_mc_checkbox_expectation('choice0', false, true),
                $this->get_contains_mc_checkbox_expectation('choice1', false, false),
                $this->get_contains_mc_checkbox_expectation('choice2', false, true),
                $this->get_contains_mc_checkbox_expectation('choice3', false, false),
                $this->get_contains_correct_expectation(),
                new question_pattern_expectation('/class="r0 correct"/'),
                new question_pattern_expectation('/class="r1"/'));
    }
}
