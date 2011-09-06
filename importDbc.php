#!/usr/bin/env php
<?php
/**
 *  mangos-zero database content tracker
 *
 * @package     mangos-zero-tracker
 * @author      Shlainn Blaze<shlainnblaze@googlemail.com>
 * @author      TheLuda <theluda@getmangos.com>
 * @copyright   Copyright (c) 2011 mangos foundation (http://getmangos.com/)
 * @license     http://www.gnu.org/licenses/gpl.html GPL v3
 */
/**
 * The build number of the World of Warcraft client needed
 *
 * @var     string
 */
$clientBuild = '5875';

/**
 * The version of the World of Warcraft client needed
 *
 * @var     string
 */
$clientVersion = '1.12.1';

/**
 * The directory where the DBC cache files are available
 *
 * @var     string
 */
$dbcStore = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dbc' . DIRECTORY_SEPARATOR;

/**
 * The file suffix for DBC cache files
 * @var     string
 */
$dbcSuffix = '.dbc';

/**
 * List of known DBC cache files (without file suffix)
 *
 * @var     array
 */
$dbcFiles = array(
    'AreaTable',
    'ChrClasses',
    'ChrRaces',
    'Emotes',
    'Faction',
    'Map',
    'QuestInfo',
    'QuestSort',
    'SkillLine',
    'Spell',
);

/**
 * A list of string containing unpack information passed to PHPs unpack to extract data from binary strings
 *
 * @var     array
 * @link    http://php.net/unpack
 */
$dbcUnpackStrings = array(
    'AreaTable' => array(
        'string' => 'L1id/Lfield1/Lfield2/L8unk/Lstring1',
        'fields' => 2,
        'strings' => 1,
    ),
    'ChrClasses' => array(
        'string' => 'L1id/L4unk/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'ChrRaces' => array(
        'string' => 'L1id/L14unk/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'Faction' => array(
        'string' => 'L1id/L18unk/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'Map' => array(
        'string' => 'L1id/Lunk/Lfield1/L1unk/Lstring1/L12unk/f2unk/Lfield2',
        'fields' => 2,
        'strings' => 1,
    ),
    'QuestInfo' => array(
        'string' => 'L1id/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'QuestSort' => array(
        'string' => 'L1id/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'SkillLine' => array(
        'string' => 'L1id/L2unk/L1string1',
        'fields' => 0,
        'strings' => 1,
    ),
    'Spell' => array(
        'string' => 'L1id/L119unk/Lstring1',
        'fields' => 0,
        'strings' => 1,
    ),
);

/**
 * Maps DBC cache fields to database table fields
 *
 * @var     array
 */
$dbcMapDatabase = array(
    'AreaTable' => array(
        'field1' => 'map',
        'field2' => 'parent',
        'string1' => 'name',
    ),
    'ChrClasses' => array(
        'string1' => 'name',
    ),
    'ChrRaces' => array(
        'string1' => 'name',
    ),
    'Faction' => array(
        'string1' => 'name',
    ),
    'Map' => array(
        'field1' => 'type',
        'field2' => 'parent',
        'string1' => 'name',
    ),
    'QuestInfo' => array(
        'string1' => 'name',
    ),
    'QuestSort' => array(
        'string1' => 'name',
    ),
    'SkillLine' => array(
        'string1' => 'name',
    ),
    'Spell' => array(
        'string1' => 'name',
    ),
);

/**
 * Require PEAR/Console_CommandLine package
 */
require_once 'Console/CommandLine.php';

/**
 * Instance of PEAR/Console_CommandLine
 *
 * @var     Console_CommandLine
 */
$parser = new Console_CommandLine();

$parser->description = 'A program to extract required base data from World of Warcraft 1.12.1 DBC files.';
$parser->version = '1.0.0';

/**
 * Add an option to suppress message output
 */
$parser->addOption(
    'quiet', array(
    'short_name' => '-q',
    'long_name' => '--quiet',
    'description' => "don't print status messages to stdout",
    'action' => 'StoreTrue',
    'default' => 'false',
    )
);

/**
 * Add an option to configure the mangos-zero world database
 */
$parser->addOption(
    'db_world', array(
    'short_name' => '-w',
    'long_name' => '--db-world',
    'description' => 'The mangos-zero world database',
    'action' => 'StoreString',
    'default' => 'zp_world',
    )
);

/**
 * Add an option to configure the quest-tracker database
 */
$parser->addOption(
    'db_tracker', array(
    'short_name' => '-t',
    'long_name' => '--db-tracker',
    'description' => 'The quest tracker database',
    'action' => 'StoreString',
    'default' => 'zp_tracker',
    )
);

/**
 * Add an option to configure the MySQL database hostname
 */
$parser->addOption(
    'db_hostname', array(
    'short_name' => '-s',
    'long_name' => '--db-hostname',
    'description' => 'The database hostname',
    'action' => 'StoreString',
    'default' => 'localhost',
    )
);

/**
 * Add an option to configure the MySQL database user
 */
$parser->addOption(
    'db_username', array(
    'short_name' => '-u',
    'long_name' => '--db-user',
    'description' => 'The database user',
    'action' => 'StoreString',
    'default' => 'zp',
    )
);

/**
 * Add an option to configure the MySQL database password
 */
$parser->addOption(
    'db_password', array(
    'short_name' => '-p',
    'long_name' => '--db-password',
    'description' => 'The database password',
    'action' => 'Password',
    'default' => 'zpdb',
    )
);

/**
 * Add an option to select the directory where DBC cache files can be found
 */
$parser->addOption(
    'dbc_storage', array(
    'short_name' => '-d',
    'long_name' => '--dbc-storage',
    'description' => 'The directory where DBC cache files are stored',
    'action' => 'StoreString',
    'default' => 'dbc',
    )
);

/**
 * Add an option to display the known DBC cache files
 */
$parser->addOption(
    'list_dbc_files', array(
    'short_name' => '-l',
    'long_name' => '--list-dbc-files',
    'description' => 'List known DBC files',
    'action' => 'List',
    'action_params' => array(
        'list' => $dbcFiles,
        'delimiter' => ', ',
    )
    )
);

/**
 * Lookup
 * @param   string      $dbcStore           Path of the directory containg the DBC files
 * @param   string      $dbcFiles           List of known DBC files
 * @return  array       $missingDbcFiles    List of missing DBC files. False in case of errors, sizeof 0 if all is fine.
 */
function lookupDbcFiles($dbcStore, $dbcFiles)
{
    global $dbcSuffix;

    $missingDbcFiles = array();

    try {
        if (is_dir($dbcStore)) {
            if ($dh = opendir($dbcStore)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != ".." && in_array(substr($file, 0, -4), $dbcFiles)) {
                        array_push($missingDbcFiles, substr($file, 0, -4));
                    }
                }
                closedir($dh);
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }

    $missingDbcFiles = array_diff($dbcFiles, $missingDbcFiles);

    return $missingDbcFiles;
}

/**
 * ... and finally do our work and parse the DBC files and store the contents in our database
 */
try {
    $result = $parser->parse();

    print_r(lookupDbcFiles($dbcStore, $dbcFiles));
} catch (Exception $exc) {
    $parser->displayError($exc->getMessage());
}
