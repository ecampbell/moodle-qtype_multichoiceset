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
 * Version information for the multiple choice All-or-Nothing question type.
 *
 * @package    qtype_multichoiceset
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_multichoiceset';
$plugin->version   = 2025042900;

$plugin->requires  = 2020110900; // Requires Moodle 3.10.

$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.10.2 (Build: 2025042900)';

$plugin->dependencies = [
    'qtype_multichoice' => ANY_VERSION,
];
