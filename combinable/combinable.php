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
 * Defines the hooks necessary to make the oumultiresponse question type combinable
 *
 * @package   qtype_multichoiceset
 * @copyright  2019 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the hooks necessary to make the oumultiresponse question type combinable
 *
 * @copyright  2019 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_combinable_type_multichoiceset extends qtype_combined_combinable_type_base {

    /** @var Name of identifier */
    protected $identifier = 'allornothing';

    /**
     * Get the extra question properties
     *
     * @return array containing the numbering format
     */
    protected function extra_question_properties() {
        return array('answernumbering' => 'abc') + $this->combined_feedback_properties();
    }

    /**
     * Get the extra question properties
     *
     * @return array containing the numbering format
     */
    protected function extra_answer_properties() {
        return array('feedback' => array('text' => '', 'format' => FORMAT_PLAIN));
    }

    /**
     * Get the subquestion option fields
     *
     * @return array containing the options
     */
    public function subq_form_fragment_question_option_fields() {
        return array('shuffleanswers' => false);
    }

    /**
     * Process the subquestion data
     *
     * @param array $subqdata Subquestion data
     * @return array containing the answer properties
     */
    protected function transform_subq_form_data_to_full($subqdata) {
        $data = parent::transform_subq_form_data_to_full($subqdata);
        foreach ($data->answer as $anskey => $answer) {
            $data->answer[$anskey] = array('text' => $answer['text'], 'format' => $answer['format']);
        }
        return $this->add_per_answer_properties($data);
    }

    /**
     * Get the extra parameters for default
     *
     * @return string
     */
    protected function third_param_for_default_question_text() {
        return 'v';
    }
}

/**
 * Defines the hooks necessary to make the oumultiresponse question type combinable
 *
 * @copyright  2019 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_combinable_multichoiceset extends qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param {

    /**
     * Add a quick form to the basic form
     *
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param bool            $repeatenabled
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $mform->addElement('advcheckbox', $this->form_field_name('shuffleanswers'), get_string('shuffle', 'qtype_gapselect'));

        $answerels = array();
        $answerels[] = $mform->createElement('editor', $this->form_field_name('answer'),
                get_string('choiceno', 'qtype_multichoice', '{no}'), ['rows' => 1]);
        $mform->setType($this->form_field_name('answer'), PARAM_RAW);
        $answerels[] = $mform->createElement('advcheckbox', $this->form_field_name('correctanswer'),
                get_string('correct', 'question'), get_string('correct', 'question'));

        $answergroupel = $mform->createElement('group',
                $this->form_field_name('answergroup'),
                get_string('choiceno', 'qtype_multichoice', '{no}'),
                $answerels, null, false);
        if ($this->questionrec !== null) {
            $countanswers = count($this->questionrec->options->answers);
        } else {
            $countanswers = 0;
        }

        if ($repeatenabled) {
            $defaultstartnumbers = QUESTION_NUMANS_START * 2;
            $repeatsatstart = max($defaultstartnumbers, $countanswers + QUESTION_NUMANS_ADD);
        } else {
            $repeatsatstart = $countanswers;
        }

        $combinedform->repeat_elements(array($answergroupel),
            $repeatsatstart,
            array(),
            $this->form_field_name('noofchoices'),
            $this->form_field_name('morechoices'),
            QUESTION_NUMANS_ADD,
            get_string('addmorechoiceblanks', 'qtype_gapselect'),
            true);
    }

    /**
     * Fill in the form from the data
     *
     * @param context_module $context
     * @param array $fileoptions
     * @return array
     */
    public function data_to_form($context, $fileoptions) {
        $mroptions = array('answer' => array(), 'correctanswer' => array());
        if ($this->questionrec !== null) {
            foreach ($this->questionrec->options->answers as $questionrecanswer) {
                $mroptions['answer'][]['text'] = $questionrecanswer->answer;
                $mroptions['correctanswer'][] = $questionrecanswer->fraction > 0;
            }
        }
        return parent::data_to_form($context, $fileoptions) + $mroptions;
    }

    /**
     * Validate the input
     *
     * @return array of errors if any
     */
    public function validate() {
        $errors = array();
        $nonemptyanswerblanks = array();
        foreach ($this->formdata->answer as $anskey => $answer) {
            $answer = $answer['text'];
            if ('' !== trim($answer)) {
                $nonemptyanswerblanks[] = $anskey;
            } else if ($this->formdata->correctanswer[$anskey]) {
                $errors[$this->form_field_name("answergroup[{$anskey}]")] = get_string('err_correctanswerblank',
                                                                                       'qtype_multichoiceset');
            }
        }
        if (count($nonemptyanswerblanks) < 2) {
            $errors[$this->form_field_name("answergroup[0]")] = get_string('err_youneedmorechoices', 'qtype_multichoiceset');
        }
        if (count(array_filter($this->formdata->correctanswer)) === 0) {
            $errors[$this->form_field_name("answergroup[0]")] = get_string('err_nonecorrect', 'qtype_multichoiceset');
        }
        return $errors;
    }

    /**
     * Check if data has been submitted
     *
     * @return bool
     */
    public function has_submitted_data() {
        return $this->submitted_data_array_not_empty('correctanswer') ||
                $this->html_field_has_submitted_data($this->form_field_name('answer')) ||
                parent::has_submitted_data();
    }
}
