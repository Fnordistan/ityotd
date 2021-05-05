<?php
/**
  * intheyearofthedragonexp.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  * Implementation of Great Wall and Super-Events expansions: @David Edelstein <davidedelstein@gmail.com>
  *
  *
  * intheyearofthedragonexp main game core
  *
*/

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

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
                "wallLength" => 20,
                "largePrivilegeCost" => 100,
                "expansions" => 101
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
        self::setGameStateInitialValue( 'lowerHelmet', 0 );
        self::setGameStateInitialValue( 'wallLength', 0 );

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

        if ($this->isGreatWall()) {
            self::initStat('table', 'walls_built_allplayers', 0);
            self::initStat( 'player', 'walls_built', 0 );
            self::initStat( 'player', 'points_wall', 0 );
            $this->initializeWall();
        }

        self::activeNextPlayer();
    }

    /**
     * Create certificates, 8 for each currency.
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


        if ($this->isGreatWall()) {
            $result['greatWall'] = $this->getWallTiles();
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
    function isGreatWall() {
        $exp = self::getGameStateValue( 'expansions' );
        return $exp == 2 || $exp == 4;
    }

    /**
     * Check whether this is a Mongol Invasion month AND we're using the Great Wall.
     * @returns true if this is a Mongol Invasion and Great Wall is being used
     */
    function isGreatWallEvent() {
        $gw = false;
        if ($this->isGreatWall()) {
            $month = self::getGameStateValue( 'month' );
            $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
            $gw = ($event == 5);
        }
        return $gw;
    }

    /**
     * Get the correct set of action groups.
     */
    function getActionGroups() {
        if ($this->isGreatWall()) {
            return $this->action_to_actiongroup_8;
        } else{
            return $this->action_to_actiongroup_7;
        }
    }

    /**
     * Are we using the Super-Events?
     */
    function isSuperEvents() {
        $exp = self::getGameStateValue( 'expansions' );
        return $exp == 3 || $exp == 4;
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
    
    function activeFirstPlayerInPlayOrder()
    {
        $player_ids = self::getObjectListFromDB( "SELECT player_id FROM player
                                                  ORDER BY player_play_order", true );

        $player_id = array_shift( $player_ids );
        $this->gamestate->changeActivePlayer( $player_id );
    }
    
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

        if( ! $bInitialChoice )
        {
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

            // Remove this personcard
            $sql = "DELETE FROM personcard WHERE personcard_id='$personcard_id' ";
            self::DbQuery( $sql );
            
            // Notify this player
            self::notifyPlayer( $player_id, 'usePersonCard','',  array(
                'personcard_id' => $personcard_id
            ) );
        }
        else
        {        
            // If this is "initial" mode:
            // _ choose only level 1
            // _ choose 2 different persons
            // _ choose a different combination than another player
            
            if( $level != 1 )
                throw new feException( self::_("During initial phase you must recruit younger persons"), true );
                
            $first_type_chosen = self::getUniqueValueFromDB( "SELECT palace_person_type
                                                              FROM palace_person
                                                              INNER JOIN palace ON palace_id=palace_person_id
                                                              WHERE palace_player='$player_id' " );
                                                              
            if( $first_type_chosen !== null && $first_type_chosen==$type )
                throw new feException( self::_( "Your two initial persons must be different" ), true );

            $all_persons = self::getCollectionFromDB( "SELECT palace_person_id id, palace_person_palace_id palace_id, palace_person_type type, palace_person_level level,
                                                       palace_player player
                                                       FROM palace_person
                                                       INNER JOIN palace ON palace_id=palace_person_id
                                                       WHERE palace_player!='$player_id' " );
                                                       
            $players = self::loadPlayersBasicInfos();
            foreach( $players as $opponent_id => $player )
            {
                if( $opponent_id != $player_id )    // Only opponents
                {
                    $nbr_person_in_common = 0;
                
                    foreach( $all_persons as $person )
                    {
                        if( $person['player'] == $opponent_id )
                        {
                            if( $person['type'] == $first_type_chosen || $person['type'] == $type )
                                $nbr_person_in_common++;
                        }
                    }
                    
                    if( $nbr_person_in_common == 2 )
                        throw new feException( sprintf( self::_("You cannot choose this combination of persons because %s choosed the same one"), $player['player_name'] ), true );
                }
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
        $this->gamestate->nextState( '' );
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
            
        self::notifyAllPlayers( 'personPointMsg', clienttranslate( '${player_name} advances ${nbr} spaces on the Person track' ), array(
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
            self::notifyAllPlayers("wallBuilt", '${player_name} builds Great Wall section and receives ${reward} bonus', array(
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
            
            if( $palace_size === null )
                throw new feException( 'This palace does not exists' );
            
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
        
        if( $remainingToBuild == 0 )
            $this->gamestate->nextState( 'nextPlayer' );
        else
            $this->gamestate->nextState( 'buildAgain' );
    }
    
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
        
        if( $person === null )
            throw new feException( 'This person does not exist' );
        
        if( $person['player'] != $player_id )
            throw new feException( self::_("This person is not one of yours"), true );

        if( !$bAndReplace && $bDrought && $person['drought_affected'] )
            throw new feException( self::_("You already release a person from this palace (see: Drought)"), true );
        
        // Okay, let's release this one
        $sql = "DELETE FROM palace_person WHERE palace_person_id='$person_id' ";
        self::DbQuery( $sql );
        
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
        
        
        if( $bAndReplace )
        {
            self::place( $palace_id );
        }
    }
    
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
        
        $this->gamestate->nextState( '' );
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
        $actioncards = $this->isGreatWall() ? 8 : 7;

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
        
            $this->gamestate->nextState('endPhase');
            return;        
        }
    }
    
    function stPersonPhaseNextPlayer()
    {
        // If all players recruit a person => end this phase
        if( self::activeNextPlayerInPlayOrder() )
        {
            // Active next player in turn order
            self::giveExtraTime( self::getActivePlayerId() );                        
            $this->gamestate->nextState( 'nextPlayer' );
        }
        else
        {
            $this->gamestate->nextState('endPhase');        
        }
     }
     
     function stEventPhase()
     {
        // Apply current event
        $month = self::getGameStateValue( 'month' );
        $event = self::getUniqueValueFromDB( "SELECT year_event FROM year WHERE year_id='$month'" );
        
        $event_type = $this->event_types[ $event ];
        
        self::notifyAllPlayers( 'eventDescription', '${event_name}: ${event_description}', array(
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
        if ($this->isGreatWallEvent()) {
            $this->gamestate->nextState( 'greatWall' );
        } else {
            if( self::activeNextPlayerInPlayOrder() ) {
                $this->gamestate->nextState( 'nextPlayer' );
            } else {
                $this->gamestate->nextState( 'endPhase' );
            }
        }
    }
    
    function stRelease()
    {
        // Check if player has at least 1 person left to release
        $player_id = self::getActivePlayerId();
        $count = self::getUniqueValueFromDB( "SELECT COUNT( palace_person_id )
                                              FROM palace_person
                                              INNER JOIN palace ON palace_id=palace_person_palace_id
                                              WHERE palace_player='$player_id' " );
        
        if( $count == 0 ) {
                $this->gamestate->nextState( 'endRelease' );
        }
    }
    
    /**
     * Done during Mongol Invasion and at game end.
     */
     function stGreatWall() {
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();

        if (self::getGameStateValue("wallLength") < self::getGameStateValue("month")) {
            self::notifyAllPlayers( 'greatWallEvent', clienttranslate('The Great Wall is not long enough; player(s) with fewest wall sections built must lose 1 person'), array() );

            // fewest walls loses person
            $wallsBuilt = array();
            $min = 12;
            foreach( $players as $pid => $player ) {
                $wb = $this->countWallTilesBuilt($pid);
                $wallsBuilt[$pid] = $wb;
                $min = min($min, $wb);
            }

            if ($wallsBuilt[$player_id] == $min) {
                self::incStat( 1, 'person_lost_events_allplayers'  );
                self::incStat( 1, 'person_lost_events' , $player_id );
            
                self::setGameStateValue( 'toRelease', 1 );
                $this->gamestate->nextState( 'releasePerson' );
            } else {
                // Jump to next player
                $this->gamestate->nextState( 'noRelease' );
            }
        } else {
            // get points per wall
            self::notifyAllPlayers( 'greatWallEvent', clienttranslate('The Great Wall reaches the current month; players score 1 point per wall section built'), array() );

            foreach( $players as $pid => $player ) {
                $vp = $this->countWallTilesBuilt($pid);
                $this->addVictoryPoints($pid, $vp);
            }
            $this->gamestate->nextState('endPhase');
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
        $sql = "SELECT palace_player, COUNT( palace_person_id ) cnt
                FROM palace_person
                INNER JOIN palace ON palace_id = palace_person_palace_id
                GROUP BY palace_player";
        $playerperson = self::getCollectionFromDB( $sql, true );
        foreach( $playerperson as $player_id => $personcount )
        {
            $player_to_scoring[ $player_id ]['person'] = 2*$personcount;
            self::incStat( 2*$personcount, 'points_person', $player_id );
        }

        // Monks
        $sql = "SELECT palace_player, palace_size, palace_person_level level
                FROM palace_person
                INNER JOIN palace ON palace_id=palace_person_palace_id
                WHERE palace_person_type='6'";  // 6 = monks
        $monks = self::getObjectListFromDB( $sql );
        foreach( $monks as $monk )        
        {
            $points = $monk['level']*$monk['palace_size'];  // Note: 1 buddha on level 1, 2 buddhas on level 2
            $player_to_scoring[ $monk['palace_player'] ]['monk'] += $points;
            self::incStat( $points, 'points_monks', $monk['palace_player'] );
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
        // Decay: innocupied palace loose a floor, and void palace are removed
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
        
        // Notify
        self::notifyAllPlayers( "decay", clienttranslate('Decay: Uninhabited palaces are reduced by 1 floor'), array(
            'destroy' => $destroy_ids,
            'reduce' => $reduce_size_ids
        ) );
        
        // Turn scoring: palaces, court ladies and privileges /////////////////////////////////////////
        self::endOfTurnScoring();

        
        /////// => next month ///////////
                
        $month = self::getGameStateValue( 'month');
        
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

        //  $result = array(   "table" => array( "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),       // game statistics
        //                     "result" => array(
        //                                     array( "rank" => 1,
        //                                            "tie" => false,
        //                                            "score" => 354,
        //                                            "player" => 45,
        //                                            "name" => "Kara Thrace",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),
        //                                     array( "rank" => 2,
        //                                            "tie" => false,
        //                                            "score" => 312,
        //                                            "player" => 46,
        //                                            "name" => "Lee Adama",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) )
        //                                     )
        //              )
        //


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
            $this->gamestate->nextState(  );
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
            $this->gamestate->nextState( '' );
        }
        else if( $state['name'] == 'palaceFull' )
        {
            $this->gamestate->nextState( '' );
        }
        else if( $state['name'] == 'release' )
        {
            // Get one person from current player, random
            $person_id = self::getUniqueValueFromDB( "SELECT palace_person_id
                                                      FROM palace_person
                                                      INNER JOIN palace ON palace_id=palace_person_palace_id
                                                      WHERE palace_player='$active_player' LIMIT 0,1" );
            self::release( $person_id );
        }
        else
            throw new feException( "Zombie mode not supported at this game state:".$state['name'] );
    }
   
   
}
  
?>
