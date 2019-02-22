<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\Player;


class PTWSession {

    private $status = 0; //0 = waiting 1 = onready 2 = ready
    private $players = [];
    private $spectators = [];

    private $sessionid;
    private $levelname;
    private $waitinglocation;
    private $playinglocation;
    private $settings;

    public function __construct(int $sessionid, string $levelname, array $waitinglocation, array $playinglocation, array $settings) {
        $this->sessionid = $sessionid;
        $this->levelname = $levelname;
        $this->waitinglocation = $waitinglocation;
        $this->playinglocation = $playinglocation;
        $this->settings = $settings;
    }

    /**
     * @return int
     */
    public function getSessionId() : int {
        return $this->sessionid;
    }

    /**
     * @return string
     */
    public function getLevelName() : string {
        return $this->levelname;
    }

    /**
     * @return array
     */
    public function getWaitingLocation() : array {
        return $this->waitinglocation;
    }

    /**
     * @return array
     */
    public function getPlayingLocation() : array {
        return $this->playinglocation;
    }

    /**
     * @return int
     */
    public function getMaxPlayer() : int {
        return $this->settings['maxplayer'];
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
    public function getGameTime() : int {
        return $this->settings['gametime'];
    }

    /**
     * @return int
     */
    public function getWinMoney() : int {
        return $this->settings['money'];
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return pocketmine\Player|bool
     */
    public function getPlayer(Player $player) : ?Player {
        return $this->players[$player->getName()] ?? false;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getPlayers() : array {
        return $this->players;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return pocketmine\Player|bool
     */
    public function getSpectator(Player $player) : ?Player {
        return $this->spectators[$player->getName()] ?? false;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getSpectators() : array {
        return $this->spectators;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return bool
     */
    public function addPlayer(Player $player) : bool {
        if(!isset($this->players[$player->getName()])) {
            $this->players[$player->getName()] = $player;
            if(isset($this->spectators[$player->getName()])) {
                $this->removeSpectator($player);
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
     * @return bool
     */
    public function addSpectator(Player $player) : bool {
        if(!isset($this->spectators[$player->getName()])) {
            $this->spectators[$player->getName()] = $player;
            if(isset($this->players[$player->getName()])) {
                $this->removePlayer($player);
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
        if($status != 0 or $status != 1 or $status != 2) {
            return false;
        }
        $this->status = $status;
        return true;
    }
}