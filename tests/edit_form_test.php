<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace qtype_multichoiceset;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the multichoiceset editing form.
 *
 * @package qtype_multichoiceset
 */
final class edit_form_test extends \advanced_testcase {

    /**
     * HTML-only blank answer content should be considered empty.
     */
    public function test_html_blank_answer_is_empty(): void {
        $this->assertTrue(html_is_blank('<p><br></p>'));
        $this->assertTrue(html_is_blank(''));
        $this->assertFalse(html_is_blank('<p>Answer</p>'));
    }
}