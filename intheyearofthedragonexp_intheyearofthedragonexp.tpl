{OVERALL_GAME_HEADER}

<div id="yearofdragon" class="whiteblock">
    <h3>{LB_THE_YEAR_OF_DRAGON}:</h3>
    <div id="eventrow">
        <!-- BEGIN event -->
        <div id="event_{ID}" class="event eventtype_{TYPE}"></div>
        <!-- END event -->
    </div>
    <div id="great_wall">
        <!-- BEGIN wall -->
        <div id="wall_{ID}" class="ityotd_wall"></div>
        <!-- END wall -->
    </div>
</div>

<div id="actions" class="whiteblock">
    <h3>{LB_ACTIONS}:</h3>
    <!-- BEGIN actionplace -->{SPACE}<div id="actionplace_{ID}" class="actionplace"></div><!-- END actionplace -->
</div>

<div id="persons" class="whiteblock">
    <h3>{LB_AVAILABLE_PERSONS}:</h3>
    <div id="persontiles">
        <!-- BEGIN persontile -->
        <div id="persontile_{ID}" class="persontile persontile_{ID}">
            <div class="persontile_nbr">x<span id="persontile_nbr_{ID}"></span></div>
        </div>
        <!-- END persontile -->
    </div>
    <div id="personcards" class="personcards_container">
        <!-- BEGIN personcard -->
        <div id="personcard_{ID}" class="personcard personcard_{TYPE} {SECONDJOKER}"></div>
        <!-- END personcard -->
    </div>
    <div id="openhands">
        <!-- BEGIN openhand_player -->
        <h1 id="{PLAYER_ID}_name">{PLAYER_NAME}</h1>
        <div id="personcards_{PLAYER_ID}" class="personcards_container">
            <!-- BEGIN openhand_person -->
            <div id="{PLAYER_ID}_personcard_{ID}" class="personcard personcard_{TYPE} {SECONDJOKER}"></div>
            <!-- END openhand_person -->
        </div>
        <!-- END openhand_player -->
    </div>
</div>

<!-- BEGIN player -->
<div id="palace_{PLAYER_ID}" class="whiteblock palaceswrap">
    <h3>{PLAYER_NAME}:</h3>
    <div>
        <div id="palaces_{PLAYER_ID}">
        </div>

    </div>
    <br class="clear"/>
</div>
<!-- END player -->

<script type="text/javascript">

// Templates
var jstpl_player_board = '<div class="boardblock">\
        <div class="yd_icon icon_yuan ttyuan" id="ttyuan${id}"></div><span id="yuannbr_${id}" class="ttyuan">0</span>&nbsp;\
        <div class="yd_icon icon_pers ttpers" id="ttpers${id}"></div><span id="persnbr_${id}" class="ttpers">0</span>\
    </div>\
    <div class="boardblock">\
        <div class="yd_icon icon_rice ttrice" id="ttrice${id}"></div><span id="ricenbr_${id}" class="ttrice">0</span>&nbsp;\
        <div class="yd_icon icon_fw ttfw" id="ttfw${id}"></div><span id="fwnbr_${id}" class="ttfw">0</span>&nbsp;\
        <div class="yd_icon icon_priv ttpriv" id="ttpriv${id}"></div><span id="privnbr_${id}" class="ttpriv">0</span>\
    </div>';

var jstpl_palace = '<div id="palace_${id}" class="palace">\
        <div id="choosepalace_${id}" class="choosepalace"></div>\
        <div id="palacefloor_${id}_3" class="palacefloor palacefloor3"><div class="palaceicon"></div></div>\
        <div id="palacefloor_${id}_2" class="palacefloor palacefloor2"><div class="palaceicon"></div></div>\
        <div id="palacefloor_${id}_1" class="palacefloor palacefloor1"><div class="palaceicon"></div></div>\
        <div class="palacespacer"></div>\
        <div id="palace_persons_${id}" class="palace_persons">\
        </div>\
    </div>';

var jstpl_palace_person = '<div id="palacepersontile_${id}" class="palacepersontile_place">\
        <div id="palacepersontile_${id}_inner" class="persontile persontile_${type}_${level}"></div>\
    </div>';
    
var jstpl_action  = '<div id="actioncard_${type}" class="actioncard actioncard_${type}"></div>';

var jstpl_actionflag  = '<div id="actionflag_${id}" class="actionflag actionflag_${color}"></div>';

// Great Wall tile on player boards
var jstpl_player_great_wall = '<div id="great_wall_${id}"></div>';
var jstpl_player_wall = '<div id="player_wall_${id}_${type}" class="ityotd_wall" style="margin-top: 10px; background-position: ${x}px ${y}px;"></div>';

var jstpl_super_event = '<div id="${id}" class="ityotd_superevent" style="background-position: ${x}px 0px; --scale: ${scale}"></div>';

// a currency icon used as a button, either note or cert
var jstpl_rsrc_btn = '<button id="${type}_${i}_btn" type="button" class="yd_icon icon_${type} tt${type}"></button>';


</script>  

{OVERALL_GAME_FOOTER}
