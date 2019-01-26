<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;

class SessionTask extends Task {

    private $plugin;
    private $roomid;

    private $explodetime;
    private $gametime;
    private $countdown = 10;
    
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
                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."房间人数已到达最小可玩人数，进入准备状态", $Session->getPlayers());
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                        $this->plugin->removeRoom($this->roomid);
                    }
                    $this->plugin->getServer()->broadcastTip(TF::YELLOW."等待玩家中...", $Session->getPlayers());
                break;

                case 1:
                    $this->countdown--;
                    $this->plugin->getServer()->broadcastTitle($this->countdown, "", -1, -1, -1, $Session->getPlayers());
                    if($this->countdown == 0) {
                        $Session->setStatus(2);
                        $this->plugin->updateSession($this->roomid, $Session);
                        $this->plugin->getServer()->broadcastTip(TF::YELLOW."游戏开始咯!", $Session->getPlayers());
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                        $this->plugin->removeRoom($this->roomid);
                    }
                break;

                case 2:
                    //TODO
            }
        }
    }
}