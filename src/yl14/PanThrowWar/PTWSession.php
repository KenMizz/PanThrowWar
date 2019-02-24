<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\Player;


class PTWSession {

    private $status = 0; //0 = waiting 1 = onready 2 = ready
    private $players = [];

    private $sessionid;
    private $levelname;
    private $waitinglocation;
    private $playinglocation;
    private $settings;
    private $taskid;

    public function __construct(int $sessionid, string $levelname, array $waitinglocation, array $playinglocation, array $settings, int $taskid) {
        $this->sessionid = $sessionid;
        $this->levelname = $levelname;
        $this->waitinglocation = $waitinglocation;
        $this->playinglocation = $playinglocation;
        $this->settings = $settings;
        $this->taskid = $taskid;
    }

    /**
     * @return int
     */
    public function getSessionId() : int {
        return $this->sessionid;
    }

    /**
     * @return int
     */
    public function getTaskId() : int {
        return $this->taskid;
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
     * @return bool
     */
    public function addPlayer(Player $player) : bool {
        if(!isset($this->players[$player->getName()])) {
            $this->players[$player->getName()] = array(
                'player' => $player,
                'isSpectator' => false
            );
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
    public function setSpectator(Player $player) : bool {
        if(isset($this->players[$player->getName()])) {
            $this->players[$player->getName()]['isSpectator'] = true;
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
        return $this->players[$player->getName()]['isSpectator'] ?? false;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return pocketmine\Player|bool
     */
    public function getPlayer(Player $player) {
        return $this->players[$player->getName()]['player'] ?? false;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getPlayers() : array {
        return $this->players;
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