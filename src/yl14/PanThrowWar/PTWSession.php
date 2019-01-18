<?php

declare(strict_types=1);
namespace yl14\PanThrowWar;

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
}