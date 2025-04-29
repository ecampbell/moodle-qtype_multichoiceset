<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * qtype_multichoiceset capability definition
 *
 * @package    qtype_multichoiceset
 * @copyright  2018 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$addons = [
    "qtype_multichoiceset" => [
        'handlers' => [ // Different places where the plugin will display content.
            'multichoiceset' => [ // Handler unique name (alphanumeric).
                'displaydata' => [
                    'title' => 'All-or-Nothing Multiple Choice question',
                    'icon' => $CFG->wwwroot . '/question/type/multichoiceset/pix/icon.gif',
                    'class' => '',
                ],
                 'delegate' => 'CoreQuestionDelegate', // Delegate (where to display the link to the plugin).
                'method' => 'mobile_get_multichoiceset', // Main function in \qtype_multichoiceset\output.
                'offlinefunctions' => [
                    'mobile_get_multichoiceset' => [], // Function that needs to be downloaded for offline use.
                ],
            ],
        ],
        'lang' => [    // Language strings that are used in all the handlers.
                ['pluginname', 'qtype_multichoiceset'],
        ],
    ],
];
