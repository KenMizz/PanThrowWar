<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\Player;


class PTWSession {

    private $status = 0; //0 = waiting, 1 = ready, 2 = started
    
    private $roomid = 0; //int
    private $levelname; //String
    private $waitinglocation = []; //Array
    private $playinglocation = []; //Array
    private $settings = []; //Array

    private $players = []; //Array
    private $spectators = []; //Array

    /**
     * @param PanThrowWar $plugin
     * @param int $roomid
     * @param String $levelname
     * @param Array $waitinglocation
     * @param Array $playinglocation
     * @param Array $settings
     */
    public function __construct(PanThrowWar $plugin, int $roomid, String $levelname, Array $waitinglocation, Array $playinglocation, Array $settings) {
        $this->roomid = $roomid;
        $this->levelname = $levelname;
        $this->waitinglocation = $waitinglocation;
        $this->playinglocation = $playinglocation;
        $this->settings = $settings;
    }

    /**
     * @return int
     */
    public function getRoomId() : int {
        return $roomid;
    }

    /**
     * @return String
     */
    public function getLevelName() : String {
        return $this->levelname;
    }

    /**
     * @return Array
     */
    public function getWaitingLocation() : Array {
        return $this->waitinglocation;
    }

    /**
     * @return Array
     */
    public function getPlayingLocation() : Array {
        return $this->playinglocation;
    }

    /**
     * @return int
     */
    public function getGameTime() : int {
        return $this->settings['gametime'];
    }

    /**
     * @return int
     */
    public function getExplodeTime() : int {
        return $this->settings['explodetime'];
    }

    /**
     * @return int
     */
    public function getMinPlayer() : int {
        return $this->settings['minplayer'];
    }

    /**
     * @return int
     */
    public function getMaxPlayer() : int {
        return $this->settings['maxplayer'];
    }

    /**
     * @param pocketmine\Player $player
     * @return bool
     */
    public function addPlayer(Player $player) : bool {
        if(!isset($this->players[$player->getName()])) {
            $this->players[$player->getName()] = $player;
            if(isset($this->spectators[$player->getName()])) {
                unset($this->spectators[$player->getName()]);
            }
            return true;
        }
        return false;
    }
	

    /**
     * @param pocketmine\Player $player
     * 
     * @return bool
     */
    public function removePlayer(Player $player) : bool {
        if(isset($this->players[$player->getName()])) {
            unset($this->players[$player->getName()]);
            return true;
        }
        return false;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return pocketmine\Player|bool
     */
    public function getPlayer(Player $player) : ?Player {
        if(isset($this->players[$player->getName()])) {
            return $this->players[$player->getName()];
        }
        return false;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getPlayers() : Array {
        return $this->players;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getSpectators() : Array {
        return $this->spectators;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return bool
     */
    public function addSpectator(Player $player) : bool {
        if(!isset($this->spectators[$player->getName()])) {
            $this->spectators[$player->getName()] = $player;
            if(isset($this->players[$player->getName()])) {
                unset($this->players[$player->getName()]);
            }
            return true;
        }
        return false;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return bool
     */
    public function removeSpectator(Player $player) : bool {
        if(isset($this->spectators[$player->getName()])) {
            unset($this->spectators[$player->getName()]);
            return true;
        }
        return false;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return bool
     */
    public function isSpectator(Player $player) : bool {
        if(isset($this->spectators[$player->getName()])) {
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getStatus() : int {
        return $this->status;
    }

    /**
     * @param int $status
     * 
     * @return bool
     */
    public function setStatus(int $status) : bool {
        if($status != 0 or $status !=1 or $status != 2) {
            return false;
        }
        $this->status = $status;
        return true;
    }
}