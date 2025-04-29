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
 * The questiontype class for the multiple choice question type.
 *
 * @package    qtype_multichoiceset
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * The multiple choice all or nothing question type.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoiceset extends question_type {
    /**
     * Can the answer fields contain HTML?
     *
     * @return bool return true if restore_decode_content_links_worker should be called.
     */
    public function has_html_answers() {
        return true;
    }

    /**
     * Get the question options.
     *
     * @param stdObject $question the question
     * @return void
     */
    public function get_question_options($question) {
        global $DB, $OUTPUT;
        $question->options = $DB->get_record('qtype_multichoiceset_options',
                ['questionid' => $question->id], '*', MUST_EXIST);
        if ($question->options === false) {
            // If this has happened, then we have a problem.
            // For the user to be able to edit or delete this question, we need options.
            debugging("Question ID {$question->id} was missing an options record. Using default.", DEBUG_DEVELOPER);

            $question->options = $this->create_default_options($question);
        }

        parent::get_question_options($question);
    }

    /**
     * Create a default options object for the provided question.
     *
     * @param object $question The queston we are working with.
     * @return object The options object.
     */
    protected function create_default_options($question) {
        // Create a default question options record.
        $options = new stdClass();
        $options->questionid = $question->id;

        // Get the default strings and just set the format.
        $options->correctfeedback = get_string('correctfeedbackdefault', 'question');
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = get_string('partiallycorrectfeedbackdefault', 'question');;
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = get_string('incorrectfeedbackdefault', 'question');
        $options->incorrectfeedbackformat = FORMAT_HTML;

        $config = get_config('qtype_multichoiceset');
        $options->single = $config->answerhowmany;
        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }
        $options->answernumbering = $config->answernumbering;
        $options->shuffleanswers = $config->shuffleanswers;
        $options->showstandardinstruction = 0;
        $options->shownumcorrect = 1;

        return $options;
    }

    /**
     * Save the question options.
     *
     * @param stdObject $question the question
     * @return stdObject error if there aren't at least 2 answers
     */
    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();

        $oldanswers = $DB->get_records('question_answers',
                ['question' => $question->id], 'id ASC');

        // Following hack to check at least two answers exist.
        $answercount = 0;
        foreach ($question->answer as $key => $answer) {
            if ($answer != '') {
                $answercount++;
            }
        }
        if ($answercount < 2) { // Check there are at lest 2 answers for multiple choice.
            $result->notice = get_string('notenoughanswers', 'qtype_multichoice', '2');
            return $result;
        }

        // Insert all the new answers.
        $totalfraction = 0;
        $maxfraction = -1;
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) == '') {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            if (is_array($answerdata)) {
                // Doing an import.
                $answer->answer = $this->import_or_save_files($answerdata,
                        $context, 'question', 'answer', $answer->id);
                $answer->answerformat = $answerdata['format'];
            } else {
                // Saving the form.
                $answer->answer = $answerdata;
                $answer->answerformat = FORMAT_HTML;
            }
            $answer->fraction = !empty($question->correctanswer[$key]);
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];

            $DB->update_record('question_answers', $answer);
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', ['id' => $oldanswer->id]);
        }

        $options = $DB->get_record('qtype_multichoiceset_options', ['questionid' => $question->id]);
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->incorrectfeedback = '';
            $options->showstandardinstruction = 0;
            $options->id = $DB->insert_record('qtype_multichoiceset_options', $options);
        }

        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }
        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;
        $options->showstandardinstruction = !empty($question->showstandardinstruction);
        $options->correctfeedback = $this->import_or_save_files($question->correctfeedback,
                $context, 'question', 'correctfeedback', $question->id);
        $options->correctfeedbackformat = $question->correctfeedback['format'];
        $options->incorrectfeedback = $this->import_or_save_files($question->incorrectfeedback,
                $context, 'question', 'incorrectfeedback', $question->id);
        $options->incorrectfeedbackformat = $question->incorrectfeedback['format'];
        $options->shownumcorrect = !empty($question->shownumcorrect);

        $DB->update_record('qtype_multichoiceset_options', $options);
        $this->save_hints($question, true);
    }

    /**
     * Save all hints.
     *
     * @param stdObject $formdata form data of question
     * @param bool $withparts whether the question has parts
     * @return stdObject
     */
    public function save_hints($formdata, $withparts = false) {
        global $DB;
        $context = $formdata->context;

        $oldhints = $DB->get_records('question_hints',
                ['questionid' => $formdata->id], 'id ASC');

        if (!empty($formdata->hint)) {
            $numhints = max(array_keys($formdata->hint)) + 1;
        } else {
            $numhints = 0;
        }

        if ($withparts) {
            if (!empty($formdata->hintclearwrong)) {
                $numclears = max(array_keys($formdata->hintclearwrong)) + 1;
            } else {
                $numclears = 0;
            }
            if (!empty($formdata->hintshownumcorrect)) {
                $numshows = max(array_keys($formdata->hintshownumcorrect)) + 1;
            } else {
                $numshows = 0;
            }
            $numhints = max($numhints, $numclears, $numshows);
        }

        if (!empty($formdata->hintshowchoicefeedback)) {
            $numshowfeedbacks = max(array_keys($formdata->hintshowchoicefeedback)) + 1;
        } else {
            $numshowfeedbacks = 0;
        }
        $numhints = max($numhints, $numshowfeedbacks);

        for ($i = 0; $i < $numhints; $i += 1) {
            if (html_is_blank($formdata->hint[$i]['text'])) {
                $formdata->hint[$i]['text'] = '';
            }

            if ($withparts) {
                $clearwrong = !empty($formdata->hintclearwrong[$i]);
                $shownumcorrect = !empty($formdata->hintshownumcorrect[$i]);
            }

            $showchoicefeedback = !empty($formdata->hintshowchoicefeedback[$i]);

            if (empty($formdata->hint[$i]['text']) && empty($clearwrong) &&
                    empty($shownumcorrect) && empty($showchoicefeedback)) {
                continue;
            }

            // Update an existing hint if possible.
            $hint = array_shift($oldhints);
            if (!$hint) {
                $hint = new stdClass();
                $hint->questionid = $formdata->id;
                $hint->hint = '';
                $hint->id = $DB->insert_record('question_hints', $hint);
            }

            $hint->hint = $this->import_or_save_files($formdata->hint[$i],
                    $context, 'question', 'hint', $hint->id);
            $hint->hintformat = $formdata->hint[$i]['format'];
            if ($withparts) {
                $hint->clearwrong = $clearwrong;
                $hint->shownumcorrect = $shownumcorrect;
            }
            $hint->options = $showchoicefeedback;
            $DB->update_record('question_hints', $hint);
        }

        // Delete any remaining old hints.
        $fs = get_file_storage();
        foreach ($oldhints as $oldhint) {
            $fs->delete_area_files($context->id, 'question', 'hint', $oldhint->id);
            $DB->delete_records('question_hints', ['id' => $oldhint->id]);
        }
    }

    /**
     * Make a hint object.
     *
     * @param stdObject $hint a hint
     * @return stdObject
     */
    protected function make_hint($hint) {
        return qtype_multichoiceset_hint::load_from_record($hint);
    }

    /**
     * Make a question instance.
     *
     * @param stdObject $questiondata the question data
     * @return stdObject
     */
    protected function make_question_instance($questiondata) {
        question_bank::load_question_definition_classes($this->name());
        $class = 'qtype_multichoiceset_question';
        return new $class();
    }

    /**
     * Initialise the question instance.
     *
     * @param question_definition $question the question_definition we are creating
     * @param stdObject $questiondata the question data
     * @return void
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->answernumbering = $questiondata->options->answernumbering;
        $question->showstandardinstruction = $questiondata->options->showstandardinstruction;
        if (!empty($questiondata->options->layout)) {
            $question->layout = $questiondata->options->layout;
        } else {
            $question->layout = qtype_multichoice_single_question::LAYOUT_VERTICAL;
        }
        $question->correctfeedback = $questiondata->options->correctfeedback;
        $question->correctfeedbackformat = $questiondata->options->correctfeedbackformat;
        $question->incorrectfeedback = $questiondata->options->incorrectfeedback;
        $question->incorrectfeedbackformat = $questiondata->options->incorrectfeedbackformat;
        $question->shownumcorrect = $questiondata->options->shownumcorrect;

        $this->initialise_question_answers($question, $questiondata, false);
    }

    /**
     * Make an answer.
     *
     * @param stdObject $answer the answer
     * @return stdObject
     */
    public function make_answer($answer) {
        // Overridden just so we can make it public for use by question.php.
        return parent::make_answer($answer);
    }

    /**
     * Delete the question.
     *
     * @param int $questionid the question ID
     * @param stdObject $contextid the context ID
     * @return stdObject
     */
    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_multichoiceset_options', ['questionid' => $questionid]);
        return parent::delete_question($questionid, $contextid);
    }

    /**
     * Get the number of correct response choices.
     *
     * @param stdObject $questiondata the question data
     * @return int
     */
    protected function get_num_correct_choices($questiondata) {
        $numright = 0;
        foreach ($questiondata->options->answers as $answer) {
            if (!question_state::graded_state_for_fraction($answer->fraction)->is_incorrect()) {
                $numright += 1;
            }
        }
        return $numright;
    }

    /**
     * Get the score if random response chosen - but not computed for this question type.
     *
     * @param stdObject $questiondata the question data
     * @return stdObject
     */
    public function get_random_guess_score($questiondata) {
        // Pretty much impossible to compute for _multi questions. Don't try.
        return null;
    }

    /**
     * Get the possible responses to the question.
     *
     * @param stdObject $questiondata the question data
     * @return array array of question parts
     */
    public function get_possible_responses($questiondata) {
        $parts = [];

        foreach ($questiondata->options->answers as $aid => $answer) {
            $parts[$aid] = [$aid => new question_possible_response(
                            html_to_text(format_text(
                            $answer->answer, $answer->answerformat, ['noclean' => true]),
                            0, false), $answer->fraction),
                           ];
        }

        return $parts;
    }

    /**
     * Get the available question numbering styles.
     *
     * @return array of the numbering styles supported. For each one, there
     *      should be a lang string answernumberingxxx in the qtype_multichoice
     *      language file, and a case in the switch statement in number_in_style,
     *      and it should be listed in the definition of this column in install.xml.
     */
    public static function get_numbering_styles() {
        $styles = [];
        foreach (['abc', 'ABCD', '123', 'iii', 'IIII', 'none'] as $numberingoption) {
            $styles[$numberingoption]
                    = get_string('answernumbering' . $numberingoption, 'qtype_multichoice');
        }
        return $styles;
    }

    /**
     * Move files from old to new context.
     *
     * @param int $questionid the question ID
     * @param stdObject $oldcontextid the source context ID
     * @param stdObject $newcontextid the destination context ID
     * @return void
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);

        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'correctfeedback', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'incorrectfeedback', $questionid);
    }

    /**
     * Delete any files in the context.
     *
     * @param int $questionid the question ID
     * @param stdObject $contextid the context ID
     * @return void
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $fs->delete_area_files($contextid, 'question', 'correctfeedback', $questionid);
        $fs->delete_area_files($contextid, 'question', 'incorrectfeedback', $questionid);
    }


    // IMPORT EXPORT FUNCTIONS.

    /**
     * Provide export functionality for xml format.
     *
     * @param stdObject $question the question object
     * @param qformat_xml $format the format object so that helper methods can be used
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $expout = '';
        $fs = get_file_storage();
        $contextid = $question->contextid;

        $expout .= "    <shuffleanswers>".$format->get_single($question->options->shuffleanswers)."</shuffleanswers>\n";

        $textformat = $format->get_format($question->options->correctfeedbackformat);
        $files = $fs->get_area_files($contextid, 'question', 'correctfeedback', $question->id);
        $expout .= "    <correctfeedback format=\"$textformat\">\n"
                . '      ' . $format->writetext($question->options->correctfeedback);
        $expout .= $format->write_files($files);
        $expout .= "    </correctfeedback>\n";

        $textformat = $format->get_format($question->options->incorrectfeedbackformat);
        $files = $fs->get_area_files($contextid, 'question', 'incorrectfeedback', $question->id);
        $expout .= "    <incorrectfeedback format=\"$textformat\">\n"
                . '      ' . $format->writetext($question->options->incorrectfeedback);
        $expout .= $format->write_files($files);
        $expout .= "    </incorrectfeedback>\n";
        if (!empty($question->options->shownumcorrect)) {
            $expout .= "    <shownumcorrect/>\n";
        }
        $expout .= "    <answernumbering>{$question->options->answernumbering}</answernumbering>\n";
        $expout .= "    <showstandardinstruction>{$question->options->showstandardinstruction}</showstandardinstruction>\n";
        $expout .= $format->write_answers($question->options->answers);

        return $expout;
    }

    /**
     * Provide import functionality for xml format
     *
     * @param mixed $data the segment of data containing the question
     * @param stdObject $question question object processed (so far) by standard import code
     * @param qformat_xml $format the format object so that helper methods can be used (in particular error())
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return stdObject question object suitable for save_options() call or false if cannot handle
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        // Check question is for us.
        if (!isset($data['@']['type']) || $data['@']['type'] != 'multichoiceset') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'multichoiceset';

        $question->shuffleanswers = $format->trans_single(
                $format->getpath($data, ['#', 'shuffleanswers', 0, '#'], 1));

        $question->answernumbering = $format->getpath($data,
                ['#', 'answernumbering', 0, '#'], 'abc');

        $question->showstandardinstruction = $format->trans_single(
                 $format->getpath($data, ['#', 'showstandardinstruction', 0, '#'], 1));

        $question->correctfeedback = [];
        $question->correctfeedback['text'] = $format->getpath($data, ['#', 'correctfeedback', 0, '#', 'text', 0, '#'],
                '', true);
        $question->correctfeedback['format'] = $format->trans_format(
                 $format->getpath($data, ['#', 'correctfeedback', 0, '@', 'format'],
                 $format->get_format($question->questiontextformat)));
        $question->correctfeedback['files'] = [];
        // Restore files in correctfeedback.
        $files = $format->getpath($data, ['#', 'correctfeedback', 0, '#', 'file'], [], false);
        foreach ($files as $file) {
            $filesdata = new stdclass;
            $filesdata->content = $file['#'];
            $filesdata->encoding = $file['@']['encoding'];
            $filesdata->name = $file['@']['name'];
            $question->correctfeedback['files'][] = $filesdata;
        }

        $question->incorrectfeedback = [];
        $question->incorrectfeedback['text'] = $format->getpath($data, ['#', 'incorrectfeedback', 0, '#', 'text', 0, '#'],
                '', true );
        $question->incorrectfeedback['format'] = $format->trans_format(
                $format->getpath($data, ['#', 'incorrectfeedback', 0, '@', 'format'],
                $format->get_format($question->questiontextformat)));
        $question->incorrectfeedback['files'] = [];
        // Restore files in incorrectfeedback.
        $files = $format->getpath($data, ['#', 'incorrectfeedback', 0, '#', 'file'], [], false);
        foreach ($files as $file) {
            $filesdata = new stdclass;
            $filesdata->content = $file['#'];
            $filesdata->encoding = $file['@']['encoding'];
            $filesdata->name = $file['@']['name'];
            $question->incorrectfeedback['files'][] = $filesdata;
        }

        $question->shownumcorrect = array_key_exists('shownumcorrect', $data['#']);

        // Run through the answers.
        $answers = $data['#']['answer'];
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer, true,
                    $format->get_format($question->questiontextformat));
            $question->answer[] = $ans->answer;

            // FIX: Some tools set fraction to `0.0`
            // which leeds to false `true` answers.
            $question->correctanswer[] = !empty($ans->fraction) &&
                                         (float)$ans->fraction > 0;
            $question->feedback[] = $ans->feedback;

            // Backwards compatibility.
            if (array_key_exists('correctanswer', $answer['#'])) {
                $key = end(array_keys($question->correctanswer));
                $question->correctanswer[$key] = $format->getpath($answer,
                        ['#', 'correctanswer', 0, '#'], 0);
            }
        }

        $format->import_hints($question, $data, true, true,
                $format->get_format($question->questiontextformat));

        // Get extra choicefeedback setting from each hint.
        if (!empty($question->hintoptions)) {
            foreach ($question->hintoptions as $key => $options) {
                $question->hintshowchoicefeedback[$key] = !empty($options);
            }
        }
        return $question;
    }

    /**
     * Support export to wordtable and htmltable format plugins
     *
     * Just call the corresponding XML functions
     * cf. https://moodle.org/plugins/pluginversions.php?plugin=qformat_wordtable
     *
     * @param stdObject $question the question object
     * @param qformat_xml $format the format object so that helper methods can be used
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_wordtable($question, qformat_xml $format, $extra=null) {
        return $this->export_to_xml($question, $format, $extra);
    }

    /**
     * Support import from wordtable format plugin
     *
     * Just call the corresponding XML function
     * cf. https://moodle.org/plugins/pluginversions.php?plugin=qformat_wordtable
     *
     * @param mixed $data the segment of data containing the question
     * @param stdObject $question question object processed (so far) by standard import code
     * @param qformat_xml $format the format object so that helper methods can be used (in particular error())
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return stdObject question object suitable for save_options() call or false if cannot handle
     */
    public function import_from_wordtable($data, $question, qformat_xml $format, $extra=null) {
        return $this->import_from_xml($data, $question, $format, $extra);
    }

    /**
     * Support export to htmltable format plugin
     *
     * Just call the corresponding XML functions
     * cf. https://moodle.org/plugins/pluginversions.php?plugin=qformat_htmltable
     *
     * @param stdObject $question the question object
     * @param qformat_xml $format the format object so that helper methods can be used
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_htmltable($question, qformat_xml $format, $extra=null) {
        return $this->export_to_xml($question, $format, $extra);
    }

}

/**
 * An extension of function question_hint_with_parts() for multichoiceset questions.
 *
 * This has an extra option for whether to show the feedback for each choice.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoiceset_hint extends question_hint_with_parts {
    /** @var bool whether to show the feedback for each choice. */
    public $showchoicefeedback;

    /**
     * Constructor.
     *
     * @param int $id Question ID
     * @param string $hint The hint text
     * @param int $hintformat
     * @param bool $shownumcorrect whether the number of right parts should be shown
     * @param bool $clearwrong whether the wrong parts should be reset.
     * @param bool $showchoicefeedback whether to show the feedback for each choice.
     */
    public function __construct($id, $hint, $hintformat, $shownumcorrect,
            $clearwrong, $showchoicefeedback) {
        parent::__construct($id, $hint, $hintformat, $shownumcorrect, $clearwrong);
        $this->showchoicefeedback = $showchoicefeedback;
    }

    /**
     * Create a basic hint from a row loaded from the question_hints table in the database.
     *
     * @param object $row with $row->hint, ->shownumcorrect and ->clearwrong set.
     * @return question_hint_with_parts
     */
    public static function load_from_record($row) {
        return new qtype_multichoiceset_hint($row->id, $row->hint, $row->hintformat,
                $row->shownumcorrect, $row->clearwrong, !empty($row->options));
    }

    /**
     * Adjust the display options
     *
     * @param question_display_options $options display options
     * @return void
     */
    public function adjust_display_options(question_display_options $options) {
        parent::adjust_display_options($options);
        $options->suppresschoicefeedback = !$this->showchoicefeedback;
    }
}
