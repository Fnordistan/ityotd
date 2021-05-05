<?php
 /**
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

$machinestates = array(

    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 5 )
    ),
    
    
    /// Initial choice of subjects /////////////////////::
    5 => array(
        "name" => "initialChoiceNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stInitialChoiceNextPlayer",
        "transitions" => array( "startGame" => 10, "nextPlayer" => 6 )
    ),
    6 => array(
        "name" => "initialChoice",
        "description" => clienttranslate('${actplayer} must summon his first 2 subjects to court'),
        "descriptionmyturn" => clienttranslate('${you} must summon your first 2 subjects to court'),
        "possibleactions" => array( "recruit" ),
        "type" => "activeplayer",
        "transitions" => array( "chooseTile" => 7, "zombiePass" => 5 )
    ),
    7 => array(
        "name" => "initialPlace",
        "description" => clienttranslate('${actplayer} must place his new person tile in one of his palaces'),
        "descriptionmyturn" => clienttranslate('${you} must place your new person tile in one of your palaces'),
        "possibleactions" => array( "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "" => 5 )
    ),
    
    /// 1st game phase: ACTIONS ///////////////////
    
    10 => array(
        "name" => "actionPhaseInit",
        "description" => '',
        "type" => "game",
        "updateGameProgression" => true,   
        "action" => "stActionPhaseInit",
        "transitions" => array( "" => 12 )
    ),
    11 => array(
        "name" => "actionPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stActionPhaseNextPlayer",
        "transitions" => array( "nextPlayer" => 12, "endPhase" => 21 )
    ),    
    12 => array(
        "name" => "actionPhaseChoose",
        "description" => clienttranslate('${actplayer} must choose an action to carry out'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action to carry out'),
        "possibleactions" => array( "action", "refillyuan" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => 11, "buildAction" => 13, "privilegeAction" => 14 )
    ),
    13 => array(
        "name" => "actionPhaseBuild",
        "description" => clienttranslate('${actplayer} must choose to extend an existing palace or to build a new one (x${toBuild})'),
        "descriptionmyturn" => clienttranslate('${you} must choose to extend an existing palace or to build a new one (x${toBuild})'),
        "possibleactions" => array( "build" ),
        "args" => "argActionPhaseBuild",
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => 11, "buildAgain" => 13 )
    ),    
    14 => array(
        "name" => "actionPhasePrivilege",
        "description" => clienttranslate('${actplayer} must choose to buy a small privilege (2 yuans) or a large privilege (${largePrivilegeCost} yuans)'),
        "descriptionmyturn" => clienttranslate('${you} must choose to buy a small privilege (2 yuans) or a large privilege (${largePrivilegeCost} yuans)'),
        "args" => "argActionPhasePrivilege",
        "possibleactions" => array( "choosePrivilege" ),
        "type" => "activeplayer",
        "transitions" => array( "nextPlayer" => 11 )
    ),  
    
    /// 2nd phase: PERSONS //////
    20 => array(
        "name" => "personPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stPersonPhaseNextPlayer",
        "transitions" => array( "nextPlayer" => 21, "endPhase" => 30 )
    ),      
    21 => array(
        "name" => "personPhaseChoosePerson",
        "description" => clienttranslate('${actplayer} must choose a person tile to recruit'),
        "descriptionmyturn" => clienttranslate('${you} must choose a person tile to recruit'),
        "possibleactions" => array( "recruit" ),
        "action" => "stPersonPhaseChoosePerson",
        "type" => "activeplayer",
        "transitions" => array( "chooseTile" => 22, "palaceFull" => 23, "zombiePass" => 20, "notPossible" => 20 )
    ),   
    22 => array(
        "name" => "personPhasePlace",
        "description" => clienttranslate('${actplayer} must place his new person tile in one of his palace'),
        "descriptionmyturn" => clienttranslate('${you} must place your new person tile in one of your palace'),
        "possibleactions" => array( "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "" => 20 )
    ),      
    23 => array(
        "name" => "palaceFull",
        "description" => clienttranslate('${actplayer} must choose which person to replace with the new one'),
        "descriptionmyturn" => clienttranslate('${you} must choose which person to replace with the new one'),
        "possibleactions" => array( "releaseReplace", "place" ),
        "args" => "argPlaceTile",
        "type" => "activeplayer",
        "transitions" => array( "" => 20 )
    ), 

    /// 3rd phase: EVENTS //////
    30 => array(
        "name" => "eventPhase",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhase",
        "transitions" => array( "releaseRound" => 31 )
    ),      
    31 => array(
        "name" => "eventPhaseApplyConsequences",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhaseApplyConsequences",
        "transitions" => array( "releasePerson" => 32, "noRelease" => 33 )
    ),           
    32 => array(
        "name" => "release",
        "description" => clienttranslate('${actplayer} must choose ${nbr} person(s) to release from his palaces'),
        "descriptionmyturn" => clienttranslate('${you} must choose ${nbr} person(s) to release from your palaces'),
        "possibleactions" => array( "release" ),
        "action" => "stRelease",
        "args" => "argNbrToRelease",
        "type" => "activeplayer",
        "transitions" => array( "continueRelease" => 32, "endRelease" => 33 )
    ), 
    33 => array(
        "name" => "eventPhaseNextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stEventPhaseNextPlayer",
        "transitions" => array( "endPhase" => 39, "nextPlayer" => 31, "greatWall" => 34 )
    ),

    34 => array(
        "name" => "greatWallEvent",
        "description" => '',
        "type" => "game",
        "action" => "stGreatWall",
        "transitions" => array( "releasePerson" => 32, "noRelease" => 33, "endPhase" => 39 )
    ),

    39 => array(
        "name" => "decayAndScoring",
        "description" => '',
        "type" => "game",
        "action" => "stDecayAndScoring",
        "transitions" => array( "nextTurn" => 10, "finalScoring" => 98 )
    ),
    
    /// Final scoring //////
    98 => array(
        "name" => "finalScoring",
        "description" => '',
        "type" => "game",
        "action" => "stFinalScoring",
        "transitions" => array( "" => 99 )
    ),    
   
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);

?>
