<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\Player;

class PTWSession {

    private $status = 0; //0: waiting, 1: ready, 2: started

    private $roomid;
    private $levelname;
    private $players = [];
    private $waitinglocation = [];
    private $playinglocation = [];
    private $settings = [];

    public function __construct(PanThrowWar $plugin, int $roomid, String $levelname, Array $waitinglocation, Array $playinglocation, Array $settings) {
        $this->roomid = $roomid;
        $this->levelname = $levelname;
        $this->waitinglocation = $waitinglocation;
        $this->playinglocation = $playinglocation;
        $this->settings = $settings;
    }

    public function getRoomId() : int {
        return $this->roomid;
    }

    public function getLevelName() : String {
        return $this->levelname;
    }

    public function getWaitingLocation() : Array {
        return $this->waitinglocation;
    }

    public function getPlayingLocation() : Array {
        return $this->playinglocation;
    }

    public function getExplodeTime() : int {
        return $this->settings['explodetime'];
    }

    public function getGameTime() : int {
        return $this->settings['gametime'];
    }

    public function getMaxPlayer() : int {
        return $this->settings['maxplayer'];
    }

    public function getMinPlayer() : int {
        return $this->settings['minplayer'];
    }

    public function getPlayers() : Array {
        return $this->players;
    }

    public function getPlayer(Player $player) : ?Player {
        if(isset($this->players[$player->getName()])) {
            return $this->players[$player->getName()];
        }
        return false;
    }

    public function addPlayer(Player $player) : bool {
        if(!isset($this->players[$player->getName()])) {
            $this->players[$player->getName()] = $player;
            return true;
        }
        return false;
    }

    public function removePlayer(Player $player) : bool {
        if(isset($this->players[$player->getName()])) {
            unset($this->players[$player->getName()]);
            return true;
        }
        return false;
    }

    public function getStatus() : int {
        return $this->status;
    }

    public function setStatus(int $status) : bool {
        if(!$status == 0 or !$status == 1 or !$status == 2) {
            return false;
        }
        $this->status = $status;
        return true;
    }
}