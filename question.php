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
 * Multiple choice question definition classes.
 *
 * @package    qtype_multichoiceset
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/multichoice/question.php');

/**
 * Represents an All-or-Nothing multiple response question.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoiceset_question extends qtype_multichoice_multi_question {

    /**
     * @var int standard instruction to be displayed if enabled.
     */
    public $showstandardinstruction = 0;

    /**
     * Get the grade
     *
     * Calculate the grade based on the users response
     *
     * @param array $response responses, as returned by question_attempt_step::get_qt_data()
     * @return array fraction and state
     */
    public function grade_response(array $response) {
        $fraction = 0;
        list($numright, $total) = $this->get_num_parts_right($response);
        $numwrong = $this->get_num_selected_choices($response) - $numright;
        $numcorrect = $this->get_num_correct_choices();
        if ($numwrong == 0 && $numcorrect == $numright) {
            $fraction = 1;
        }

        $state = question_state::graded_state_for_fraction($fraction);

        return array($fraction, $state);
    }

    /**
     * Disable hint settings if too many choices selected
     *
     * Disable those hint settings that we don't want when the student has selected
     * more choices than the number of right choices. This avoids giving the game away.
     * @param question_hint_with_parts $hint a hint.
     */
    protected function disable_hint_settings_when_too_many_selected(
            question_hint_with_parts $hint) {
        parent::disable_hint_settings_when_too_many_selected($hint);
        $hint->showchoicefeedback = false;
    }
}
