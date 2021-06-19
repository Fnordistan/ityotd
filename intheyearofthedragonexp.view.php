<?php
 /**
  * intheyearofthedragonexp.view.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * intheyearofthedragonexp main static view construction
  *
  */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_intheyearofthedragonexp_intheyearofthedragonexp extends game_view
  {
    function getGameName() {
        return "intheyearofthedragonexp";
    }    
  	function build_page( $viewArgs )
  	{		
        $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "player" );
        $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "persontile" );
        $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "personcard" );
        $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "actionplace" );


  	    // Get players
        $players = $this->game->loadPlayersBasicInfos();
        self::watch( "players", $players );
        
        $player_nbr = count( $players );    // Note: number of players = number of rows

        global $g_user;

        foreach( $players as $player )
        {
            if( $player['player_id'] == $g_user->get_id() )
            {
                $this->page->insert_block( "player", array( "PLAYER_ID" => $player['player_id'],
                                                            "PLAYER_NAME" => $player['player_name'] ) );
            }
        }

        foreach( $players as $player )
        {
            if( $player['player_id'] != $g_user->get_id() )
            {
                $this->page->insert_block( "player", array( "PLAYER_ID" => $player['player_id'],
                                                            "PLAYER_NAME" => $player['player_name'] ) );
            }
        }
        
        // Events
        $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "event" );
        $events = $this->game->getEvents();
        foreach( $events as $event_id => $event_type )
        {
            $this->page->insert_block( 'event', array(
                'ID' => $event_id,
                'TYPE' => $event_type
            ) );
        }

        if ($this->game->useGreatWall()) {
            $this->page->begin_block( "intheyearofthedragonexp_intheyearofthedragonexp", "wall" );
            for ($w = 1; $w <=12; $w++) {
                $this->page->insert_block( 'wall', array(
                    'ID' => $w,
                ) );
            }
        }

        // Persontiles
        foreach( $this->game->person_types as $type_id => $person_type )
        {
            foreach( $person_type['subtype'] as $level => $subtype )
            {
                $this->page->insert_block( 'persontile', array(
                    'ID' => $type_id.'_'.$level
                ) );
            }
        }
        
        // Personcard
        $cards = $this->game->getPersoncards();
        $second_joker = false;
        foreach( $cards as $card_id => $card )
        {
            $this->page->insert_block( 'personcard', array(
                'ID' => $card_id,
                'TYPE' => $card['type'],
                'SECONDJOKER' => ( $card['type']==0 && $second_joker ) ? 'second_joker' : ''
            ) );
            
            if( $card['type'] == 0 )    // Joker
                $second_joker = true;
        }      
        
        // Actions
        $actiongroups = $this->game->getActionGroups();
        $actionmap = $actiongroups[ count( $players ) ];
        $current_group = 1;
        $actionct = $this->game->useGreatWall() ? 8 : 7;
        for( $i=1; $i<=$actionct; $i++ )
        {
            $space_before = '';
        
            if( $current_group != $actionmap[ $i ] )
            {
                $current_group = $actionmap[ $i ];
                $space_before = '&nbsp;&nbsp;';
            }
        
            $this->page->insert_block( 'actionplace', array(
                'ID' => $i,
                'SPACE' => self::raw( $space_before )
            ) );
        }
  	}
}