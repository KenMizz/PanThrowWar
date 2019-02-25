<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;

class PTWTask extends Task {

    private $plugin;
    private $sessionid;

    private $readyCountdown = 10;

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
                    if(count($Session->getPlayers()) >= $Session->getMinPlayer()) {
                        $this->plugin->getServer()->broadcastMessage(TF::YELLOW."房间人数足够啦！正在开始游戏...");
                        $Session->setStatus(1);
                        $this->plugin->updateSession($Session->getSessionId(), $Session);
                    }
                    if(count($Session->getPlayers()) <= 0) {
                        $this->plugin->closeRoom($Session->getSessionId(), $Session->getTaskId());
                    }
                break;

                case 1:
                    $this->readyCountdown--;
                    switch($this->readyCountdown) {

                        default:
                            $this->plugin->getServer()->broadcastTitle((string)$this->readyCountdown, "", -1, -1, -1, $Session->getPlayers());
                        break;

                        case 5:
                        case 4:
                        case 3:
                        case 2:
                            $this->plugin->getServer()->broadcastTitle(TF::YELLOW.(string)$this->readyCountdown, "", -1, -1, -1, $Session->getPlayers());
                        break;

                        case 1:
                            $this->plugin->getServer()->broadcastTitle(TF::YELLOW."开始游戏!", "", -1, -1, -1, $Session->getPlayers());
                        break;

                        case 0:
                            foreach($Session->getPlayers() as $player) {
                                if($player instanceof Player) {
                                    $player->setXpLevel(0);
                                }
                            }
                            $Session->setStatus(2);
                            $this->plugin->updateSession($Session->getSessionId(), $Session);
                    }
                    foreach($Session->getPlayers() as $player) {
                        if($player instanceof Player) {
                            $player->setXpLevel($this->readyCountdown);
                        }
                    }
                    if(count($Session->getPlayers()) <= 0) {
                        $this->plugin->closeRoom($Session->getSessionId(), $Session->getTaskId());
                    }
                break;

                case 2:
                    
            }
        }
    }
}