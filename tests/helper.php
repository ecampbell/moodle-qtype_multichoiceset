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
 * Contains the helper class for the select missing words question type tests.
 *
 * @package    qtype_multichoiceset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Test helper class for the all or nothing multichoice question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoiceset_test_helper {
    /**
     * Get dummy test questions.
     *
     * @return array
     */
    public function get_test_questions() {
        return ['two_of_four'];
    }

    /**
     * Makes a multichoiceset question with 2 correct answers.
     *
     * @return qtype_miltichoiceset_question
     */
    public function make_multichoiceset_question_two_of_four() {
        question_bank::load_question_definition_classes('multichoiceset');
        $mc = new qtype_multichoiceset_question();
        test_question_maker::initialise_a_question($mc);
        $mc->name = 'All or nothing multiple choice choice question';
        $mc->questiontext = 'Which are the odd numbers?';
        $mc->generalfeedback = 'The odd numbers are One and Three.';
        $mc->qtype = question_bank::get_qtype('multichoiceset');
        $mc->shuffleanswers = 1;
        $mc->answernumbering = 'abc';
        test_question_maker::set_standard_combined_feedback_fields($mc);
        $mc->answers = [
            13 => new question_answer(13, 'One', 1.0, 'One is odd.', FORMAT_HTML),
            14 => new question_answer(14, 'Two', 0, 'Two is even.', FORMAT_HTML),
            15 => new question_answer(15, 'Three', 1.0, 'Three is odd.', FORMAT_HTML),
            16 => new question_answer(16, 'Four', 0, 'Four is even.', FORMAT_HTML),
        ];
        return $mc;
    }

    /**
     * Get the question data, as it would be loaded by get_question_options.
     *
     * @return object
     */
    public static function get_multichoiceset_question_data_two_of_four() {
        global $USER;

        $qdata = new stdClass();

        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->qtype = 'multichoiceset';
        $qdata->name = 'All or nothing multiple choice choice question';
        $qdata->questiontext = 'Which are the odd numbers?';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'The odd numbers are One and Three.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;

        $qdata->options = new stdClass();
        $qdata->options->shuffleanswers = 1;
        $qdata->options->answernumbering = '123';
        $qdata->options->layout = 0;
        $qdata->options->correctfeedback
                = test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = 1;
        $qdata->options->incorrectfeedback
                = test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;

        $qdata->options->answers = [
            13 => (object) [
                'id' => 13,
                'answer' => 'One',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => '1.0',
                'feedback' => 'One is odd.',
                'feedbackformat' => FORMAT_HTML,
            ],
            14 => (object) [
                'id' => 14,
                'answer' => 'Two',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => '0.0',
                'feedback' => 'Two is even.',
                'feedbackformat' => FORMAT_HTML,
            ],
            15 => (object) [
                'id' => 15,
                'answer' => 'Three',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => '1.0',
                'feedback' => 'Three is odd.',
                'feedbackformat' => FORMAT_HTML,
            ],
            16 => (object) [
                'id' => 16,
                'answer' => 'Four',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => '0.0',
                'feedback' => 'Four is even.',
                'feedbackformat' => FORMAT_HTML,
            ],
        ];

        $qdata->hints = [
            1 => (object) [
                'hint' => 'Hint 1.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 1,
                'clearwrong' => 0,
                'options' => 0,
            ],
            2 => (object) [
                'hint' => 'Hint 2.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 1,
                'clearwrong' => 1,
                'options' => 1,
            ],
        ];

        return $qdata;
    }
    /**
     * Get the question data, as it would be loaded by get_question_options.
     *
     * @return object
     */
    public static function get_multichoiceset_question_form_data_two_of_four() {
        $qdata = new stdClass();

        $qdata->name = 'All or nothing multiple choice choice question';
        $qdata->questiontext = ['text' => 'Which are the odd numbers?', 'format' => FORMAT_HTML];
        $qdata->generalfeedback = ['text' => 'The odd numbers are One and Three.', 'format' => FORMAT_HTML];
        $qdata->defaultmark = 1;
        $qdata->noanswers = 5;
        $qdata->numhints = 2;
        $qdata->penalty = 0.3333333;

        $qdata->shuffleanswers = 1;
        $qdata->answernumbering = '123';
        $qdata->single = '0';
        $qdata->correctfeedback = ['text' => test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK,
                                                 'format' => FORMAT_HTML,
                                  ];
        $qdata->shownumcorrect = 1;
        $qdata->incorrectfeedback = ['text' => test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK,
                                                   'format' => FORMAT_HTML,
                                    ];
        $qdata->correctanswer = ['1', '0', '1', '0', '0'];
        $qdata->answer = [
            0 => [
                'text' => 'One',
                'format' => FORMAT_PLAIN,
            ],
            1 => [
                'text' => 'Two',
                'format' => FORMAT_PLAIN,
            ],
            2 => [
                'text' => 'Three',
                'format' => FORMAT_PLAIN,
            ],
            3 => [
                'text' => 'Four',
                'format' => FORMAT_PLAIN,
            ],
            4 => [
                'text' => '',
                'format' => FORMAT_PLAIN,
            ],
        ];

        $qdata->feedback = [
            0 => [
                'text' => 'One is odd.',
                'format' => FORMAT_HTML,
            ],
            1 => [
                'text' => 'Two is even.',
                'format' => FORMAT_HTML,
            ],
            2 => [
                'text' => 'Three is odd.',
                'format' => FORMAT_HTML,
            ],
            3 => [
                'text' => 'Four is even.',
                'format' => FORMAT_HTML,
            ],
            4 => [
                'text' => '',
                'format' => FORMAT_HTML,
            ],
        ];

        $qdata->hint = [
            0 => [
                'text' => 'Hint 1.',
                'format' => FORMAT_HTML,
            ],
            1 => [
                'text' => 'Hint 2.',
                'format' => FORMAT_HTML,
            ],
        ];
        $qdata->hintclearwrong = [0, 1];
        $qdata->hintshownumcorrect = [1, 1];

        return $qdata;
    }
}
