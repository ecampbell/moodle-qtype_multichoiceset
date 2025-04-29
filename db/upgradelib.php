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
 * Upgrade library code for the multichoiceset question type.
 *
 * @package    qtype_multichoiceset
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class to convert multichoiceset question attempt data when upgrading to the new question engine.
 *
 * This class is used by the code in question/engine/upgrade/upgradelib.php.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoiceset_qe2_attempt_updater extends question_qtype_attempt_updater {
    /** @var array variable name => value */
    protected $order;

    /**
     * Return if the answer is blank (no responses selected)
     *
     * @param stdObject $state the question state
     * @return boolean
     */
    public function is_blank_answer($state) {
        // Blank multichoiceset answers are not empty strings, they rather end in a colon.
        return empty($state->answer) || substr($state->answer, -1) == ':';
    }

    /**
     * Return the right answer
     *
     * @return string
     */
    public function right_answer() {
        $rightbits = [];
        foreach ($this->question->options->answers as $ans) {
            if ($ans->fraction >= 0.000001) {
                $rightbits[] = $this->to_text($ans->answer);
            }
        }
        return implode('; ', $rightbits);
    }

    /**
     * Expand the answer response
     *
     * @param string $answer
     * @return string
     */
    protected function explode_answer($answer) {
        if (strpos($answer, ':') !== false) {
            list($order, $responses) = explode(':', $answer);
            return $responses;
        } else {
            // Sometimes, a bug means that a state is missing the <order>: bit,
            // We need to deal with that.
            $this->logger->log_assumption("Dealing with missing order information
                    in attempt at multiple choice question {$this->question->id}");
            return $answer;
        }
    }

    /**
     * Set the data elements for the question step
     *
     * @param stdObject $state the question state
     * @return array question response summary
     */
    public function response_summary($state) {
        $responses = $this->explode_answer($state->answer);
        if (!empty($responses)) {
            $responses = explode(',', $responses);
            $bits = [];
            foreach ($responses as $response) {
                if (array_key_exists($response, $this->question->options->answers)) {
                    $bits[] = $this->to_text(
                            $this->question->options->answers[$response]->answer);
                } else {
                    $this->logger->log_assumption("Dealing with a place where the
                            student selected a choice that was later deleted for
                            multiple choice question {$this->question->id}");
                    $bits[] = '[CHOICE THAT WAS LATER DELETED]';
                }
            }
            return implode('; ', $bits);
        } else {
            return null;
        }
    }

    /**
     * Test if the question response was selected
     *
     * @param stdObject $state the question state
     * @return boolean
     */
    public function was_answered($state) {
        $responses = $this->explode_answer($state->answer);
        return !empty($responses);
    }

    /**
     * Set the data elements for the first question step
     *
     * @param stdObject $state the question state
     * @param array $data the question data
     */
    public function set_first_step_data_elements($state, &$data) {
        if (!$state->answer) {
            return;
        }
        list($order, $responses) = explode(':', $state->answer);
        $data['_order'] = $order;
        $this->order = explode(',', $order);
    }

    /**
     * Supply any missing first step data
     *
     * @param array $data the question data
     */
    public function supply_missing_first_step_data(&$data) {
        $data['_order'] = implode(',', array_keys($this->question->options->answers));
    }

    /**
     * Set the data elements for the question step
     *
     * @param stdObject $state the question state
     * @param array $data the question data
     */
    public function set_data_elements_for_step($state, &$data) {
        $responses = $this->explode_answer($state->answer);
        $responses = explode(',', $responses);
        foreach ($this->order as $key => $ansid) {
            if (in_array($ansid, $responses)) {
                $data['choice' . $key] = 1;
            } else {
                $data['choice' . $key] = 0;
            }
        }
    }
}
