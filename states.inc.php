<?php
/*
  * states.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * In the Year of the Dragon game states
  *
*/

/*
*
*   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
*   in a very easy way from this configuration file.
*
*
*   States types:
*   _ manager: game manager can make the game progress to the next state.
*   _ game: this is an (unstable) game state. the game is going to progress to the next state as soon as current action has been accomplished
*   _ activeplayer: an action is expected from the activeplayer
*
*   Arguments:
*   _ possibleactions: array that specify possible player actions on this step (for state types "manager" and "activeplayer")
*       (correspond to actions names)
*   _ action: name of the method to call to process the action (for state type "game")
*   _ transitions: name of transitions and corresponding next state
*       (name of transitions correspond to "nextState" argument)
*   _ description: description is displayed on top of the main content.
*   _ descriptionmyturn (optional): alternative description displayed when it's player's turn
*
*/

if (!defined('STATE_SETUP')) { // ensure this block is only invoked once, since it is included multiple times
    define("STATE_SETUP", 1);
    define("STATE_INITIAL_CHOICE_NP", 5);
    define("STATE_INITIAL_CHOICE", 6);
    define("STATE_INITIAL_PLACE", 7);
    define("STATE_START_GAME", 10);
    define("STATE_ACTION_NP", 11);
    define("STATE_ACTION_CHOOSE", 12);
    define("STATE_BUILD", 13);
    define("STATE_PRIVILEGE", 14);
    define("STATE_NEXT_PHASE", 20);
    define("STATE_RECRUIT_PERSON", 21);
    define("STATE_PLACE_PERSON", 22);
    define("STATE_REPLACE_PERSON", 23);
    define("STATE_EVENT", 30);
    define("STATE_EVENT_CONSEQUENCE", 31);
    define("STATE_RELEASE_PERSON", 32);
    define("STATE_NEXT_PLAYER", 33);
    define("STATE_GREAT_WALL", 34);
    define("STATE_GREAT_WALL_NP", 35);
    define("STATE_GREAT_WALL_RELEASE", 36);
    define("STATE_SUPER_EVENT", 37);
    define("SUPER_EVENT_INIT", 38);
    define("STATE_BUILD_WALL", 39);
    define("STATE_END_PHASE", 40);
    define("STATE_EARTHQUAKE", 41);
    define("STATE_REDUCE_PALACE", 42);
    define("STATE_REDUCE_POPULATION", 43);
    define("STATE_FLOOD", 50);
    define("STATE_REDUCE_RESOURCES", 51);
    define("STATE_TORNADO", 60);
    define("STATE_DISCARD", 61);
    define("STATE_SUNRISE", 70);
    define("STATE_PLACE_YOUNG", 71);
    define("STATE_CHARTER", 80);
    define("STATE_CHARTER_PERSON", 81);
    define("STATE_FINAL_SCORING", 98);
    define("STATE_ENDGAME", 99);
};

$machinestates = array(

    STATE_SETUP => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 5 )
    ),

    /// Initial choice of subjects /////////////////////::
    STATE_INITIAL_CHOICE_NP => array(
        "name" => "initialChoiceNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stInitialChoiceNextPlayer",
        "transitions" => array( "startGame" => STATE_START_GAME, "nextPlayer" => STATE_INITIAL_CHOICE )
    ),
    STATE_INITIAL_CHOICE => array(
        "name" => "initialChoice",
        "description" => clienttranslate('${actplayer} must summon first 2 subjects to court'),
        "descriptionmyturn" => clienttranslate('${you} must summon your first 2 subjects to court'),
        "possibleactions" => array( "recruit" ),
        "type" => "activeplayer",
        "transitions" => array( "chooseTile" => STATE_INITIAL_PLACE, "zombiePass" => STATE_INITIAL_CHOICE_NP )
    ),
    STATE_INITIAL_PLACE => array(
        "name" => "initialPlace",
        "description" => clienttranslate('${actplayer} must place new person tile in a palace'),
        "descriptionmyturn" => clienttranslate('${you} must place new person tile in a palace'),
        "possibleactions" => array( "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "nextPhase" => STATE_INITIAL_CHOICE_NP )
    ),
    
    /// 1st game phase: ACTIONS ///////////////////
    
    STATE_START_GAME => array(
        "name" => "actionPhaseInit",
        "description" => '',
        "type" => "game",
        "updateGameProgression" => true,   
        "action" => "stActionPhaseInit",
        "transitions" => array( "" => STATE_ACTION_CHOOSE )
    ),
    STATE_ACTION_NP => array(
        "name" => "actionPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stActionPhaseNextPlayer",
        "transitions" => array( "nextPlayer" => STATE_ACTION_CHOOSE, "endPhase" => STATE_RECRUIT_PERSON, "noRecruit" => STATE_EVENT )
    ),

    STATE_ACTION_CHOOSE => array(
        "name" => "actionPhaseChoose",
        "description" => clienttranslate('${actplayer} must choose an action to carry out'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action to carry out'),
        "possibleactions" => array( "action", "refillyuan" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_ACTION_NP, "buildAction" => STATE_BUILD, "privilegeAction" => STATE_PRIVILEGE, "buildWallAction" => STATE_BUILD_WALL )
    ),

    STATE_BUILD => array(
        "name" => "actionPhaseBuild",
        "description" => clienttranslate('${actplayer} must extend an existing palace or build a new one (x${toBuild})'),
        "descriptionmyturn" => clienttranslate('${you} must extend an existing palace or build a new one (x${toBuild})'),
        "possibleactions" => array( "build" ),
        "args" => "argActionPhaseBuild",
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_ACTION_NP, "buildAgain" => STATE_BUILD, "charter" => STATE_CHARTER )
    ),    
    STATE_PRIVILEGE => array(
        "name" => "actionPhasePrivilege",
        "description" => clienttranslate('${actplayer} must buy a small privilege (2 yuans) or a large privilege (${largePrivilegeCost} yuans)'),
        "descriptionmyturn" => clienttranslate('${you} must buy a small privilege (2 yuans) or a large privilege (${largePrivilegeCost} yuans)'),
        "args" => "argActionPhasePrivilege",
        "possibleactions" => array( "choosePrivilege" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_ACTION_NP )
    ),  
    
    /// 2nd phase: PERSONS //////
    STATE_NEXT_PHASE => array(
        "name" => "personPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stPersonPhaseNextPlayer",
        "transitions" => array( "nextPlayer" => STATE_RECRUIT_PERSON, "endPhase" => STATE_EVENT )
    ),      
    STATE_RECRUIT_PERSON => array(
        "name" => "personPhaseChoosePerson",
        "description" => clienttranslate('${actplayer} must choose a person tile to recruit'),
        "descriptionmyturn" => clienttranslate('${you} must choose a person tile to recruit'),
        "possibleactions" => array( "recruit" ),
        "action" => "stPersonPhaseChoosePerson",
        "type" => "activeplayer",
        "transitions" => array( "chooseTile" => STATE_PLACE_PERSON, "palaceFull" => STATE_REPLACE_PERSON, "zombiePass" => STATE_NEXT_PHASE, "notPossible" => STATE_NEXT_PHASE )
    ),   
    STATE_PLACE_PERSON => array(
        "name" => "personPhasePlace",
        "description" => clienttranslate('${actplayer} must place a new person tile in a palace'),
        "descriptionmyturn" => clienttranslate('${you} must place your new person tile in a palace'),
        "possibleactions" => array( "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "nextPhase" => STATE_NEXT_PHASE, "sunrise" => STATE_SUNRISE )
    ),      
    STATE_REPLACE_PERSON => array(
        "name" => "palaceFull",
        "description" => clienttranslate('${actplayer} must choose which person to replace with the new one'),
        "descriptionmyturn" => clienttranslate('${you} must choose which person to replace with the new one'),
        "possibleactions" => array( "releaseReplace", "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "nextPhase" => STATE_NEXT_PHASE, "sunrise" => STATE_SUNRISE )
    ), 

    /// 3rd phase: EVENTS //////
    STATE_EVENT => array(
        "name" => "eventPhase",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhase",
        "transitions" => array( "releaseRound" => STATE_EVENT_CONSEQUENCE )
    ),      
    STATE_EVENT_CONSEQUENCE => array(
        "name" => "eventPhaseApplyConsequences",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhaseApplyConsequences",
        "transitions" => array( "releasePerson" => STATE_RELEASE_PERSON, "noRelease" => STATE_NEXT_PLAYER )
    ),           
    STATE_RELEASE_PERSON => array(
        "name" => "release",
        "description" => clienttranslate('${actplayer} must choose ${nbr} person(s) to release'),
        "descriptionmyturn" => clienttranslate('${you} must choose ${nbr} person(s) to release'),
        "possibleactions" => array( "release" ),
        "action" => "stRelease",
        "args" => "argNbrToRelease",
        "type" => "activeplayer",
        "transitions" => array( "continueRelease" => STATE_RELEASE_PERSON, "endRelease" => STATE_NEXT_PLAYER )
    ),
    STATE_NEXT_PLAYER => array(
        "name" => "eventPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhaseNextPlayer",
        "transitions" => array( "endPhase" => STATE_SUPER_EVENT, "nextPlayer" => STATE_EVENT_CONSEQUENCE, "greatWall" => STATE_GREAT_WALL )
    ),

    /// GREAT WALL expansion //////

    STATE_GREAT_WALL => array(
        "name" => "greatWallEvent",
        "description" => "",
        "type" => "game",
        "action" => "stGreatWall",
        "transitions" => array( "losePerson" => STATE_GREAT_WALL_NP, "endPhase" => STATE_SUPER_EVENT )
    ),

    STATE_GREAT_WALL_NP => array(
        "name" => "greatWallNextPlayer",
        "description" => "",
        "action" => "stGreatWallNext",
        "type" => "game",
        "transitions" => array( "nextPlayer" => STATE_GREAT_WALL_NP, "releasePerson" => STATE_GREAT_WALL_RELEASE, "endPhase" => STATE_SUPER_EVENT )
    ),

    STATE_GREAT_WALL_RELEASE => array(
        "name" => "greatWallRelease",
        "description" => clienttranslate('${actplayer} must choose 1 person to release'),
        "descriptionmyturn" => clienttranslate('${you} must choose 1 person to release'),
        "possibleactions" => array( "release" ),
        "action" => "stGreatWallRelease",
        "args" => "argNbrToRelease",
        "type" => "activeplayer",
        "transitions" => array( "endRelease" => STATE_GREAT_WALL_NP )
    ),

    STATE_BUILD_WALL => array(
        "name" => "actionBuildWall",
        "description" => clienttranslate('${actplayer} must choose a Great Wall tile to build'),
        "descriptionmyturn" => clienttranslate('${you} must choose a Great Wall tile to build'),
        "possibleactions" => array( "buildWall" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_ACTION_NP, "buildAction" => STATE_BUILD )
    ),    

    /// SUPER EVENTS //////

    STATE_SUPER_EVENT => array(
        "name" => "superEvent",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEvent",
        "transitions" => array( "endPhase" => STATE_END_PHASE, "earthquake" => SUPER_EVENT_INIT, "flood" => SUPER_EVENT_INIT, "solar" => STATE_EVENT, "tornado" => SUPER_EVENT_INIT, "sunrise" => SUPER_EVENT_INIT, "charter" => SUPER_EVENT_INIT )
    ),

    STATE_END_PHASE => array(
        "name" => "decayAndScoring",
        "description" => '',
        "type" => "game",
        "action" => "stDecayAndScoring",
        "transitions" => array( "nextTurn" => STATE_START_GAME, "finalScoring" => STATE_FINAL_SCORING )
    ),

    SUPER_EVENT_INIT => array(
        "name" => "superEventInit",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventInit",
        "transitions" => array( "earthquake" => STATE_EARTHQUAKE, "flood" => STATE_FLOOD, "tornado" => STATE_TORNADO, "sunrise" => STATE_SUNRISE, "charter" => STATE_CHARTER )
    ),

    // from Earthquake
    STATE_EARTHQUAKE => array(
        "name" => "earthquake",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventRotate",
        "transitions" => array( "nextPlayer" => STATE_REDUCE_PALACE, "endPhase" => STATE_END_PHASE )
    ),

    STATE_REDUCE_PALACE => array(
        "name" => "reducePalace",
        "description" => clienttranslate('${actplayer} must remove ${nbr} palace section(s)'),
        "descriptionmyturn" => clienttranslate('${you} must remove ${nbr} palace section(s)'),
        "possibleactions" => array( "reduce" ),
        "args" => "argNbrToReduce",
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_EARTHQUAKE, "nextReduce" => STATE_REDUCE_PALACE, "releasePerson" => STATE_REDUCE_POPULATION, "zombiePass" => STATE_EARTHQUAKE )
    ),

    STATE_REDUCE_POPULATION => array(
        "name" => "reducePopulation",
        "description" => clienttranslate('${actplayer} must choose ${nbr} person(s) to release'),
        "descriptionmyturn" => clienttranslate('${you} must choose ${nbr} person(s) to release'),
        "possibleactions" => array( "depopulate" ),
        "args" => "argNbrToRelease",
        "type" => "activeplayer",
        "transitions" => array( "continueRelease" => STATE_REDUCE_POPULATION, "endRelease" => STATE_EARTHQUAKE, "zombiePass" => STATE_EARTHQUAKE )
    ),

    // from Flood
    STATE_FLOOD => array(
        "name" => "flood",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventRotate",
        "transitions" => array( "nextPlayer" => STATE_REDUCE_RESOURCES, "skipPlayer" => STATE_FLOOD, "endPhase" => STATE_END_PHASE )
    ),

    STATE_REDUCE_RESOURCES => array(
        "name" => "reduceResources",
        "description" => clienttranslate('${actplayer} must choose ${nbr} resource(s) to lose'),
        "descriptionmyturn" => clienttranslate('${you} must choose ${nbr} resource(s) to lose'),
        "possibleactions" => array( "removeResources" ),
        "args" => "argNbrToReduce",
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_FLOOD, "zombiePass" => STATE_FLOOD )
    ),

    STATE_TORNADO => array(
        "name" => "tornado",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventRotate",
        "transitions" => array( "nextPlayer" => STATE_DISCARD, "endPhase" => STATE_END_PHASE )
    ),

    STATE_DISCARD => array(
        "name" => "discardPersonCards",
        "description" => clienttranslate('${actplayer} must discard ${nbr} person cards'),
        "descriptionmyturn" => clienttranslate('${you} must discard ${nbr} person cards'),
        "possibleactions" => array( "discard" ),
        "args" => "argNbrToReduce",
        "type" => "activeplayer",
        "transitions" => array( "continueDiscard" => STATE_DISCARD, "endDiscard" => STATE_TORNADO, "zombiePass" => STATE_TORNADO )
    ),

    STATE_SUNRISE => array(
        "name" => "sunrise",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventRotate",
        "transitions" => array( "nextPlayer" => STATE_PLACE_YOUNG, "endPhase" => STATE_END_PHASE )
    ),

    // from Sunrise
    STATE_PLACE_YOUNG => array(
        "name" => "sunriseRecruit",
        "description" => clienttranslate('${actplayer} must choose a young person to add'),
        "descriptionmyturn" => clienttranslate('${you} must choose a young person to add'),
        "possibleactions" => array( "recruit" ),
        "type" => "activeplayer",
        "transitions" => array( "chooseTile" => STATE_PLACE_PERSON, "palaceFull" => STATE_REPLACE_PERSON, "zombiePass" => STATE_SUNRISE )
    ),

    // from Charter
    STATE_CHARTER => array(
        "name" => "charter",
        "description" => '',
        "type" => "game",
        "action" => "stSuperEventRotate",
        "transitions" => array( "nextPlayer" => STATE_CHARTER_PERSON, "endPhase" => STATE_END_PHASE )
    ),

    STATE_CHARTER_PERSON => array(
        "name" => "charterPerson",
        "description" => clienttranslate('${actplayer} must choose a person type in their realm to gain benefits'),
        "descriptionmyturn" => clienttranslate('${you} must choose a person type in your realm to gain benefits'),
        "possibleactions" => array( "charter" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => STATE_CHARTER, "buildAction" => STATE_BUILD, "zombiePass" => STATE_CHARTER )
    ),

    /// Final scoring //////
    STATE_FINAL_SCORING => array(
        "name" => "finalScoring",
        "description" => '',
        "type" => "game",
        "action" => "stFinalScoring",
        "transitions" => array( "" => STATE_ENDGAME )
    ),    
   
    STATE_ENDGAME => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);