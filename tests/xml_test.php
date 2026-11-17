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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for Moodle XML import support.
 *
 * @package    qtype_multichoiceset
 * @copyright  2026 PD Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_multichoiceset;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * XML format subclass allowing answer file state to be prepared for testing.
 */
class testable_qformat_xml extends \qformat_xml {

    /**
     * Initialise the file arrays normally prepared by the full XML exporter.
     *
     * @param array $answers Question answers.
     */
    public function prepare_answer_files(array $answers): void {
        foreach ($answers as $answer) {
            $this->answerfiles[$answer->id] = [];
            $this->feedbackfiles[$answer->id] = [];
        }
    }
}

/**
 * Tests for Moodle XML import support.
 */
final class xml_test extends \advanced_testcase {

    /**
     * Load dependencies required by the XML import test.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        parent::setUpBeforeClass();

        require_once($CFG->dirroot . '/question/type/multichoiceset/questiontype.php');
    }

    /**
     * Exported XML should contain the multichoiceset-specific settings.
     */
    public function test_export_to_xml(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();

        $createdquestion = $generator->create_question(
                'multichoiceset',
                'two_of_four',
                ['category' => $category->id]
        );

        $question = \question_bank::load_question_data($createdquestion->id);

		$format = new testable_qformat_xml();
		$format->prepare_answer_files($question->options->answers);

		$qtype = new \qtype_multichoiceset();

        $xml = $qtype->export_to_xml($question, $format);

		$this->assertIsString($xml);
		$this->assertNotSame('', $xml);

		$this->assertStringContainsString(
				'<shuffleanswers>true</shuffleanswers>',
				$xml
		);

		$this->assertStringContainsString(
				'<correctfeedback format="html">',
				$xml
		);

		$this->assertStringContainsString(
				'<incorrectfeedback format="html">',
				$xml
		);

		$this->assertStringContainsString(
				'<shownumcorrect/>',
				$xml
		);

		$this->assertStringContainsString(
				'<answernumbering>123</answernumbering>',
				$xml
		);

		$this->assertStringContainsString(
				'<showstandardinstruction>0</showstandardinstruction>',
				$xml
		);

		$this->assertSame(
				2,
				substr_count($xml, '<answer fraction="100"')
		);

		$this->assertSame(
				2,
				substr_count($xml, '<answer fraction="0"')
		);
		$this->assertStringContainsString('<text>One</text>', $xml);
		$this->assertStringContainsString('<text>Two</text>', $xml);
		$this->assertStringContainsString('<text>Three</text>', $xml);
		$this->assertStringContainsString('<text>Four</text>', $xml);

		$this->assertStringContainsString('<text>Well done!</text>', $xml);
		$this->assertStringContainsString('<text>That is not right at all.</text>', $xml);

	}

    /**
     * Historical XML without showstandardinstruction should import correctly.
     */
    public function test_import_historical_fixture(): void {
        global $CFG;

        $fixturepath = $CFG->dirroot
                . '/question/type/multichoiceset/tests/fixtures/'
                . 'qtype_sample_multichoiceset.xml';

        $xml = file_get_contents($fixturepath);
        $this->assertNotFalse($xml);

		$parser = new \core\xml_parser();
		$parsedxml = $parser->parse($xml, 0);

		$this->assertArrayHasKey('quiz', $parsedxml);
        $this->assertArrayHasKey('question', $parsedxml['quiz']['#']);

        $questiondata = null;

        foreach ($parsedxml['quiz']['#']['question'] as $candidate) {
            if (($candidate['@']['type'] ?? '') === 'multichoiceset') {
                $questiondata = $candidate;
                break;
            }
        }

        $this->assertNotNull(
                $questiondata,
                'The XML fixture must contain a multichoiceset question.'
        );

        $format = new \qformat_xml();
        $qtype = new \qtype_multichoiceset();

        $question = $qtype->import_from_xml(
                $questiondata,
                new \stdClass(),
                $format
        );

        $this->assertNotFalse($question);
        $this->assertInstanceOf(\stdClass::class, $question);

        $this->assertSame('multichoiceset', $question->qtype);
        $this->assertSame('All-or-nothing-001', $question->name);
        $this->assertSame('abc', $question->answernumbering);
        $this->assertSame(1, $question->shuffleanswers);

        // The historical fixture has no showstandardinstruction element.
        $this->assertSame(0, $question->showstandardinstruction);

        $this->assertSame(
                'Your answer is correct.',
                $question->correctfeedback['text']
        );
        $this->assertSame(
                'Your answer is incorrect.',
                $question->incorrectfeedback['text']
        );

        $this->assertSame(
                [false, false, true, false, true],
                $question->correctanswer
        );

        $answertexts = array_map(
                static fn(array $answer): string => $answer['text'],
                $question->answer
        );

        $this->assertSame(
                ['Tokyo', 'Spain', 'London', 'Barcelona', 'Paris'],
                $answertexts
        );

        $this->assertCount(2, $question->hint);
        $this->assertSame('First hint', $question->hint[0]['text']);
        $this->assertSame('Second hint', $question->hint[1]['text']);
    }
}
