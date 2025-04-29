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
 * Backup handler for Moodle 1.x Multichoiceset questions
 *
 * @package    qtype_multichoiceset
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * multichoiceset question type conversion handler
 *
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodle1_qtype_multichoiceset_handler extends moodle1_qtype_handler {

    /**
     * Return the subpaths within a question
     *
     * @return array
     */
    public function get_question_subpaths() {
        return [
            'ANSWERS/ANSWER',
            'MULTICHOICESET',
        ];
    }

    /**
     * Appends the multichoiceset specific information to the question
     *
     * @param array $data the question data
     * @param array $raw unused
     */
    public function process_question(array $data, array $raw) {

        // Convert and write the answers first.
        if (isset($data['answers'])) {
            $this->write_answers($data['answers'], $this->pluginname);
        }

        // Convert and write the multichoiceset.
        if (!isset($data['multichoiceset'])) {
            // This should never happen, but it can do if the 1.9 site contained
            // corrupt data.
            $data['multichoiceset'] = [[
                'shuffleanswers'                 => 1,
                'correctfeedback'                => '',
                'correctfeedbackformat'          => FORMAT_HTML,
                'incorrectfeedback'              => '',
                'incorrectfeedbackformat'        => FORMAT_HTML,
                'answernumbering'                => 'abc',
                'shownumcorrect'                => 0,
                'showstandardinstruction'        => 0,
            ]];
        }
        $this->write_multichoiceset($data['multichoiceset'], $data['oldqtextformat']);
    }

    /**
     * Converts the multichoiceset info and writes it into question XML
     *
     * @param array $multichoicesets the grouped structure
     * @param int $oldqtextformat - (see moodle1_question_bank_handler::process_question())
     */
    protected function write_multichoiceset(array $multichoicesets, $oldqtextformat) {
        global $CFG;

        // The grouped array is supposed to have just one element - let us use foreach anyway
        // just to be sure we do not loose anything.
        foreach ($multichoicesets as $multichoiceset) {
            // Append an artificial 'id' attribute (is not included in moodle.xml).
            $multichoiceset['id'] = $this->converter->get_nextid();

            // Replay the upgrade step 2009021801.
            $multichoiceset['correctfeedbackformat']               = 0;
            $multichoiceset['incorrectfeedbackformat']             = 0;

            if ($CFG->texteditors !== 'textarea' && $oldqtextformat == FORMAT_MOODLE) {
                $multichoiceset['correctfeedback']         = text_to_html($multichoiceset['correctfeedback'], false, false, true);
                $multichoiceset['correctfeedbackformat']   = FORMAT_HTML;
                $multichoiceset['incorrectfeedback']       = text_to_html($multichoiceset['incorrectfeedback'], false, false, true);
                $multichoiceset['incorrectfeedbackformat'] = FORMAT_HTML;
            } else {
                $multichoiceset['correctfeedbackformat']   = $oldqtextformat;
                $multichoiceset['incorrectfeedbackformat'] = $oldqtextformat;
            }

            $this->write_xml('multichoiceset', $multichoiceset, ['/multichoiceset/id']);
        }
    }
}
