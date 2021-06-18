<?php
 /**
  * intheyearofthedragonexp.action.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  * Implementation of Great Wall and Super Events expansions: @David Edelstein <davidedelstein@gmail.com>
  *
  * intheyearofthedragon main action entry point
  *
  */
  
  
  class action_intheyearofthedragonexp extends APP_GameAction
  { 
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "intheyearofthedragonexp_intheyearofthedragonexp";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
    public function recruit()
    {
        self::setAjaxMode();     
        $type = self::getArg( "type", AT_posint, true );
        $level = self::getArg( "level", AT_posint, true );
        $result = $this->game->recruit($type, $level);
        self::ajaxResponse( );
    }
    public function place()
    {
        self::setAjaxMode();     
        $palace_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->place( $palace_id );
        self::ajaxResponse( );
    }    
    public function action()
    {
        self::setAjaxMode();     
        $action_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->action( $action_id );
        self::ajaxResponse( );
    } 
    public function refillyuan()
    {
        self::setAjaxMode();     
        $result = $this->game->refillyuan();
        self::ajaxResponse( );
    }       
    public function choosePrivilege()
    {
        self::setAjaxMode();     
        $isLarge = self::getArg( "large", AT_posint, true );
        $result = $this->game->choosePrivilege( ($isLarge==1) );
        self::ajaxResponse( );
    }        
    public function build()
    {
        self::setAjaxMode();     
        $palace_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->buildPalace( $palace_id );
        self::ajaxResponse( );    
    }

    public function reduce() {
        self::setAjaxMode();
        $palace_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->reduce( $palace_id );
        self::ajaxResponse( );    
    }

    public function discard() {
        self::setAjaxMode();   
        $id = self::getArg( "id", AT_posint, true );
        $result = $this->game->discard( $id );
        self::ajaxResponse( );    
    }

    public function depopulate() {
        self::setAjaxMode();     
        $palace_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->depopulate( $palace_id );
        self::ajaxResponse( );    
    }

    public function removeResources() {
        self::setAjaxMode();     
        $rice = self::getArg( "rice", AT_posint, true );
        $fw = self::getArg( "fireworks", AT_posint, true );
        $yuan = self::getArg( "yuan", AT_posint, true );
        $result = $this->game->removeResources( $rice, $fw, $yuan );
        self::ajaxResponse( );    
    }

    public function release()
    {
        self::setAjaxMode();     
        $person_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->release( $person_id );
        self::ajaxResponse( );    
    }
    public function releaseReplace()
    {
        self::setAjaxMode();     
        $person_id = self::getArg( "id", AT_posint, true );
        $result = $this->game->releaseReplace( $person_id );
        self::ajaxResponse( );    
    }
    public function noReplace()
    {
        self::setAjaxMode();     
        $result = $this->game->noReplace(  );
        self::ajaxResponse( );    
    }
 }
  
?>
