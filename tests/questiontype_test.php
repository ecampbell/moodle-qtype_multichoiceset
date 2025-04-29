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
 * Unit tests for the mulitple choice question definition class.
 *
 * @package    qtype_multichoiceset
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_multichoiceset;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/multichoiceset/questiontype.php');


/**
 * Unit tests for the multiple choice all or nothing question definition class.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class questiontype_test extends advanced_testcase {
    /** @var $qtype the question type. */
    protected $qtype;

    /**
     * Set up test question.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_multichoiceset();
    }

    /**
     * Shut down test question.
     *
     * @return void
     */
    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }

    /**
     * Get test name.
     *
     * @return void
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'multichoiceset');
    }

    /**
     * Get test question data.
     *
     * @return stdObject
     */
    protected function get_test_question_data() {
        $q = new stdClass();
        $q->id = 1;
        $q->options = new stdClass();
        $q->options->answers[1] = (object) ['answer' => 'frog',
                'answerformat' => FORMAT_HTML, 'fraction' => 1, ];
        $q->options->answers[2] = (object) ['answer' => 'toad',
                'answerformat' => FORMAT_HTML, 'fraction' => 0, ];

        return $q;
    }

    /**
     * Check that we can analyse responses.
     *
     * @return void
     */
    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Get random score.
     *
     * @return void
     */
    public function test_get_random_guess_score(): void {
        $q = $this->get_test_question_data();
        $this->assertNull($this->qtype->get_random_guess_score($q));
    }

    /**
     * Get possible responses.
     *
     * @return void
     */
    public function test_get_possible_responses(): void {
        $q = $this->get_test_question_data();

        $this->assertEquals([
            1 => [1 => new question_possible_response('frog', 1)],
            2 => [2 => new question_possible_response('toad', 0)],
        ], $this->qtype->get_possible_responses($q));
    }
}
