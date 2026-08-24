<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_stage.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Executes the upgrade steps for mod_stage.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_stage_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026082303) {
        $table = new xmldb_table('stage_question');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('stageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('themeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('evaltype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('qtype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null);
        $table->add_field('options', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('stageid', XMLDB_KEY_FOREIGN, ['stageid'], 'stage', ['id']);
        $table->add_key('themeid', XMLDB_KEY_FOREIGN, ['themeid'], 'stage_theme', ['id']);
        $table->add_index('themeid-evaltype', XMLDB_INDEX_NOTUNIQUE, ['themeid', 'evaltype']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('stage_answer');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('answertext', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('entryid', XMLDB_KEY_FOREIGN, ['entryid'], 'stage_entry', ['id']);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'stage_question', ['id']);
        $table->add_index('entryid-questionid', XMLDB_INDEX_UNIQUE, ['entryid', 'questionid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026082303, 'stage');
    }

    if ($oldversion < 2026082311) {
        $table = new xmldb_table('stage_question_theme');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('themeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'stage_question', ['id']);
        $table->add_key('themeid', XMLDB_KEY_FOREIGN, ['themeid'], 'stage_theme', ['id']);
        $table->add_index('questionid-themeid', XMLDB_INDEX_UNIQUE, ['questionid', 'themeid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Fait migrer l'affectation thématique existante (une seule) de chaque question vers la
        // table d'association, qui permet désormais d'en réutiliser une pour plusieurs thématiques.
        $existing = $DB->get_records('stage_question', null, '', 'id, themeid, timecreated');
        foreach ($existing as $question) {
            if (!$DB->record_exists('stage_question_theme',
                    ['questionid' => $question->id, 'themeid' => $question->themeid])) {
                $DB->insert_record('stage_question_theme', (object) [
                    'questionid' => $question->id,
                    'themeid' => $question->themeid,
                    'timecreated' => $question->timecreated,
                ]);
            }
        }

        upgrade_mod_savepoint(true, 2026082311, 'stage');
    }

    if ($oldversion < 2026082401) {
        $table = new xmldb_table('stage_theme');
        $field = new xmldb_field('studyyear', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'requiredduration');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026082401, 'stage');
    }

    return true;
}
