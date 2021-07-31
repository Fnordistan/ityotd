<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Implementation of Great Wall and Super Events expansions: @David Edelstein <davidedelstein@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * InTheYearOfTheDragonExp game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in intheyearofthedragonexp.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
        'name' => totranslate('Large privilege cost'),
        'values' => array(
            1 => array( 'name' => totranslate('New edition: 7')),
            2 => array( 'name' => totranslate('Old edition: 6')),
        ),
        'default' => 1
    ),

    101 => array(
                'name' => totranslate('Great Wall expansion'),
                'values' => array(
                    1 => array( 'name' => totranslate('None'), 'description' => totranslate('Do not use Great Wall')),
                    2 => array( 'name' => totranslate('Great Wall'), 'description' => totranslate('Choose reward tile when Great Wall built'), 'alpha' => true, 'nobeginner' => true ),
                    3 => array( 'name' => totranslate('Great Wall (HARD MODE!)'), 'description' => totranslate('Random reward tiles'), 'alpha' => true, 'nobeginner' => true ),
                ),
                'default' => 1
            ),

    102 => array(
        'name' => totranslate('Super Events expansion'),
        'values' => array(
            1 => array( 'name' => totranslate('None'), 'description' => totranslate('Do not use super events')),
            2 => array( 'name' => totranslate('Random'), 'description' => totranslate('Random super event'), 'alpha' => true, 'nobeginner' => true ),
            3 => array( 'name' => totranslate('Lanternfest'), 'description' => totranslate('Lanternfest super event'), 'alpha' => true, 'nobeginner' => true ),
            4 => array( 'name' => totranslate('Buddha'), 'description' => totranslate('Buddha super event'), 'alpha' => true, 'nobeginner' => true ),
            5 => array( 'name' => totranslate('Earthquake'), 'description' => totranslate('Earthquake super event'), 'alpha' => true, 'nobeginner' => true ),
            6 => array( 'name' => totranslate('Flood'), 'description' => totranslate('Flood super event'), 'alpha' => true, 'nobeginner' => true ),
            7 => array( 'name' => totranslate('Solar Eclipse'), 'description' => totranslate('Solar Eclipse super event'), 'alpha' => true, 'nobeginner' => true ),
            8 => array( 'name' => totranslate('Volcanic Eruption'), 'description' => totranslate('Volcanic Eruption super event'), 'alpha' => true, 'nobeginner' => true ),
            9 => array( 'name' => totranslate('Tornado'), 'description' => totranslate('Tornado super event'), 'alpha' => true, 'nobeginner' => true ),
            10 => array( 'name' => totranslate('Sunrise'), 'description' => totranslate('Sunrise super event'), 'alpha' => true, 'nobeginner' => true ),
            11 => array( 'name' => totranslate('Assassination Attempt'), 'description' => totranslate('Assassination Attempt super event'), 'alpha' => true, 'nobeginner' => true ),
            12 => array( 'name' => totranslate('Charter'), 'description' => totranslate('Charter super event'), 'alpha' => true, 'nobeginner' => true ),
            13 => array( 'name' => totranslate('HARD MODE!'), 'description' => totranslate('Random hidden super event, not revealed until turn 7'), 'alpha' => true, 'nobeginner' => true ),
        ),
        'default' => 1
    ),

    103 => array(
        'name' => totranslate('Open hand display'),
        'values' => array(
            1 => array( 'name' => totranslate('No open hands'), 'description' => totranslate('Do not show cards in hand')),
            2 => array( 'name' => totranslate('Open hands'), 'description' => totranslate('Show cards in hand for all players')),
        ),
        'default' => 1
    )

);

$game_preferences = array(
    100 => array(
        'name' => totranslate('Confirmation dialogs'),
        'values' => array(
            1 => array( 'name' => totranslate('None'), 'description' => totranslate('Never ask for confirmation')),
            2 => array( 'name' => totranslate('Recruiting'), 'description' => totranslate('Ask for confirmation after selecting a Person for recruitment')),
            3 => array( 'name' => totranslate('Action'), 'description' => totranslate('Ask for confirmation after selecting an Action')),
            4 => array( 'name' => totranslate('Select Person'), 'description' => totranslate('Ask for confirmation after selecting a Person in a palace')),
            5 => array( 'name' => totranslate('All'), 'description' => totranslate('Ask for confirmation after Recruiting, Actions, and Select Person')),
        ),
        'needReload' => true,
        'default' => 1
    )
);