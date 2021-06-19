<?php
/**
  * intheyearofthedragonexp.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  * Implementation of Great Wall and Super Events expansions: @David Edelstein <davidedelstein@gmail.com>
  *
  *
  * intheyearofthedragonexp main game core
  *
*/

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

define('SUPER_EVENT', "SUPER_EVENT");
define('SUPER_EVENT_DONE', "SUPER_EVENT_DONE");
define('SUPER_EVENT_ACTION', "SUPER_EVENT_ACTION");
define('SUPER_EVENT_FIRST_PLAYER', "seFirstPlayer");

class InTheYearOfTheDragonExp extends Table
{
	function __construct( )
	{
	    // Note: remaining big/small favor are NO MORE USED (indeed, there is no limit)
        parent::__construct();
        
        self::initGameStateLabels( 
            array( 
                "remainingSmallFavor" => 10,
                "remainingBigFavor" => 11,
                "toPlaceType"=>12,
                "toPlaceLevel"=>13,
                "toBuild" => 14,
                "month" => 15,
                "toRelease" => 16,
                "lowerHelmet" => 17,
                "toReduce" => 18,
                "wallLength" => 20,
                "minWalls" => 21, // minimum number of Wall tiles built in current turn
                SUPER_EVENT_FIRST_PLAYER => 22, // flag for rotating players in super event
                SUPER_EVENT => 23,
                SUPER_EVENT_DONE => 24,
                SUPER_EVENT_ACTION => 30,
                "largePrivilegeCost" => 100,
                "greatWall" => 101,
                "superEvents" => 102,
            ));
    
        $this->tie_breaker_description = self::_("Position on the person track (descending order)");                
	}
	
    protected function getGameName( )
    {
        return "intheyearofthedragonexp";
    }	

    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 
 
        // Create players
        $default_color = array( "ff0000", "008000", "0000ff", "ffa500", "ff00ff" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, array( "ff0000", "008000", "0000ff", "ffa500", "ff00ff" ) );
        self::reloadPlayersBasicInfos();

        // Create event list //////////////////////////////////////
        $events = array( 1 => 1, 2 => 1 );    // 2 "peace" events to begin
        $remaining_events = array( 2, 2, 3, 3, 4, 4, 5, 5, 6, 6 );
        shuffle( $remaining_events );

        for( $i=3; $i<=12; $i++ )
        {
            $new_event = array_pop( $remaining_events );
            
            // Check if event n-1 is the same
            if( $events[ $i-1 ] == $new_event )
            {
                if( $i != 12 )
                {
                    // In such a case, we take the next event on the list and replace this one
                    $event_postponed = $new_event;
                    $new_event = array_pop( $remaining_events );
                    array_push( $remaining_events, $event_postponed );
                }
                else
                {
                    // The 2 last tiles are the same ! In this case, we exchange tiles in position 10 and 11
                    $tmp = $events[10];
                    $events[10] = $events[11];
                    $events[11] = $tmp;
                }
            }
            
            // General case
            $events[$i] = $new_event;
        }
        
        // Write events in DB
        $sql = "INSERT INTO year (year_id, year_event) VALUES ";
        $sql_values = array();
        foreach( $events as $id => $event )
        {
            $sql_values[] = "('$id','$event')";
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
        
        // Personpool
        $player_nbr = count( $players );
        $sql = "INSERT INTO personpool (personpool_type, personpool_level, personpool_nbr) VALUES ";
        $sql_values = array();
        foreach( $this->person_types as $type_id => $person_type )
        {
            if( count( $person_type['subtype'] ) == 1 )
            {
                // 10 person level I
                $nbr = 10-2*(5-$player_nbr);    // Each missing player => 2 less person
                $sql_values[] = "('$type_id','1','$nbr')";
            }
            else
            {
                // 6 persons level I + 4 level II
                $nbrYoung = 6-(5-$player_nbr);    // Each missing player => 1 less person
                $nbrOld = 4-(5-$player_nbr);    // Each missing player => 1 less person
                $sql_values[] = "('$type_id','1','$nbrYoung')";
                $sql_values[] = "('$type_id','2','$nbrOld')";
            }
        }
        
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
        
        // Personcard
        $sql = "INSERT INTO personcard (personcard_player, personcard_type) VALUES ";
        $sql_values = array();
        foreach( $players as $player_id => $player )
        {
            // 1 card for each person type + 2 joker cards
            foreach( $this->person_types as $type_id => $person_type )
            {
                $sql_values[] = "('$player_id','$type_id')";
            }
            
            $sql_values[] = "('$player_id','0')";   // JOKER
            $sql_values[] = "('$player_id','0')";   // JOKER
        }        
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );

        // 2 palaces with 2 floors / player
        $sql = "INSERT INTO palace (palace_player, palace_size) VALUES ";
        $sql_values = array();
        foreach( $players as $player_id => $player )
        {
            $sql_values[] = "('$player_id','2')";
            $sql_values[] = "('$player_id','2')";
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
        
        // Globals
        self::setGameStateInitialValue( 'remainingSmallFavor', 5 ); // Depreciated
        self::setGameStateInitialValue( 'remainingBigFavor', 3 );   // Depreciated
        self::setGameStateInitialValue( 'toPlaceType', 0 );
        self::setGameStateInitialValue( 'toPlaceLevel', 0 );
        self::setGameStateInitialValue( 'toBuild', 0 );
        self::setGameStateInitialValue( 'month', 1 );
        self::setGameStateInitialValue( 'toRelease', 0 );
        self::setGameStateInitialValue( 'toReduce', 0 );
        self::setGameStateInitialValue( 'lowerHelmet', 0 );
        self::setGameStateInitialValue( 'wallLength', 0 );
        self::setGameStateInitialValue( 'minWalls', 0 );
        self::setGameStateInitialValue( SUPER_EVENT_FIRST_PLAYER, 0 );
        self::setGameStateInitialValue( SUPER_EVENT, 0 ); // note this is different from "superEvents" which is the gameoptions value
        self::setGameStateInitialValue( SUPER_EVENT_DONE, 0 );
        self::setGameStateInitialValue( SUPER_EVENT_ACTION, 0 );

        // Statistics
        self::initStat( 'table', 'person_lost_events_allplayers', 0 );
        self::initStat( 'player', 'person_lost_events', 0 );
        self::initStat( 'player', 'palace_nbr', 0 );
        self::initStat( 'player', 'decay', 0 );
        self::initStat( 'player', 'action_payed', 0 );
        self::initStat( 'player', 'points_palace', 0 );
        self::initStat( 'player', 'points_privilege', 0 );
        self::initStat( 'player', 'points_court_ladies', 0 );
        self::initStat( 'player', 'points_scholars', 0 );
        self::initStat( 'player', 'points_fireworks', 0 );
        self::initStat( 'player', 'points_person', 0 );
        self::initStat( 'player', 'points_monks', 0 );
        self::initStat( 'player', 'points_remaining', 0 );
        self::initStat( 'player', 'points_mongol', 0 );

        if ($this->useGreatWall()) {
            self::initStat('table', 'walls_built_allplayers', 0);
            self::initStat( 'player', 'walls_built', 0 );
            self::initStat( 'player', 'points_wall', 0 );
            $this->initializeWall();
        }
        $this->initializeSuperEvent();

        self::activeNextPlayer();
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        // Add players ityotd specific infos
        $sql = "SELECT player_id id, player_score score, player_action_choice action_choice,
                player_yuan yuan, player_rice rice, player_fireworks fireworks, player_favor favor,
                player_person_score person_score, player_person_score_order person_score_order 
                FROM player ";
        $dbres = self::DbQuery( $sql );
        while( $player = mysql_fetch_assoc( $dbres ) )
        {
            $result['players'][ $player['id'] ] = $player;
        }
        
        // Get year events
        $result['year'] = self::getEvents();
        $result['event_types'] = $this->event_types;
        
        // Person types
        $result['person_types'] = $this->person_types;
        
        // Count person pool
        $result['personpool'] = self::getObjectListFromDB( "SELECT personpool_type type, personpool_level level, personpool_nbr nbr FROM personpool" );
        
        // Palaces
        $result['palace'] = self::getCollectionFromDB( "SELECT palace_id id, palace_player player, palace_size size FROM palace" );
        
        // Person in palaces
        $result['personpalace'] = self::getCollectionFromDB( "SELECT palace_person_id id, palace_person_palace_id palace_id, palace_person_type type, palace_person_level level 
                                                              FROM palace_person" );
        
        // Actions
        $result['actions'] = self::getCollectionFromDB( "SELECT action_id, action_type FROM action", true );
        $result['action_types'] = $this->action_types;
        
        $result['month'] = self::getGameStateValue('month');
        
        $result['largePrivilegeCost'] = $this->getLargePrivilegeCost();

        // $result['droughtPalaces'] = $this->getOverfilledPalaces();

        if ($this->useGreatWall()) {
            $result['greatWall'] = $this->getWallTiles();
        }

        $result['superEvent'] = self::getGameStateValue(SUPER_EVENT);
        if (self::getGameStateValue(SUPER_EVENT) != 0) {
            $result['super_events'] = $this->superevents;
        }
  
        return $result;
    }
    
    // Return an array with options infos for this game
    function getGameOptionsInfos()
    {
        return array(
        );
    }

    function getGameProgression()
    {
        // Game progression: get player maximum score
        
        $month = self::getGameStateValue( 'month' );
        
        // month => 1 to 12
        return round( ( ($month-1)*100/11 ) );
    }

//////////////////////////////////////////////////////////////////////////////
////// Setup Functions
//////////////////////////////////////////////////////////////////////////////

    /**
     * Initialize the wall tiles for Great Wall
     */
     protected function initializeWall() {
        $players = self::loadPlayersBasicInfos();

        foreach( $players as $player_id => $player ) {
            foreach ($this->wall_tiles as $w => $wall) {
                self::DbQuery( "INSERT INTO WALL (player_id, bonus, location) VALUES($player_id, $w, 0)" );
            }
        }
    }

    /**
     * Maps the SuperEvents option to the Materials ordinals.
     * Sets superEvent gamestate value.
     * Sets to 1-10 (for event), 0 (for no Superevents) or 11 (if it's hard mode random and not revealed yet)
     */
     protected function initializeSuperEvent() {
        $se = self::getGameStateValue( 'superEvents' );
        if ($se == 1) {
            self::setGameStateValue(SUPER_EVENT, 0);
        } else if ($se == 2) {
            self::setGameStateValue(SUPER_EVENT, bga_rand(1,10));
        } else if ($se == 13) {
            self::setGameStateValue(SUPER_EVENT, 11);
        } else {
            self::setGameStateValue(SUPER_EVENT, $se-2);
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions    (functions used everywhere)
////////////    

    /**
     * Depends on the game state variable
     */
    function getLargePrivilegeCost() {
        return self::getGameStateValue( 'largePrivilegeCost' ) == 1 ? 7 : 6;
    }

    /**
     * Are we using the Great Wall expansion?
     */
    function useGreatWall() {
        return self::getGameStateValue( 'greatWall' ) == 2;
    }

    /**
     * Check whether this is a Mongol Invasion month (or turn 12) AND we're using the Great Wall.
     * @returns true if this is a Mongol Invasion/last turn and Great Wall is being used
     */
    function isGreatWallEvent() {
        $gw = false;
        if ($this->useGreatWall()) {
            $month = self::getGameStateValue( 'month' );
            $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
            $gw = ($event == 5) || ($month == 12);
        }
        return $gw;
    }

    /**
     * Check whether this is Month 7 and Super Events are enabled.
     */
    function isSuperEvent() {
        $s_ev = false;
        if (self::getGameStateValue('month') == 7) {
            if (self::getGameStateValue(SUPER_EVENT) != 0) {
                return true;
            }
        }
        return $s_ev;
    }

    /**
     * It's the Super Event phase.
     * Return the next phase to go to
     */
    function doSuperEvent() {
        $state = "endPhase";
        $superevent = self::getGameStateValue(SUPER_EVENT);
        switch ($superevent) {
            case 0:
                // this shouldn't happen, we shouldn't call this if not using Super Events
                throw new BgaVisibleSystemException ( "Super Events called when that option was not enabled" ); // NOI18N
                break;
            case 1:
                // Lanternfest
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players score their people' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $this->scorePersons();
                break;
            case 2:
                // Buddha
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players score their Monks' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $this->scoreMonks(0);
                break;
            case 3:
                // Earthquake
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players lose two palace sections' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "earthquake";
                break;
            case 4:
                // Flood
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players lose half their resources (rounded down)' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "flood";
                break;
            case 5:
                // Solar Eclipse
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: Repeat previous event' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "solar";
                break;
            case 6:
                // Volcanic Eruption
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players set back to 0 on the person track' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $this->resetPlayerPlayOrder();
                break;
            case 7:
                // Tornado
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players must discard two person cards' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "tornado";
                break;
            case 8:
                // Sunrise
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players select one young person tile' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "sunrise";
                break;
            case 9:
                // Assassination Attempt
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players lose all privileges' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                self::DbQuery( "UPDATE player SET player_favor=0" );
                break;
            case 10:
                // Charter
                self::notifyAllPlayers( 'superEvent', '${superevent_i}'.clienttranslate( '${superevent_name}: All players select one person type in their realm and gain the appropriate benefits' ), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent_i' => $superevent,
                ));
                $state = "charter";
                break;
            case 11:
                // Hard Mode random, determine event now
                $se = bga_rand(1,10);
                self::setGameStateValue(SUPER_EVENT, $se);
                self::notifyAllPlayers( 'superEventChosen', clienttranslate('Super event revealed: ${superevent_name}'), array(
                    'superevent_name' => $this->superevents[$superevent]['nametr'],
                    'superevent' => $se,
                ) );
                $state = $this->doSuperEvent();
                break;
        }
        return $state;
    }

    /**
     * Get all the Wall tiles.
     */
     protected function getWallTiles() {
        return self::getNonEmptyCollectionFromDB("SELECT * FROM WALL");
    }

    /**
     * Returns all the unplayed Wall tiles for this player as an associative array.
     */
    function getAvailableWallTiles($player_id) {
        $tiles = self::getCollectionFromDB("SELECT * from WALL WHERE player_id = $player_id AND location = 0");
        return $tiles;
    }

    /**
     * Count how many Great Wall sections were built by this player.
     */
    function countWallTilesBuilt($player_id) {
        $tiles = self::getObjectListFromDB("SELECT id from WALL WHERE player_id = $player_id AND location != 0", true);
        return count($tiles);
    }

    /**
     * Give player bonus for their wall section.
     * #return TRUE if we are entering build state, otherwise false
     */
    function assignWallBonus($wall) {
        $tobuild = false;
        $player_id = $wall['player_id'];
        switch($wall['bonus']) {
            case 1:
                // PP
                $this->addPersonPoints($player_id, 3);
                break;
            case 2:
                // Rice
                $this->addRice($player_id, 1);
                break;
            case 3:
                // Palace
                self::setGameStateValue( 'toBuild', 1 );
                $tobuild = true;
                break;
            case 4:
                // Yuan
                $this->addYuan($player_id, 2);
                break;
            case 5:
                // Fireworks
                $this->addFireworks($player_id, 1);
                break;
            case 6:
                // Gain 3 VP
                $this->addVictoryPoints($player_id, 3);
                break;
        }
        return $tobuild;
    }

    /**
     * Get the correct set of action groups.
     */
    function getActionGroups() {
        if ($this->useGreatWall()) {
            return $this->action_to_actiongroup_8;
        } else{
            return $this->action_to_actiongroup_7;
        }
    }

    function getEvents()
    {
        return self::getCollectionFromDB( 'SELECT year_id,year_event FROM year ORDER BY year_id', true );
    }

    function getPersoncards()
    {
        global $g_user;
        $player_id = $g_user->get_id();
        return self::getCollectionFromDB( "SELECT personcard_id id, personcard_type type FROM personcard WHERE personcard_player='$player_id' " );
    }
    
    /**
     * Reorder player order, assigning player_play_order by player_person_score/player_person_score_order.
     * 1 = 1st
     */
    function updatePlayerPlayOrder()
    {
        $player_ids = self::getObjectListFromDB( "SELECT player_id FROM player
                                                  ORDER BY player_person_score DESC, player_person_score_order DESC", true );

        $playorder = 1;
        foreach( $player_ids as $player_id )
        {
            self::DbQuery( "UPDATE player SET player_play_order='$playorder' WHERE player_id='$player_id' " );
            $playorder ++;
        }
    }

    /**
     * For Lanternfest super event.
     */
    function scorePersons() {
        $players = self::loadPlayersBasicInfos();
        $player_person = $this->countPersons();
        foreach ($player_person as $player_id => $personct) {
            $personscore = 2*$personct;
            self::incStat( $personscore, 'points_person', $player_id );
            self::DbQuery( "UPDATE player SET player_score=player_score+$personscore WHERE player_id='$player_id' " );
            self::notifyAllPlayers( 'gainPoint', clienttranslate( '${player_name} scores ${nbr} points' ), array(
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'nbr' => $personscore
            ));
        }
    }

    /**
     * For Buddha or Charter event.
     * If scoring_player=0, score all players. Otherwise, score only the player id passed.
     */
    function scoreMonks($scoring_player) {
        $players = self::loadPlayersBasicInfos();

        $monks = $this->countMonks();
        $monk_points = array();
        foreach( $monks as $m => $monk ) {
            $player_id = $monk['palace_player'];
            $points = $monk['level'] * $monk['palace_size'];  // Note: 1 buddha on level 1, 2 buddhas on level 2

            if (isset($monk_points[$player_id])) {
                $monk_points[$player_id] += $points;
            } else {
                $monk_points[$player_id] = $points;
            }
        }
        foreach( $monk_points as $player_id => $pts) {
            // if there is a scoring_player that's the only one being scored
            if ($scoring_player != 0 && $scoring_player != $player_id) {
                continue;
            }
            self::incStat( $pts, 'points_monks', $player_id );
            self::DbQuery( "UPDATE player SET player_score=player_score+$pts WHERE player_id='$player_id' " );
            self::notifyAllPlayers( 'gainPoint', clienttranslate( '${player_name} scores ${nbr} points from Monks' ), array(
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'nbr' => $pts
            ));
        }
    }

    /**
     * For Volcanic Eruption, set all player person points to 0.
     */
    function resetPlayerPlayOrder() {
        $player_ids = self::getCollectionFromDB( "SELECT player_id, player_person_score pp FROM player
                                                  ORDER BY player_person_score DESC, player_person_score_order DESC", true );
        // reset all score orders to 0
        self::DbQuery( "UPDATE player SET player_person_score_order=0" );
        // iterate in reverse order so new player_person_score_order will be correctly incremented
        foreach( array_reverse($player_ids, true) as $player_id => $pp ) {
            $this->increasePersonScore($player_id, -$pp);
        }
    }

    /**
     * Set whoever is 1 in player_play_order as active player
     */
    function activeFirstPlayerInPlayOrder()
    {
        $player_ids = self::getObjectListFromDB( "SELECT player_id FROM player
                                                  ORDER BY player_play_order", true );

        $player_id = array_shift( $player_ids );
        $this->gamestate->changeActivePlayer( $player_id );
    }

    /**
     * If a player has not gone yet in current rotation, set that player to the active player and return true.
     * Otherwise return false.
     */
    function activeNextPlayerInPlayOrder()
    {
        $player_ids = self::getObjectListFromDB( "SELECT player_id FROM player
                                                  ORDER BY player_play_order", true );
        $next_player = self::createNextPlayerTable( $player_ids, false );
        $current_player = $next_player[ self::getActivePlayerId() ];
        
        if( $current_player === null )
            return false;
        
        $this->gamestate->changeActivePlayer( $current_player );
        return true;
    }  
    
    // Count items present on player's persons with specified type
    function countItemsByType( $player_id, $person_type )
    {
        $result = 0;
        $person_type_details = $this->person_types[ $person_type ];
        
        $sql = "SELECT palace_person_level level FROM palace_person
                INNER JOIN palace ON palace_id=palace_person_palace_id
                WHERE palace_player='$player_id' AND palace_person_type='$person_type' ";
        $persons = self::getObjectListFromDB( $sql );
        foreach( $persons as $person )
        {
            $result += $person_type_details['subtype'][ $person['level'] ]['items'];
        }
        
        return $result;
    }    
    
    // Return player_id => items number
    function countItemsByTypeForAll( $person_type )
    {
        $result = array();

        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $result[ $player_id ] = 0;
        }
        
        $person_type_details = $this->person_types[ $person_type ];
        
        $sql = "SELECT palace_person_level level, palace_player player FROM palace_person
                INNER JOIN palace ON palace_id=palace_person_palace_id
                WHERE palace_person_type='$person_type' ";
        $persons = self::getObjectListFromDB( $sql );
        foreach( $persons as $person )
        {
            $result[ $person[ 'player' ] ] += $person_type_details['subtype'][ $person['level'] ]['items'];
        }
        
        return $result;
    }

    /**
     * Count the rice+fireworks+yuan owned by current player, return half the total (rounded down).
     */
    function countResourcesToReduce() {
        $player_id = self::getActivePlayerId();
        $resources = self::getObjectFromDB( "SELECT player_rice rice, player_fireworks fireworks, player_yuan yuan FROM player WHERE player_id='$player_id' " );
        $count = 0;
        foreach ($resources as $res => $ct ) {
            $count += $ct;
        }
        $toReduce = floor($count/2);
        return $toReduce;
     }

    // /**
    //  * Get the palace ids of palaces to remove people from (Drought or Earthquake)
    //  */
    // function getOverfilledPalaces() {
    //     $player_id = self::getActivePlayerId();
    //     $sql = "SELECT palace_id FROM palace WHERE palace_player=$player_id AND palace_drought_affected != 0";
    //     $palaces = self::getObjectListFromDB($sql, true);
    //     return $palaces;
    // }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    // Recruit a new person
    function recruit( $type, $level )
    {
        self::checkAction( 'recruit' );
        $player_id = self::getActivePlayerId();
        
        // Check this person type/level exists
        if( ! isset( $this->person_types[ $type ] ) )
            throw new feException( 'This type does not exists' );
        if( ! isset( $this->person_types[ $type ]['subtype'][$level] ) )
            throw new feException( 'This type does not exists' );
        
        // Check there is tiles available
        $nbr = self::getUniqueValueFromDB( "SELECT personpool_nbr FROM personpool
                                            WHERE personpool_type='$type' AND personpool_level='$level' ");
        
        if( $nbr == 0 )
        {
           // "No more person from this type"
           
        }
        
        $state = $this->gamestate->state();
        $bInitialChoice = ( $state['name'] == 'initialChoice' );
        $bSunrise = ( $state['name'] == 'sunriseRecruit' );

        if ( $bInitialChoice || $bSunrise ) {
            // If this is "initial" mode:
            // _ choose only level 1
            // _ choose 2 different persons
            // _ choose a different combination than another player
            // Sunrise super event: choose only 1 person and don't use a card
            
            if( $level != 1 ) {
                if ($bInitialChoice) {
                    throw new BgaUserException( self::_("During the initial phase you must recruit younger persons") );
                } else {
                    throw new BgaUserException( self::_("During the Sunrise super event you must recruit a younger person") );
                }
            }
            if ($nbr == 0 && $bSunrise) {
                throw new BgaUserException( self::_("There are no more young persons of this type available") );
            }

            $first_type_chosen = self::getUniqueValueFromDB( "SELECT palace_person_type FROM palace_person INNER JOIN palace ON palace_id=palace_person_id WHERE palace_player=$player_id" );
                                                              
            if( $first_type_chosen !== null && $first_type_chosen==$type ) {
                throw new BgaUserException( self::_( "Your two initial persons must be different" ) );
            }

            if ($bInitialChoice) {
                $all_persons = self::getCollectionFromDB( "SELECT palace_person_id id, palace_person_palace_id palace_id, palace_person_type type, palace_person_level level,
                                                                palace_player player
                                                                FROM palace_person
                                                                INNER JOIN palace ON palace_id=palace_person_id
                                                                WHERE palace_player!='$player_id' " );
                
                $players = self::loadPlayersBasicInfos();
                foreach( $players as $opponent_id => $player ) {
                    if( $opponent_id != $player_id )    // Only opponents
                    {
                        $nbr_person_in_common = 0;

                        foreach( $all_persons as $person ) {
                            if( $person['player'] == $opponent_id ) {
                                if( $person['type'] == $first_type_chosen || $person['type'] == $type )
                                $nbr_person_in_common++;
                            }
                        }

                        if( $nbr_person_in_common == 2 ) {
                            throw new BgaUserException( sprintf( self::_("You cannot choose this combination of persons because %s chose the same one"), $player['player_name'] ) );
                        }
                    }
                }
            } else {
                // sunrise
                $sunrise = self::getUniqueValueFromDB( "SELECT personpool_sunrise FROM personpool WHERE personpool_type=$type AND personpool_level=$level");
                if ($sunrise) {
                    throw new BgaUserException( self::_("This person type was already chosen in the Sunrise phase") );
                } else {
                    self::DBQuery( "UPDATE personpool SET personpool_sunrise = 1 WHERE personpool_type=$type AND personpool_level=$level");
                }
            }
        } else {
                // Check there is some personcard available
                // (note: not for initial recruitment)
                
                $personcard_id = self::getUniqueValueFromDB( "SELECT personcard_id FROM personcard WHERE personcard_type='$type' AND personcard_player='$player_id'" );
                if( $personcard_id === null )
                {
                    // No available card => we take a "joker" one
                    $personcard_id = self::getUniqueValueFromDB( "SELECT personcard_id FROM personcard WHERE personcard_type='0' AND personcard_player='$player_id' LIMIT 0,1" );
    
                    if( $personcard_id === null )
                        throw new feException( self::_("You have no remaining valid person card to recruit this person"), true );
                }
                // still need to check in case of zombie player
                if ($personcard_id != null) {
                    // Remove this personcard
                    $sql = "DELETE FROM personcard WHERE personcard_id='$personcard_id' ";
                    self::DbQuery( $sql );

                    // Notify this player
                    self::notifyPlayer( $player_id, 'usePersonCard','',  array(
                        'personcard_id' => $personcard_id
                    ) );
                }
        }

        if( $nbr == 0 )
        {
            $this->gamestate->nextState( 'notPossible' );
            return;
        }
                                        
        // Select person as "to be placed"
        self::setGameStateValue( 'toPlaceType', $type );
        self::setGameStateValue( 'toPlaceLevel', $level );
        
        // Next step...
        
        // If all palaces are full ... ////
        $totalSpace = self::getUniqueValueFromDB( "SELECT SUM( palace_size ) FROM palace WHERE palace_player='$player_id' " );
        $totalPersons = self::getUniqueValueFromDB( "SELECT COUNT( palace_person_id ) cnt
                                                    FROM palace_person
                                                    INNER JOIN palace ON palace_id = palace_person_palace_id
                                                    WHERE palace_player='$player_id' " );
                              
        if( $totalPersons < $totalSpace )
            $this->gamestate->nextState('chooseTile');
        else      
            $this->gamestate->nextState('palaceFull' );
    }
    
    // Place selected tile in this palace
    function place( $palace_id )
    {
        self::checkAction( 'place' );
        
        $tile_type = self::getGameStateValue( 'toPlaceType' );
        $tile_level = self::getGameStateValue( 'toPlaceLevel' );
        $tile_persontype = $this->person_types[ $tile_type ];
        $tile_subtype = $this->person_types[ $tile_type ]['subtype'][ $tile_level ];
        
        // Check if tile exists and is available
        // ===> already done at the previous step
        
        // Check if this palace exists and get its infos
        $player_id = self::getCurrentPlayerId();
        $sql = "SELECT palace_size FROM palace WHERE palace_id='$palace_id' AND palace_player='$player_id' ";
        $palace_size = self::getUniqueValueFromDb( $sql );
        
        // Get all persons in this palace
        $already_in_palace = self::getUniqueValueFromDB( "SELECT COUNT( palace_person_id ) FROM palace_person WHERE palace_person_palace_id='$palace_id'" );
        
        // Check if there is some space available
        if( $already_in_palace == $palace_size )
            throw new feException( self::_("This palace is full"), true );
        
        // Okay !
        // Place this tile in this palace
        $sql = "INSERT INTO palace_person (palace_person_palace_id,palace_person_type,palace_person_level)
                VALUES ('$palace_id', '$tile_type', '$tile_level' )";
        self::DbQuery( $sql );
        $tile_id = self::DbGetLastId();
        
        // Reduce number of available tile
        self::DbQuery( "UPDATE personpool SET personpool_nbr = personpool_nbr-1
                                          WHERE personpool_type='$tile_type' AND personpool_level='$tile_level' ");


        // Player current person score
        $tile_person_score = $tile_subtype['value'];
        self::increasePersonScore( $player_id, $tile_person_score );                                

        
        // Notify
        $i18n = array( 'person_type_name' );
        $details = '';
        $message = clienttranslate('${player_name} places a ${person_type_name} in one of his palace');
        if( count( $tile_persontype['subtype'] ) > 1 )
        {
            $i18n[] = 'details';
            $message = clienttranslate('${player_name} places a ${person_type_name} (${details}) in one of his palace');
            if( $tile_level == 1 )
                $details = clienttranslate('young');
            else
                $details = clienttranslate('old');
        }
        self::notifyAllPlayers( 'placePerson', $message, array(
            'i18n' => $i18n,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'person_type' => $tile_type,
            'person_level' => $tile_level,
            'palace_id' => $palace_id,
            'person_id' => $tile_id,
            'person_type_name' => $tile_persontype['name'],
            'details' => $details
        ) );
        $state = self::getGameStateValue(SUPER_EVENT_ACTION) == 1 ? 'sunrise' : 'nextPhase';
        $this->gamestate->nextState( $state );
    }

    // Increase person score of given player
    // Also update player_person_score_order and notify
    function increasePersonScore( $player_id, $inc )
    {
        // Player current person score
        $current_score = self::getUniqueValueFromDB( "SELECT player_person_score FROM player WHERE player_id='$player_id' " );
        $new_score = $current_score + $inc;
        // Get "maximum" place at new_score
        $max_place = self::getUniqueValueFromDB( "SELECT COALESCE( MAX( player_person_score_order ), 0) FROM player WHERE player_person_score='$new_score'" );
        $place_id = $max_place+1;
        
        // Add person score points to player & set place
        self::DbQuery( "UPDATE player SET player_person_score=$new_score,
                                          player_person_score_order=$place_id
                                          WHERE player_id='$player_id' ");
        
        self::notifyAllPlayers( 'personScoreUpdate', '', array(
            'player_id' => $player_id,
            'person_score' => $new_score,
            'person_score_place' => $place_id,        
        ) );
    }
    
    /**
     * Add to person track and send notification to players.
     */
    function addPersonPoints($player_id, $pp) {
        self::increasePersonScore( $player_id, $pp );
        $players = self::loadPlayersBasicInfos();
            
        self::notifyAllPlayers( 'personPointMsg', clienttranslate( '${player_name} advances ${nbr} spaces on the person track' ), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $pp
        ) );
    }

    /**
     * Add rice and send notification to players.
     */
    function addRice($player_id, $rice) {
        $sql = "UPDATE player SET player_rice=player_rice+$rice WHERE player_id='$player_id' ";
        self::DbQuery( $sql );
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'harvest', clienttranslate( '${player_name} gains ${nbr} rice' ), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $rice
        ) );
    }

    /**
     * Add Fireworks and send notification to players.
     */
    function addFireworks($player_id, $fw) {
        $sql = "UPDATE player SET player_fireworks=player_fireworks+$fw WHERE player_id='$player_id' ";
        self::DbQuery( $sql );
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'fireworks', clienttranslate( '${player_name} gains ${nbr} fireworks' ), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $fw
        ) );
    }

    /**
     * Add yuan and send notification to players.
     */
    function addYuan($player_id, $yuan) {
        $sql = "UPDATE player SET player_yuan=player_yuan+$yuan WHERE player_id='$player_id' ";
        self::DbQuery( $sql );
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'taxes', clienttranslate( '${player_name} gains ${nbr} yuan' ), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $yuan
        ) );
    }

    /**
     * Add VPs and send notification to players.
     */
    function addVictoryPoints($player_id, $vp) {
        $sql = "UPDATE player SET player_score=player_score+$vp WHERE player_id='$player_id' ";
        self::DbQuery( $sql );
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'gainPoint', clienttranslate( '${player_name} gets ${nbr} points' ), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'nbr' => $vp
        ) );
    }

    // Realize an action
    function action( $action_id )
    {
        self::checkAction( 'action' );
        
        $player_id = self::getActivePlayerId();

        // Check if action exists
        if( ! isset( $this->action_types[ $action_id ] ) )
            throw new feException( 'This action does not exists' );

        // Get action position from its id
        $action_position = self::getUniqueValueFromDB( "SELECT action_id FROM action WHERE action_type='$action_id' " );
        
        // Check if someone already realize some action from the same group
        // (in this case: it costs 3 yuans)
        $player_actions = self::getCollectionFromDB( "SELECT player_id, action_id
                                                      FROM player
                                                      INNER JOIN action ON action_type=player_action_choice", true );
        $players = self::loadPlayersBasicInfos();
        $actiongroups = $this->getActionGroups();
        $actionconfig = $actiongroups[ count( $players ) ];
        $this_group = $actionconfig[ $action_position ];
        $bOccupiedGroup = false;
        foreach( $player_actions as $opponentplayer_id => $playeraction_id )
        {
            if( $playeraction_id !== null )
            {
                if( $actionconfig[ $playeraction_id ] == $this_group )
                {
                    $bOccupiedGroup = true;
                    break;
                }
            }
        }
        
        $moneycost = 0;
        $notiftext = clienttranslate( '${player_name} chooses action ${action_name}' );
        
        if( $bOccupiedGroup )
        {
            // This player must pay 3 yuans to use this action
            $money = self::getUniqueValueFromDB( "SELECT player_yuan FROM player WHERE player_id='$player_id' " );
            if( $money >= 3 )
            {
                // Okay
                $sql = "UPDATE player SET player_yuan=player_yuan-3 WHERE player_id='$player_id' ";
                self::DbQuery( $sql );
                $moneycost = 3;
                $notiftext = clienttranslate( '${player_name} chooses action ${action_name} for 3 yuan' );
                self::incStat( 1, 'action_payed', $player_id );
            }
            else {
                throw new BgaUserException( self::_("You do not have the required 3 yuan to pay for this action"));
            }
        }
        
        // This player => this action
        $sql = "UPDATE player SET player_action_choice='$action_id' WHERE player_id='$player_id' ";
        self::DbQuery( $sql );
        
        // Notify
        self::notifyAllPlayers( 'actionChoice', $notiftext, array(
            'i18n' => array( 'action_name' ),
            'player_name' => self::getCurrentPlayerName(),
            'player_id' => $player_id,
            'action_id' => $action_id,
            'action_name' => $this->action_types[ $action_id ]['name'],
            'pay' => $bOccupiedGroup ? 3 : 0
        ) );        

        
        // Perform this action special effect
        $nextState = 'nextPlayer';
        if( $action_id == 1 )
        {   // Taxes
            // Get all tax collectors (4)
            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 4 );
            
            $this->addYuan($player_id, $items);
        }
        else if( $action_id == 2 )
        {   // Build
            // Get all craftsmen (1)
            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 1 );
            
            self::setGameStateValue( 'toBuild', $items );
            $nextState = 'buildAction';
        }
        else if( $action_id == 3 )
        {   // Harvest
            // Get all Farmers (8)
            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 8 );
            $this->addRice($player_id, $items);
        }
        else if( $action_id == 4 )
        {   // Fireworks
            // Get all Pyrotechnists (3)
            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 3 );
            $this->addFireworks($player_id, $items);
        }
        else if( $action_id == 5 )
        {   // Military parade
            // Get all wariors (5)

            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 5 );
            $this->addPersonPoints($player_id, $items);
        }
        else if( $action_id == 6 )
        {   // Research
            // Get all scholars (9)

            $items = $this->action_types[ $action_id ]['items'] + self::countItemsByType( $player_id, 9 );
            $this->addVictoryPoints($player_id, $items);
            
            self::incStat( $items, 'points_scholars', $player_id );
        }
        else if( $action_id == 7 )
        {   // Privilege
            
            // We must check that there is enough remaining privilege and that player can afford it.
            $money = self::getUniqueValueFromDB( "SELECT player_yuan FROM player WHERE player_id='$player_id' " );

            if( $money < 2 ) {
                throw new BgaUserException( self::_("You don't have enough yuan to buy a privilege"));
            }

            $nextState = 'privilegeAction';
        } else if ($action_id == 8) {
            // Great Wall
            $nextWall = self::getGameStateValue("wallLength")+1;
            if ($nextWall > 12) {
                throw new BgaUserException( self::_("No more wall sections can be built"));
            }
            $tiles = $this->getAvailableWallTiles($player_id);
            if (count($tiles) == 0) {
                throw new BgaUserException( self::_("You have no more wall sections to build"));
            }
            shuffle($tiles);
            $wall = $tiles[0];

            self::DbQuery("UPDATE WALL SET location=$nextWall WHERE id=".$wall['id']);
            self::incGameStateValue("wallLength", 1);
            self::notifyAllPlayers("wallBuilt", clienttranslate('${player_name} builds Great Wall section and receives ${reward} bonus'), array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => $player_id,
                'length' => $nextWall,
                'bonus' => $wall['bonus'],
                'reward' => $this->wall_tiles[$wall['bonus']]['name']
            ));
            $tobuild = $this->assignWallBonus($wall);
            if ($tobuild) {
                $nextState = 'buildAction';
            }
        }
        
        // Next player (unless building)
        $this->gamestate->nextState( $nextState );
    }
    
    function refillyuan()
    {
        self::checkAction( 'action' );
        
        $player_id = self::getActivePlayerId();
        
        // Get current money
        $money = self::getUniqueValueFromDB( "SELECT player_yuan FROM player WHERE player_id='$player_id'" );
        if( $money < 3 )
        {
            self::DbQuery( "UPDATE player SET player_yuan='3' WHERE player_id='$player_id'" );
        }
        
        // Notify
        self::notifyAllPlayers( 'refillyuan', clienttranslate('${player_name} chooses to bring his personal supply of money up to 3 yuan'), array(
            'i18n' => array( 'action_name' ),
            'player_name' => self::getCurrentPlayerName(),
            'player_id' => $player_id
        ) );            

        $this->gamestate->nextState( 'nextPlayer' );
    }
    
    function argActionPhasePrivilege()
    {
        return array(
            'largePrivilegeCost' => ( $this->getLargePrivilegeCost() )
        );
    }
    
    function choosePrivilege( $bIsLarge )
    {
        self::checkAction( 'choosePrivilege' );

        $player_id = self::getActivePlayerId();
        
        $remainingMoney = self::getUniqueValueFromDB( "SELECT player_yuan FROM player WHERE player_id='$player_id'" ); 
        $largePrivilegeCost = $this->getLargePrivilegeCost();
        $price = ( $bIsLarge ? $largePrivilegeCost : 2 );
        
        if( $remainingMoney < $price )
            throw new BgaUserException( self::_("Not enough money") );
        
        
        if( $bIsLarge )
        {
            $sql = "UPDATE player SET player_favor=player_favor+2,
                    player_yuan=player_yuan-$price
                    WHERE player_id='$player_id' ";
            self::DbQuery( $sql );
            
            $notifyText = clienttranslate( '${player_name} buys a big privilege' );
        }
        else
        {
            $sql = "UPDATE player SET player_favor=player_favor+1,
                    player_yuan=player_yuan-$price
                    WHERE player_id='$player_id' ";
            self::DbQuery( $sql );

            $notifyText = clienttranslate( '${player_name} buys a small privilege' );
        }


        $sql = "UPDATE player SET player_yuan=player_yuan-$price WHERE player_id='$player_id' ";
        
        self::notifyAllPlayers( 'buyPrivilege', $notifyText, array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'nbr' => ( $bIsLarge ? 2 : 1 ),
            'price' => $price
        ) );
        
        $this->gamestate->nextState('nextPlayer');
    }
    
    function buildPalace( $palace_id )
    {
        self::checkAction( 'build' );
        $player_id = self::getActivePlayerId();
        
        $remainingToBuild = self::incGameStateValue( 'toBuild', -1 );
        
        // palace_id must be 0 or an existing palace
        if( $palace_id != 0 )
        {
            $sql = "SELECT palace_size FROM palace WHERE palace_id='$palace_id' AND palace_player='$player_id' ";
            $palace_size = self::getUniqueValueFromDb( $sql );
            
            if( $palace_size === null ) {
                throw new BgaVisibleSystemException( 'This palace ($palace_id) does not exist' );// NOI18N
            }
            
            if( $palace_size == 3 )
                throw new feException( self::_("No palace can have more than 3 floors."), true );
                
            self::DbQuery( "UPDATE palace SET palace_size=palace_size+1 WHERE palace_id='$palace_id' " );

            self::notifyAllPlayers( 'buildPalace', clienttranslate('${player_name} extends a palace'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'palace_id' => $palace_id
            ) );
        }
        else
        {
            // New palace
            $sql = "INSERT INTO palace (palace_player, palace_size) VALUES 
                    ('$player_id', '1' )";
            self::DbQuery( $sql );
            $palace_id = self::DbGetLastId();
            
            self::notifyAllPlayers( 'newPalace', clienttranslate('${player_name} builds a new palace'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'palace_id' => $palace_id
            ) );
        }

        if( $remainingToBuild == 0 ) {
            $state = self::getGameStateValue(SUPER_EVENT_ACTION) == 1 ? "charter" : "nextPlayer";
            $this->gamestate->nextState( $state );
        } else {
            $this->gamestate->nextState( 'buildAgain' );
        }
    }

    /**
     * Player action to reduce a palace
     */
    function reduce($palace_id) {
        self::checkAction( 'reduce' );
        $player_id = self::getActivePlayerId();

        $sql = "SELECT palace_size FROM palace WHERE palace_id='$palace_id' AND palace_player='$player_id' ";
        $palace_size = self::getUniqueValueFromDb( $sql );
        if( $palace_size === null ) {
            throw new BgaVisibleSystemException( 'This palace ($palace_id) does not exist' );// NOI18N
        }
        $palace_size--;

        // putting this first to have the reduce palace log message appear before the people being removed
        self::notifyAllPlayers( 'reducePalaceMsg', clienttranslate('${player_name} reduces a palace section'), array(
            'player_name' => self::getActivePlayerName(),
        ) );

        if ($palace_size == 0) {
            // remove all people from this palace
            $removedPersons = self::getObjectListFromDB("SELECT palace_person_id FROM palace_person WHERE palace_person_palace_id='$palace_id' ", true);
            foreach ($removedPersons as $person_id) {
                self::doRelease( $person_id, false );
            }
            // then delete palace
            self::DbQuery( "DELETE FROM palace WHERE palace_id='$palace_id' " );
        } else {
            self::DbQuery( "UPDATE palace SET palace_size=$palace_size WHERE palace_id='$palace_id' " );
        }

        self::notifyAllPlayers( 'reducePalace', '', array(
            'reduce' => $palace_id,
            'size' => $palace_size
        ) );

        $remainingToReduce = self::incGameStateValue( 'toReduce', -1 );
        if ($remainingToReduce == 0) {
            // check for palaces that no longer have enough spaces
            if ($this->markOverPopulatedPalaces($player_id)) {
                $this->gamestate->nextState( 'releasePerson' );
            } else {
                $this->gamestate->nextState( 'nextPlayer' );
            }
        } else {
            $this->gamestate->nextState( 'nextReduce' );
        }
    }

    /**
     * We're going to use the existing drought_affected flag to indicate overfilled palaces.
     * Set toRelease value.
     * Return true if there are any overfilled palaces belonging to this player.
     */
    function markOverPopulatedPalaces($player_id) {
        $overFilled = false;
        $palaces = self::getCollectionFromDB("SELECT palace_id, palace_size FROM palace WHERE palace_player='$player_id' ", true);
        foreach ($palaces as $palace_id => $size) {
            $persons = self::getObjectListFromDB("SELECT palace_person_id FROM palace_person WHERE palace_person_palace_id=$palace_id", true);
            $diff = count($persons) - $size;
            if ($diff > 0) {
                self::incGameStateValue('toRelease', $diff);
                self::DbQuery("UPDATE palace SET palace_drought_affected=$diff WHERE palace_id='$palace_id'");
                $overFilled = true;
            }
        }
        return $overFilled;
    }

    /**
     * Release a person.
     */
    function release( $person_id )
    {
        self::checkAction( 'release' );
        self::doRelease( $person_id, false );
        
        $toRelease = self::incGameStateValue( 'toRelease', -1 );
        if( $toRelease > 0 )
            $this->gamestate->nextState( 'continueRelease' );
        else    
            $this->gamestate->nextState( 'endRelease' ); 
    }

    /**
     * Actual release action.
     */
    function doRelease( $person_id, $bAndReplace=false )
    {
        $player_id = self::getActivePlayerId();
        
        $month = self::getGameStateValue( 'month' );
        $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
        $bDrought = ( $event==3 );
        
        // Check if this person really exists and belong to this player
        $sql = "SELECT palace_player player, palace_person_type type, palace_person_level level, palace_id, palace_drought_affected drought_affected FROM palace
                INNER JOIN palace_person ON palace_person_palace_id=palace_id
                WHERE palace_person_id='$person_id' ";
        $person = self::getObjectFromDB( $sql );
        
        if( $person === null ) {
            throw new BgaVisibleSystemException( 'This person does not exist' ); // NOI18N
        }
        
        if( $person['player'] != $player_id ) {
            throw new BgaUserException( self::_("This person is not one of yours") );
        }

        if( !$bAndReplace && $bDrought && $person['drought_affected'] ) {
            throw new BgaUserException( self::_("You already released a person from this palace (see: Drought)") );
        }
        
        // Okay, let's release this one
        self::DbQuery( "DELETE FROM palace_person WHERE palace_person_id='$person_id' " );
        
        $palace_id = $person['palace_id'];
        if( $bDrought )
            self::DbQuery( "UPDATE palace SET palace_drought_affected='1' WHERE palace_id='$palace_id'" );
        
        // Notify
        $tile_persontype = $this->person_types[ $person['type'] ];
        $i18n = array( 'person_type_name' );
        $details = '';
        if( count( $tile_persontype['subtype'] ) > 1 )
        {
            $i18n[] = 'details';
            if( $person['level'] == 1 ) {
                $details = ' ('.clienttranslate('young').')';
            } else {
                $details = ' ('.clienttranslate('old').')';
            }
        }
       
        self::notifyAllPlayers( 'release', clienttranslate('${player_name} releases a ${person_type_name}${details}'), array(
            'i18n' => $i18n,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'person_type_name' => $tile_persontype['name'],
            'details' => $details,
            'person_id' => $person_id
        ) );
        
        
        if( $bAndReplace )
        {
            self::place( $palace_id );
        }
    }

    /**
     * Action for replacing one person with another.
     */
    function releaseReplace( $person_id )
    {
        self::checkAction( 'releaseReplace' );
        self::doRelease( $person_id, true );
    }
    
    function noReplace()
    {
        self::checkAction( "releaseReplace" );
        
        // Release to tile "to recruit" and change nothing else
        
        self::notifyAllPlayers( 'releaseNoReplace', clienttranslate('${player_name} releases the person recruited'), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName()
        ) );
        $state = self::getGameStateValue(SUPER_EVENT_ACTION) == 1 ? "sunrise" : "nextPhase";
        $this->gamestate->nextState( $state );
    }

    /**
     * Release person but for Earthquakes.
     */
     function depopulate( $person_id ) {
        self::checkAction( 'depopulate' );

        $this->doDepopulate($person_id);
        
        $toRelease = self::incGameStateValue( 'toRelease', -1 );
        if( $toRelease > 0 ) {
            $this->gamestate->nextState( 'continueRelease' );
        } else {
            $this->gamestate->nextState( 'endRelease' );
        }
    }

    /**
     * Releasing a person as a result of Earthquake.
     * palace_drought_affected is used to record how many people must be removed.
     */
    function doDepopulate($person_id) {
        $player_id = self::getActivePlayerId();
        
        // Check if this person really exists and belong to this player
        $sql = "SELECT palace_player player, palace_person_type type, palace_person_level level, palace_id, palace_drought_affected overpop FROM palace
                INNER JOIN palace_person ON palace_person_palace_id=palace_id
                WHERE palace_person_id='$person_id' ";
        $person = self::getObjectFromDB( $sql );
        
        if( $person === null ) {
            throw new BgaVisibleSystemException( 'This person does not exist' );// NOI18N
        }
        
        if( $person['player'] != $player_id ) {
            throw new BgaVisibleSystemException( 'This person is not one of yours' );// NOI18N
        }

        if ($person['overpop'] == 0) {
            throw new BgaUserException(self::_("You must release a person from a palace without enough levels"));            
        }

        // is this a person in an overpopulated palace?
        $palace_id = $person['palace_id'];

        // we can release this person
        self::DbQuery( "DELETE FROM palace_person WHERE palace_person_id='$person_id' " );
        // and mark palace_drought_affected
        self::DbQuery( "UPDATE palace SET palace_drought_affected=palace_drought_affected-1 WHERE palace_id='$palace_id'" );

        // Notify
        $tile_persontype = $this->person_types[ $person['type'] ];
        $i18n = array( 'person_type_name' );
        $details = '';
        if( count( $tile_persontype['subtype'] ) > 1 )
        {
            $i18n[] = 'details';
            if( $person['level'] == 1 )
                $details = ' ('.clienttranslate('young').')';
            else
                $details = ' ('.clienttranslate('old').')';
        }
       
        self::notifyAllPlayers( 'release', clienttranslate('${player_name} releases a ${person_type_name}${details}'), array(
            'i18n' => $i18n,
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'person_type_name' => $tile_persontype['name'],
            'details' => $details,
            'person_id' => $person_id
        ) );
    }

    /**
     *From Flood super event. Player action.
     */
     function removeResources($rice, $fireworks, $yuan) {
        self::checkAction('removeResources');
        $player_id = self::getActivePlayerId();
        // do sanity check
        $resources = self::getObjectFromDB( "SELECT player_rice rice, player_fireworks fireworks, player_yuan yuan FROM player WHERE player_id='$player_id' " );
        if ($resources['rice'] < $rice) {
            throw new BgaVisibleSystemException("Cannot remove more rice than available");// NOI18N
        }
        if ($resources['fireworks'] < $fireworks) {
            throw new BgaVisibleSystemException("Cannot remove more fireworks than available");// NOI18N
        }
        if ($resources['yuan'] < $yuan) {
            throw new BgaVisibleSystemException("Cannot remove more yuan than available");// NOI18N
        }
        self::DbQuery( "UPDATE player SET player_rice=player_rice-$rice, player_fireworks=player_fireworks-$fireworks, player_yuan=player_yuan-$yuan WHERE player_id='$player_id' " );

        self::notifyAllPlayers( 'loseResources', clienttranslate( '${player_name} loses ${nbrrice} rice, ${nbrfw} fireworks, and ${nbryuan} yuan to Flood' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'nbrrice' => $rice,
            'nbrfw' => $fireworks,
            'nbryuan' => $yuan
        ) );
        
        $this->gamestate->nextState('nextPlayer');
    }

    /**
     * From Tornado super event.
     * {int} $pid
     */
     function discard($pid) {
        self::checkAction( 'discard' );
        $player_id = self::getActivePlayerId();
        $pp = self::getUniqueValueFromDB("SELECT personcard_type FROM personcard WHERE personcard_id=$pid AND personcard_player='$player_id'" );
        if ($pp == null) {
            throw new BgaVisibleSystemException("You do not have this person card in hand: $pid");
        }
        // Remove this personcard
        self::DbQuery( "DELETE FROM personcard WHERE personcard_id='$pid' " );
            
        // Notify this player
        self::notifyPlayer( $player_id, 'usePersonCard','',  array(
            'personcard_id' => $pid
        ) );
        $person = ($pp == 0) ? "Wild" : $this->person_types[$pp]['name'];
        self::notifyAllPlayers( 'discardCard', clienttranslate( '${player_name} discards ${persontype} card' ), array(
            'player_name' => self::getCurrentPlayerName(),
            'persontype' => $person
        ) );

        $toReduce = self::incGameStateValue('toReduce', -1);
        if ($toReduce == 0) {
            $this->gamestate->nextState('endDiscard');
        } else {
            $this->gamestate->nextState('continueDiscard');
        }
    }

    /**
     * From Charter super event.
     */
     function charter($type) {
        self::checkAction( 'charter' );
        $player_id = self::getActivePlayerId();
    
        self::notifyAllPlayers( 'charterPerson', clienttranslate( '${player_name} charters ${persontype}' ), array(
            'player_name' => self::getCurrentPlayerName(),
            'persontype' => $this->person_types[$type]['name']
        ) );

        $nextState = 'nextPlayer';
   
        switch($type) {
            case 1:
                // Craftsmen
                $toBuild = self::countItemsByType( $player_id, $type );
                self::setGameStateValue( 'toBuild', $toBuild );
                $nextState = 'buildAction';
                break;
            case 2:
                // Court Ladies
                $vp = self::countItemsByType( $player_id, $type );
                $this->addVictoryPoints($player_id, $vp);
                self::incStat( $vp, 'points_court_ladies', $player_id );
                break;
            case 3:
                // Pyrotechnists
                $fw = self::countItemsByType( $player_id, $type );
                $this->addFireworks($player_id, $fw);
                break;
            case 4:
                // Tax Collectors
                $yuan = self::countItemsByType( $player_id, $type );
                $this->addYuan($player_id, $yuan);
                break;
            case 5:
                // Warriors
                $pp = self::countItemsByType( $player_id, $type );
                $this->addPersonPoints($player_id, $pp);
                break;
            case 6:
                // Monks
                $this->scoreMonks($player_id);
                break;
            case 7:
                // Healers - do nothing
                break;
            case 8:
                // Farmers
                $rice = self::countItemsByType( $player_id, $type );
                $this->addRice($player_id, $rice);
                break;
            case 9:
                // Scholars
                $books = self::countItemsByType( $player_id, $type );
                $this->addVictoryPoints($player_id, $books);
                self::incStat( $books, 'points_scholars', $player_id );
                break;
            default:
                throw new BgaVisibleSystemException("Invalid person type: $type");
        }

        $this->gamestate->nextState($nextState);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argNbrToRelease()
    {
        return array(
            'nbr' => self::getGameStateValue( 'toRelease' )
        );
    }

    /**
     * For reducing palaces or resources or person cards.
     */
    function argNbrToReduce() {
        return array(
            'nbr' => self::getGameStateValue( 'toReduce' )
        );
    }
    
    function argPlaceTile()
    {
        return array(
            'type' => self::getGameStateValue( 'toPlaceType' ),
            'level' => self::getGameStateValue( 'toPlaceLevel' )
        );
    }
    
    function argActionPhaseBuild()
    {
        return array(
            'toBuild' => self::getGameStateValue( 'toBuild' )
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    function stInitialChoiceNextPlayer()
    {
        // If all players placed 2 persons => go to next phase
        $players = self::loadPlayersBasicInfos();
        
        $players_nbr = count( $players );
        $total_nbr_tiles = self::getUniqueValueFromDB( 'SELECT COUNT( palace_person_id ) FROM palace_person' );
        if( $total_nbr_tiles == $players_nbr*2 )
        {
            $this->gamestate->nextState('startGame');
            return;
        }

        // If current player has placed 2 persons => go to next player
        $player_id = self::getActivePlayerId();
        $player_nbr_tiles = self::getUniqueValueFromDB( "SELECT COUNT( palace_person_id )
                                                        FROM palace_person
                                                        INNER JOIN palace ON palace_id=palace_person_palace_id
                                                        WHERE palace_player='$player_id'" );
        if( $player_nbr_tiles == 2 )
        {
            self::activeNextPlayer();
            $this->gamestate->nextState('nextPlayer');
        }
        else
        {
            // Otherwise: place a person in palace
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stActionPhaseInit()
    {
        // Remove existing actions
        self::DbQuery( "DELETE FROM action WHERE 1" );
        
        // Place action cards randomly
        $actioncards = $this->useGreatWall() ? 8 : 7;

        $actions = range(1, $actioncards);
        $newactions = array();

        shuffle( $actions );
        $action_id = 1;
        $sql = "INSERT INTO action (action_id,action_type) VALUES ";
        $sql_values = array();
        for( $action_id=1; $action_id <= $actioncards; $action_id++ )
        {
            $action = array_shift( $actions );
            $sql_values[] = "('$action_id','$action')";
            $newactions[ $action_id ] = $action;
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
        
        self::notifyAllPlayers( 'newActions', '', array( 'actions' => $newactions ) );
        
        // Reset player actions choices
        self::DbQuery( "UPDATE player SET player_action_choice=NULL " );
                
        // Active first player to play
        self::updatePlayerPlayOrder();
        self::activeFirstPlayerInPlayOrder();
        self::giveExtraTime( self::getActivePlayerId() );            
        
        $this->gamestate->nextState( );
    }
    
    function stActionPhaseNextPlayer()
    {
        // Active next player to play in turn order
        if( self::activeNextPlayerInPlayOrder() )
        {
            self::giveExtraTime( self::getActivePlayerId() );            
            $this->gamestate->nextState( 'nextPlayer' );
        }
        else
        {
            self::updatePlayerPlayOrder();
            self::activeFirstPlayerInPlayOrder();

            self::giveExtraTime( self::getActivePlayerId() );            
            // need to check if there are still person cards, because Tornado might reduce to 0
            $personcards = self::getObjectListFromDB( "SELECT personcard_id FROM personcard", true );
            if (count($personcards) == 0) {
                $this->gamestate->nextState('noRecruit');
            } else {
                $this->gamestate->nextState('endPhase');
            }
        }
    }

    function stPersonPhaseNextPlayer()
    {
        if( self::activeNextPlayerInPlayOrder() ) {
            // Active next player in turn order
            self::giveExtraTime( self::getActivePlayerId() );                        
            $this->gamestate->nextState( 'nextPlayer' );
        } else {
            $this->gamestate->nextState('endPhase');        
        }
     }
     
     function stEventPhase()
     {
        // Apply current event
        $month = self::getGameStateValue( 'month' );
        $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
        
        $event_type = $this->event_types[ $event ];
        
        self::notifyAllPlayers( 'eventDescription', '${event_name}: ${event_description}', array(  // NOI18N
            'i18n' => array( 'event_name', 'event_description' ),
            'event_name' => $event_type['name'],
            'event_description' => $event_type['description']
        ) );
        
        self::updatePlayerPlayOrder();        
        self::activeFirstPlayerInPlayOrder();
        
        // Initial actions to perform
        
        if( $event == 5 ) // Mongol invasion
        {
            // Should get the persons with the fewest number of helmets
            $player_items = self::countItemsByTypeForAll( 5 );
            $minHelmets = 99;
            foreach( $player_items as $player_id => $items )
            {
                $minHelmets = min( $minHelmets, $items );
            }
            self::setGameStateValue( 'lowerHelmet', $minHelmets );
        }
        else if( $event == 4 ) // Dragon festival
        {
            // Get players with the maximum number of fireworks and the second score
            $player_items = self::getCollectionFromDB( "SELECT player_id, player_fireworks fireworks
                                                        FROM player ORDER BY player_fireworks DESC", true );
            $players = self::loadPlayersBasicInfos();
            
            $lastScore = 0;
            $rank = 1;
            foreach( $player_items as $player_id => $fireworks )
            {
                if( $fireworks > 0 )
                {
                    if( $lastScore == 0 )
                        $rank = 1;  // First player met
                    else
                    {                    
                        if( $fireworks==$lastScore )
                        {
                            // We don't change the rank
                        }
                        else
                            $rank ++;
                    }
                        
                    if( $rank == 1 )
                    {
                        // First player
                        self::DbQuery( "UPDATE player SET player_score=player_score+6, player_fireworks=FLOOR(player_fireworks/2)
                                        WHERE player_id='$player_id' " );
                                        
                        self::notifyAllPlayers( "gainPointFireworks", clienttranslate('${player_name} has the most fireworks and gets ${nbr} points'), array(
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => 6 ) );
                        self::incStat( 6, 'points_fireworks', $player_id );
                    }
                    else if( $rank == 2 )
                    {
                        // Second (3 points)
                        self::DbQuery( "UPDATE player SET player_score=player_score+3, player_fireworks=FLOOR(player_fireworks/2)
                                        WHERE player_id='$player_id' " );
                                        
                        self::notifyAllPlayers( "gainPointFireworks", clienttranslate('${player_name} has the second most fireworks and gets ${nbr} points'), array(
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => 3 ) );
                        self::incStat( 3, 'points_fireworks', $player_id );
                    }
                    
                    $lastScore = $fireworks;
                }
            }            
        }
        
        // Check situation, player by player        
        $this->gamestate->nextState( 'releaseRound' );
  
    }
    
    function stEventPhaseApplyConsequences()
    {
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();

        $month = self::getGameStateValue( 'month' );
        $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
        
        $event_type = $this->event_types[ $event ];
    
        $toRelease = 0;
    
        if( $event == 1 )   // Peace
        {
            // Nothing to do
        }
        else if( $event == 2 )  // Imperial Tribute
        {
            // 4 yuans per player
            $money = self::getUniqueValueFromDB( "SELECT player_yuan FROM player WHERE player_id='$player_id' " );
            if( $money >= 4 )
            {
                // No problem, just reduce the money
                self::DbQuery( "UPDATE player SET player_yuan=player_yuan-4 WHERE player_id='$player_id' " );
                self::notifyAllPlayers( 'eventPayYuan', clienttranslate( '${player_name} pays 4 yuans to the Emperor' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => 4
                ) );
                $toRelease = 0;
            }
            else
            {
                if( $money > 0 )
                {
                    // Money => 0
                    self::DbQuery( "UPDATE player SET player_yuan=0 WHERE player_id='$player_id' " );
                }
                
                $toRelease = 4-$money;

                self::notifyAllPlayers( 'eventPayYuan', clienttranslate( '${player_name} pays ${nbr} yuans to the Emperor and must release ${nbrperson} person(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => $money,
                    'nbrperson' => $toRelease
                ) );
            }
        }
        else if( $event == 3 )  // Drought
        {
            // Drought: 1 rive per palace with at least 1 person
            
            // Count palaces
            $palacesCount = self::getUniqueValueFromDB( "SELECT COUNT( DISTINCT palace_id ) FROM palace
                                                         INNER JOIN palace_person ON palace_person_palace_id=palace_id
                                                         WHERE palace_player='$player_id' " );

            // Count rices
            $rices = self::getUniqueValueFromDB( "SELECT player_rice FROM player WHERE player_id='$player_id' " );
            
            if( $rices >= $palacesCount )
            {
                // No problem, just reduce the rice
                self::DbQuery( "UPDATE player SET player_rice=player_rice-$palacesCount WHERE player_id='$player_id' " );
                self::notifyAllPlayers( 'eventPayRice', clienttranslate( '${player_name} spends ${nbr} rice to feed ${nbr} palaces' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => $palacesCount
                ) );
                $toRelease = 0;
            }
            else
            {
                if( $rices > 0 )
                {
                    // Rice => 0
                    self::DbQuery( "UPDATE player SET player_rice=0 WHERE player_id='$player_id' " );
                }
                
                $toRelease = $palacesCount-$rices;

                self::notifyAllPlayers( 'eventPayRice', clienttranslate( '${player_name} spends ${nbr} rice to feed ${nbr} palaces and must release ${nbrperson} person(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => $rices,
                    'nbrperson' => $toRelease
                ) );
                
                self::DbQuery( "UPDATE palace SET palace_drought_affected='0' " );  // All palaces are reset for this one
            }
        }
        else if( $event == 5 )  // Mongol Invasion
        {
            // +X points where X is the number of helmet (=warriors=5)
            $items = self::countItemsByType( $player_id, 5 );
    
            if( $items > 0 )
            {
                self::DbQuery( "UPDATE player SET player_score=player_score+$items WHERE player_id='$player_id' " );
                self::notifyAllPlayers( 'gainPoint', clienttranslate( 'Mongol Invasion: ${player_name} has ${nbr} helmet(s) and gets ${nbr} point(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'nbr' => $items
                ) );
                self::incStat( $items, 'points_mongol', $player_id );
            }
            
            if( $items == self::getGameStateValue( 'lowerHelmet' ) )
            {
                $toRelease = 1;
                self::notifyAllPlayers( 'eventRelease', clienttranslate( '${player_name} has the lowest number of helmets (${minHelmets}) and must release ${toRelease} person(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'minHelmets' => $items,
                    'toRelease' => $toRelease
                ) );
            }
            else {
                $toRelease = 0;
            }
        }
        else if( $event == 6 )  // Contagion
        {
            // Release 3 persons minus number of healers
            // Get all healers (7)
            $items = self::countItemsByType( $player_id, 7 );
            $toRelease = max( 0, 3-$items );

            if( $toRelease > 0 )
            {
                self::notifyAllPlayers( 'eventRelease', clienttranslate( '${player_name} has only ${items} mortars and must release ${toRelease} person(s)' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name'],
                    'items' => $items,
                    'toRelease' => $toRelease
                ) );
            }            
        }      
        
        if( $toRelease == 0 )
        {
            // Jump to next player
            $this->gamestate->nextState( 'noRelease' );
        }
        else
        {
            self::incStat( $toRelease, 'person_lost_events_allplayers'  );
            self::incStat( $toRelease, 'person_lost_events' , $player_id );
        
            self::setGameStateValue( 'toRelease', $toRelease );
            $this->gamestate->nextState( 'releasePerson' );
        }
    }
    
    function stPersonPhaseChoosePerson()
    {
        if( self::getGameStateValue( 'month' )  == 12 )
        {
            // Last month: skip this phase
            $this->gamestate->nextState( 'notPossible' );
            return;
        }
            
    
        // Check that at least 1 person can be recruited by player with his card. Otherwise, discard a card and jump to next player
        $player_id = self::getActivePlayerId();
        
        // If player has some joker card => no problem
        $personcard_id = self::getUniqueValueFromDB( "SELECT personcard_id FROM personcard WHERE personcard_type='0' AND personcard_player='$player_id' LIMIT 0,1" );

        if( $personcard_id !== null )
            return; // No problem ! There is always a possibility to recruit
        
        // Check, for each card in hand, if there are some tile available
        $possiblecards = self::getObjectListFromDB( "SELECT personcard_id, personpool_nbr
                                    FROM personcard
                                    INNER JOIN personpool ON personpool_type=personcard_type
                                    WHERE personcard_player='$player_id' AND personpool_nbr>0 " );
                                    
        if( count( $possiblecards ) == 0 )
        {
            // No card no play => discard one !
            $personcard_id = self::getUniqueValueFromDB( "SELECT personcard_id FROM personcard WHERE personcard_player='$player_id' LIMIT 0,1" );

            // Remove this personcard
            $sql = "DELETE FROM personcard WHERE personcard_id='$personcard_id' ";
            self::DbQuery( $sql );
            
            // Notify this player
            self::notifyPlayer( $player_id, 'usePersonCard','',  array(
                'personcard_id' => $personcard_id
            ) );

            self::notifyAllPlayers( 'noPersonCardToPlay', clienttranslate('${player_name} cannot recruit considering his remaining cards'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ) );
               
            $this->gamestate->nextState( 'notPossible' );
        }
    }

    function stEventPhaseNextPlayer()
    {
        // Done ! => next player
        if( self::activeNextPlayerInPlayOrder() ) {
            $this->gamestate->nextState( 'nextPlayer' );
        } else if ($this->isGreatWallEvent()) {
            $this->gamestate->nextState( "greatWall" );
        } else {
            $this->gamestate->nextState( 'endPhase' );
        }
    }

    /**
     * Check if player has at least 1 person left to release
      * returns number of people left in palaces
     */
    function nbrPersonsLeft($player_id) {
        $count = self::getUniqueValueFromDB( "SELECT COUNT( palace_person_id )
                                              FROM palace_person
                                              INNER JOIN palace ON palace_id=palace_person_palace_id
                                              WHERE palace_player='$player_id' " );
        return $count;
    }

    /**
     * Check whether player still has people left in Palaces, so we won't
     * try to go below 0.
     * Return true if still 1 or more person lefts, otherwise false.
     */
    function hasPersonsLeft() {
        $player_id = self::getActivePlayerId();
        $count = $this->nbrPersonsLeft($player_id);
        return ($count > 0);
    }

    /**
     * Default release state.
     */
    function stRelease() {
        if (!$this->hasPersonsLeft()) {
            $this->gamestate->nextState( 'endRelease' );
        }
    }
    
    /**
     * Done during Mongol Invasion and at game end.
     */
     function stGreatWall() {
        $players = self::loadPlayersBasicInfos();
        if (self::getGameStateValue("wallLength") < self::getGameStateValue("month")) {
            self::notifyAllPlayers( 'greatWallEvent', clienttranslate('The Great Wall is not long enough; player(s) with fewest wall sections built must lose 1 person'), array() );

            // find the lowest number of walls
            $min = 12;
            foreach( $players as $pid => $player ) {
                $wb = $this->countWallTilesBuilt($pid);
                $min = min($min, $wb);
            }
            self::updatePlayerPlayOrder();
            self::activeFirstPlayerInPlayOrder();
            self::setGameStateValue('minWalls', $min);
            self::setGameStateValue(SUPER_EVENT_FIRST_PLAYER, 1);
            $this->gamestate->nextState('losePerson');
        } else {
            // get points per wall
            self::notifyAllPlayers( 'greatWallEvent', clienttranslate('The Great Wall reaches the current event; players score 1 point per wall section built'), array() );
            foreach( $players as $pid => $player ) {
                $vp = $this->countWallTilesBuilt($pid);
                $this->addVictoryPoints($pid, $vp);
            }
            $this->gamestate->nextState('endPhase');
        }
    }

    /**
     * For checking next player in a Super Event. If SUPER_EVENT_FIRST_PLAYER has been initialized it, unflag it.
     * If first player, or there is another player in player order, return true to continue.
     * If all players have gone, return false.
     */
    function rotatePlayerSuperEvent() {
        $continue = true;
        if (self::getGameStateValue(SUPER_EVENT_FIRST_PLAYER) == 1) {
            self::setGameStateValue(SUPER_EVENT_FIRST_PLAYER, 0);
        } else {
            // means we need to transition to next player to check
            $continue = self::activeNextPlayerInPlayOrder();
        }
        return $continue;
    }

    /**
     * Choose whether this player needs to lose someone.
     */
    function stGreatWallNext() {
        $continue = $this->rotatePlayerSuperEvent();

        if ( $continue ) {
            $player_id = self::getActivePlayerId();
            $wb = $this->countWallTilesBuilt($player_id);
            if ($wb == self::getGameStateValue('minWalls')) {
                self::incStat( 1, 'person_lost_events_allplayers'  );
                self::incStat( 1, 'person_lost_events' , $player_id );
                self::setGameStateValue( 'toRelease', 1 );
                $this->gamestate->nextState( 'releasePerson' );
            } else {
                $this->gamestate->nextState( 'nextPlayer' );
            }
        } else {
            $this->gamestate->nextState( 'endPhase' );
        }
    }

    /**
     * Players with fewest walls lose a person.
     * Same method as stRelease, but differs in invocation from states.php.
     */
    function stGreatWallRelease() {
        if (!$this->hasPersonsLeft()) {
            $this->gamestate->nextState( 'endRelease' );
        }
    }

    /**
     * Inserted before endphase, check if we have to do a Super Event before end of turn scoring.
     */
    function stSuperEvent() {
        $state = "endPhase";
        if ($this->isSuperEvent()) {
            if (self::getGameStateValue(SUPER_EVENT_DONE) == 0) {
                self::setGameStateValue(SUPER_EVENT_DONE, 1);
                // do Super Event
                $state = $this->doSuperEvent();
            }
        }
        $this->gamestate->nextState( $state );
    }

    /**
     * Not used for all Super Events, but only those requiring we cycle through all players.
     */
    function stSuperEventInit() {
        self::updatePlayerPlayOrder();        
        self::activeFirstPlayerInPlayOrder();
        self::setGameStateValue(SUPER_EVENT_FIRST_PLAYER, 1);
        $state = "";
        $se = self::getGameStateValue(SUPER_EVENT);
        switch ($se) {
            case 3:
                $state = "earthquake";
                break;
            case 4:
                $state = "flood";
                break;
            case 7:
                $state = "tornado";
                break;
            case 8:
                $state = "sunrise";
                self::setGameStateValue(SUPER_EVENT_ACTION, 1);
                break;
            case 10:
                $state = "charter";
                self::setGameStateValue(SUPER_EVENT_ACTION, 1);
                break;
            default:
                throw new BgaVisibleSystemException ( "Invalid Super Event value: $se" );
        }

        $this->gamestate->nextState($state);
    }

    /**
     * Rotate through players for super event.
     */
    function stSuperEventRotate() {
        $continue = $this->rotatePlayerSuperEvent();

        if ( $continue ) {
            $se = self::getGameStateValue(SUPER_EVENT);
            $state = 'nextPlayer';
            switch ($se) {
                case 3:
                    // earthquake
                    self::setGameStateValue('toReduce', 2);
                    break;
                case 4:
                    // flood
                    $toReduce = $this->countResourcesToReduce();
                    self::setGameStateValue('toReduce', $toReduce);
                    if ($toReduce == 0) {
                        $state = 'skipPlayer';
                    }
                    break;
                case 7:
                    // tornado
                    self::setGameStateValue('toReduce', 2);
                    break;
                case 8:
                    // Sunrise
                    // check for (rare) case where no more young persons available
                    $youngpers = self::getObjectListFromDB( "SELECT personpool_nbr FROM personpool WHERE personpool_level=1", true);
                    $nbr = 0;
                    foreach ($youngpers as $n) {
                        $nbr += $n;
                    }
                    if ($nbr == 0) {
                        $state = 'endPhase';
                    }
                    break;
                case 10:
                    // charter
                    // make sure we actually have persons
                    if (!$this->hasPersonsLeft()) {
                        $state = 'endPhase';
                    }
                    break;
                default:
                    throw new BgaVisibleSystemException ( "Invalid Super Event value: $se" );
            }

            // next player
            $this->gamestate->nextState( $state );
        } else {
            if (self::getGameStateValue(SUPER_EVENT_ACTION) == 1) {
                self::setGameStateValue(SUPER_EVENT_ACTION, 0);
            }
            $this->gamestate->nextState( 'endPhase' );
        }
    }

    function endOfTurnScoring()
    {
        $notification = array( 
            array( 
                array( 'type' => 'header','str' => clienttranslate('Player'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('Palaces'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('Court ladies'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('Privileges'), 'args' => array() ),
                array( 'type' => 'header','str' => clienttranslate('Total points'), 'args' => array() ) 
                ) 
        );
                
        $player_to_scoring = array();
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $player_to_scoring[ $player_id ] = array( 'palace' => 0, 'ladies' => 0, 'privilege' => 0 );
        }
        
        // Palaces
        $sql = "SELECT palace_player player, COUNT(palace_id) cnt
                FROM palace
                GROUP BY palace_player";
        $playerpalace = self::getCollectionFromDB( $sql, true );
        foreach( $playerpalace as $player_id => $palacescore )
        {
            $player_to_scoring[ $player_id ]['palace'] = $palacescore;
            self::incStat( $palacescore, 'points_palace', $player_id );
        }
        
        // Court ladies
        $sql = "SELECT palace_player, COUNT( palace_person_id ) cnt
                FROM palace_person
                INNER JOIN palace ON palace_id=palace_person_palace_id
                WHERE palace_person_type='2' 
                GROUP BY palace_player";
        $playerladies = self::getCollectionFromDB( $sql, true );
        foreach( $playerladies as $player_id => $ladyscore )
        {
            $player_to_scoring[ $player_id ]['ladies'] = $ladyscore;
            self::incStat( $ladyscore, 'points_court_ladies', $player_id );
        }
        
        // Privilege
        $sql = "SELECT player_id, player_favor FROM player";
        $playerprivilege = self::getCollectionFromDB( $sql, true );
        foreach( $playerprivilege as $player_id => $privscore )
        {
            $player_to_scoring[ $player_id ]['privilege'] = $privscore;
            self::incStat( $privscore, 'points_privilege', $player_id );
        }

        // Update players score
        $player_to_score = array();
        foreach( $players as $player_id => $player )
        {
            $score = $player_to_scoring[ $player_id ]['palace'];
            $score += $player_to_scoring[ $player_id ]['ladies'];
            $score += $player_to_scoring[ $player_id ]['privilege'];
            $sql = "UPDATE player SET player_score=player_score+$score WHERE player_id='$player_id' ";
            self::DbQuery( $sql );
            
            $notification[] = array(
                array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $players[ $player_id ]['player_name'] ),
                                 'type' => 'header'
                                ),
                $player_to_scoring[ $player_id ]['palace'],
                $player_to_scoring[ $player_id ]['ladies'],
                $player_to_scoring[ $player_id ]['privilege'],
                array( 'str' => $score.'',
                        'args' => array(),
                        'type'=> 'header' )
            );
            
            $player_to_score[ $player_id ] = $score;
        }
        
        self::notifyAllPlayers( 'endOfTurnScoring', '', array(
            'datagrid' => $notification,
            'player_to_score' => $player_to_score
        ) );    
    }

    /**
     * Return an associated array of player_id => count of persons in palaces
     */
    function countPersons() {
        $sql = "SELECT palace_player, COUNT( palace_person_id ) cnt
                FROM palace_person
                INNER JOIN palace ON palace_id = palace_person_palace_id
                GROUP BY palace_player";
        $playerperson = self::getCollectionFromDB( $sql, true );
        return $playerperson;
    }

    /**
     * Return an associated array of monks per player and palace level
     * palace_player => [palace_size, palace_person_level]
     */
    function countMonks() {
        $sql = "SELECT palace_player, palace_size, palace_person_level level
                FROM palace_person
                INNER JOIN palace ON palace_id=palace_person_palace_id
                WHERE palace_person_type='6'";  // 6 = monks
        $monks = self::getObjectListFromDB( $sql );
        return $monks;
    }

    function finalScoring()
    {
        $notification = array( 
            array( 
                array( 'type' => 'header','str' => clienttranslate('Player'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('2pt / Person'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('Monks'), 'args' => array() ), 
                array( 'type' => 'header','str' => clienttranslate('Remaining items'), 'args' => array() ),
                array( 'type' => 'header','str' => clienttranslate('Total points (final scoring)'), 'args' => array() ) 
                ) 
        );    
        
        $player_to_scoring = array();
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $player_to_scoring[ $player_id ] = array( 'person' => 0, 'monk' => 0, 'money' => 0 );
        }
        
        // Persons (2pt/pers)
        $playerperson = $this->countPersons();
        foreach( $playerperson as $player_id => $personcount )
        {
            $player_to_scoring[ $player_id ]['person'] = 2*$personcount;
            self::incStat( 2*$personcount, 'points_person', $player_id );
        }

        // Monks
        $monks = $this->countMonks();
        foreach( $monks as $m => $monk )        
        {
            $mpid = $monk['palace_player'];
            $points = $monk['level']*$monk['palace_size'];  // Note: 1 buddha on level 1, 2 buddhas on level 2
            $player_to_scoring[ $mpid ]['monk'] += $points;
            self::incStat( $points, 'points_monks', $mpid );
        }
        
        // Money
        $sql = "SELECT player_id, player_yuan, player_rice, player_fireworks FROM player";
        $remaining = self::getCollectionFromDB( $sql );
        foreach( $remaining as $player_id => $remain )
        {
            $money = $remain['player_yuan']+2*$remain['player_rice']+2*$remain['player_fireworks'];
            $points = floor( $money / 3 );
            $player_to_scoring[ $player_id ]['money'] = $points;
            self::incStat( $points, 'points_remaining', $player_id );
        }
        
        // Update players score
        $player_to_score = array();
        foreach( $players as $player_id => $player )
        {
            $score = $player_to_scoring[ $player_id ]['person'];
            $score += $player_to_scoring[ $player_id ]['monk'];
            $score += $player_to_scoring[ $player_id ]['money'];
            $sql = "UPDATE player SET player_score=player_score+$score WHERE player_id='$player_id' ";
            self::DbQuery( $sql );
            
            $notification[] = array(
                array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $players[ $player_id ]['player_name'] ),
                                 'type' => 'header'
                                ),
                $player_to_scoring[ $player_id ]['person'],
                $player_to_scoring[ $player_id ]['monk'],
                $player_to_scoring[ $player_id ]['money'],
                array( 'str' => $score.'',
                        'args' => array(),
                        'type'=> 'header' )
            );
            
            $player_to_score[ $player_id ] = $score;
        }
        
        self::notifyAllPlayers( 'endOfGameScoring', '', array(
            'datagrid' => $notification,
            'player_to_score' => $player_to_score
        ) );            
    }
    
    function stDecayAndScoring()
    {
        // Decay: unoccupied palaces lose a floor, and void palaces are removed
        $sql = "SELECT palace_id, palace_size size, palace_player player, COUNT( palace_person_id ) cnt
                FROM palace
                LEFT JOIN palace_person ON palace_person_palace_id=palace_id
                GROUP BY palace_id
                HAVING cnt='0'";
        $palaces_to_decay = self::getCollectionFromDB( $sql );
        
        $reduce_size_ids = array();
        $destroy_ids = array();
        
        foreach( $palaces_to_decay as $palace_to_decay )
        {
            $palace_id=$palace_to_decay['palace_id'];
            
            if( $palace_to_decay['size'] > 1 )
            {
                $sql = "UPDATE palace SET palace_size=palace_size-1 WHERE palace_id='$palace_id' ";
                self::DbQuery( $sql );
                $reduce_size_ids[] = $palace_id;
            }
            else
            {
                // Palace with size = 1 => remove this palace
                $sql = "DELETE FROM palace WHERE palace_id='$palace_id' ";
                self::DbQuery( $sql );
                $destroy_ids[] = $palace_id;
            }
            
            self::incStat( 1, 'decay', $palace_to_decay['player'] );
        }
        
        $month = self::getGameStateValue( 'month');
        
        // Notify
        self::notifyAllPlayers( "decay", clienttranslate('Decay: Uninhabited palaces are reduced by 1 floor'), array(
            'destroy' => $destroy_ids,
            'reduce' => $reduce_size_ids,
            'month' => $month, // for super events
        ) );

        // Turn scoring: palaces, court ladies and privileges /////////////////////////////////////////
        self::endOfTurnScoring();

        
        /////// => next month ///////////
                
        // If final conditions are met (12th turn):
        if( $month == 12 )
            $this->gamestate->nextState( 'finalScoring' );
        else
        {
            $month = self::incGameStateValue( 'month', 1 );
            self::notifyAllPlayers( 'newMonth', clienttranslate("A new month begins"), array( 'month' => $month ) );
            $this->gamestate->nextState( 'nextTurn' );
        }            
    }

    function stFinalScoring()
    {
        // Final scoring: 
        // 1 person = 2 points
        // monks
        // rice or fireworks = 2 yuans, 1 VP for 3 yuans
        self::finalScoring();
        
        // + auxiliary score = place on person track
        self::updatePlayerPlayOrder();

        self::DbQuery( "UPDATE player SET player_score_aux=-player_play_order " );  // Note: opposite to play order (people 1 in play order has the best aux score)
        
        // Last statistics
        $sql = "SELECT palace_player player, COUNT(palace_id) cnt
                FROM palace
                GROUP BY palace_player";
        $playerpalace = self::getCollectionFromDB( $sql, true );
        foreach( $playerpalace as $player_id => $palacecount )
        {
            self::setStat( $palacecount, 'palace_nbr', $player_id );
        }        
        
        $this->gamestate->nextState();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// End of game management
////////////    
 
    
    protected function getGameRankInfos()
    {
        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();
        
        return $result;
    } 

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn( $state, $active_player )
    {
        if( $state['name'] == 'initialChoice' )
        {
            // Note: if there is a zombie at this state there is a risk of an infinite loop => we don't support this
            throw new feException( "Zombie mode (voluntarly) not supported at this game state:".$state['name'], true );
        }
        else if( $state['name'] == 'initialPlace' )
        {
            $this->gamestate->nextState( 'nextPhase' );
        }
        else if( $state['name'] == 'actionPhaseChoose' )
        {
            $this->gamestate->nextState( 'nextPlayer' );
        }
        else if( $state['name'] == 'actionPhaseBuild' )
        {
            $this->gamestate->nextState( 'nextPlayer'  );
        }
        else if( $state['name'] == 'actionPhasePrivilege' )
        {
            $this->gamestate->nextState( 'nextPlayer' );
        }
        else if( $state['name'] == 'personPhaseChoosePerson' )
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if( $state['name'] == 'personPhasePlace' )
        {
            $state = self::getGameStateValue(SUPER_EVENT_ACTION) == 1 ? 'sunrise' : 'nextPhase';
            $this->gamestate->nextState( $state );
        }
        else if( $state['name'] == 'palaceFull' )
        {
            $state = self::getGameStateValue(SUPER_EVENT_ACTION) == 1 ? 'sunrise' : 'nextPhase';
            $this->gamestate->nextState( $state );
        }
        else if( $state['name'] == 'release' )
        {
            // Get one person from current player, random
            $this->releaseRandomPerson($active_player);
        }
        else if ($state['name'] == 'greatWallRelease')
        {
            $this->releaseRandomPerson($active_player);
        }
        else if ($state['name'] == 'reducePalace')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if ($state['name'] == 'reducePopulation')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if ($state['name'] == 'reduceResources')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if ($state['name'] == 'discardPersonCards')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if ($state['name'] == 'sunriseRecruit')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else if ($state['name'] == 'charterPerson')
        {
            $this->gamestate->nextState( 'zombiePass' );
        }
        else
            throw new feException( "Zombie mode not supported at this game state:".$state['name'] );
    }

    /**
     * For zombie player release random person
     */
    function releaseRandomPerson($active_player) {
            // Get one person from current player, random
            $person_id = self::getUniqueValueFromDB( "SELECT palace_person_id
                                                      FROM palace_person
                                                      INNER JOIN palace ON palace_id=palace_person_palace_id
                                                      WHERE palace_player='$active_player' AND palace_drought_affected=0 LIMIT 0,1" );
            self::release( $person_id );
    }

    function upgradeTableDb( $from_version )
    {
        // Example:
       if( $from_version <= 2105120134 )
       {
           // ! important ! Use DBPREFIX_<table_name> for all tables

           $sql = "ALTER TABLE DBPREFIX_personpool ADD personpool_sunrise BOOLEAN NOT NULL DEFAULT '0'";
           self::applyDbUpgradeToAllDB( $sql );
       }
    }
}