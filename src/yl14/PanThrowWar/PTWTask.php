<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;

class PTWTask extends Task {

    private $plugin;
    private $sessionid;

    public function __construct(PanThrowWar $plugin, int $sessionid) {
        $this->plugin = $plugin;
        $this->sessionid = $sessionid;
    }

    public function onRun(int $currentTick) {
        $Session = $this->plugin->getRoomById($this->sessionid);
        if($Session instanceof PTWSession) {
            $Status = $Session->getStatus();
            switch($Status) {

                case 0:
                    $this->plugin->getServer()->broadcastTip(TF::YELLOW."等待玩家中...", $Session->getPlayers());
                    if(count($Session->getPlayers()) >= $Session->getMaxPlayer()) {
                        $Session->setStatus(1);
                        $this->plugin->updateSession($Session->getSessionId(), $Session);
                    }
                    if(count($Session->getPlayers()) <= 0) {
                        $this->plugin->closeRoom($Session->getSessionId(), $Session->getTaskId());
                    }
                break;

                case 1:
                    //TODO
                break;

                case 2:
                    //TODO
            }
        }
    }
}