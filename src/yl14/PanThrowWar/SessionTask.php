<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;

class SessionTask extends Task {

    private $plugin;
    private $roomid;

    private $explodetime;
    private $gametime;
    
    public function __construct(PanThrowWar $plugin, int $roomid) {
        $this->plugin = $plugin;
        $this->roomid = $roomid;
        $this->init();
    }

    private function init() {
        $this->explodetime = $this->plugin->getRoomById($this->roomid)->getExplodeTime();
        $this->gametime = $this->plugin->getRoomById($this->roomid)->getGameTime();
    }

    public function onRun(int $tick) {
        $Session = $this->plugin->getRoomById($this->roomid);
        if($Session instanceof PTWSession) {
            $Status = $Session->getStatus();
            switch($Status) {

                case 0:
                    if(count($Session->getPlayers()) >= $Session->getMinPlayer()) {
                        $Session->setStatus(1);
                        $this->plugin->updateSession($this->roomid, $Session);
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->removeRoom($this->roomid);
                    }
                    $this->plugin->getServer()->broadcastTip(TF::YELLOW."等待玩家中...", $Session->getPlayers());
                break;

                case 1:
                    //TODO
            }
        }
    }
}