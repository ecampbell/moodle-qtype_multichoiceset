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
 * Mobile output class for question type multichoiceset.
 *
 * @package qtype_multichoiceset
 * @copyright 2018 Zvonko Martinovic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_multichoiceset\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Mobile output class for question type multichoiceset.
 *
 * @package qtype_multichoiceset
 * @copyright 2018 Zvonko Martinovic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the All-or-Nothing question type for the quiz in the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return void
     */
    public static function mobile_get_multichoiceset($args) {
        global $CFG;

        $args = (object) $args;
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';

        // General notes:
        // If you have worked on mobile activities, there is no cmid or courseid in $args here.
        // This is not equivalent to mod/quiz/attempt.php?attempt=57&cmid=147, rather
        // this is just a section of that page, with all the access checking already done for us.
        // The full file path is required even though file_get_contents should work with relative paths.
        return [
            'templates' => [[
                'id' => 'main',
                'html' => file_get_contents($CFG->dirroot . "/question/type/multichoiceset/mobile/multichoiceset_$versionname.html")
            ]],
            'javascript' => file_get_contents($CFG->dirroot . '/question/type/multichoiceset/mobile/multichoiceset.js')
        ];
    }
}
