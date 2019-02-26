<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;

class PTWTask extends Task {

    private $plugin;
    private $sessionid = 0;

    private $readyCountdown = 10;
    private $switchingtime = 0;
    private $explodetime = 0;
    private $onSwitching = true;
    private $explodeCountdown = false;

    public function __construct(PanThrowWar $plugin, int $sessionid) {
        $this->plugin = $plugin;
        $this->sessionid = $sessionid;
        $this->init();
    }

    private function init() {
        $Session = $this->plugin->getRoomById($this->sessionid);
        if($Session instanceof PTWSession) {
            $this->switchingtime = $Session->getSwitchingTime();
            $this->explodetime = $Session->getExplodeTime();
        }
    }

    public function onRun(int $currentTick) {
        $Session = $this->plugin->getRoomById($this->sessionid);
        if($Session instanceof PTWSession) {
            $Status = $Session->getStatus();
            switch($Status) {

                case 0:
                    $this->plugin->getServer()->broadcastTip(TF::YELLOW."等待玩家中...", $Session->getPlayers());
                    if(count($Session->getPlayers()) >= $Session->getMinPlayer()) {
                        $this->plugin->getServer()->broadcastMessage(TF::YELLOW."房间人数足够啦！正在开始游戏...", $Session->getPlayers());
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
                            $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."将在{$Session->getSwitchingTime()}秒后随机抽取玩家背锅", $Session->getPlayers());
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
                    $this->switchingtime--;
                    if($this->switchingtime == 0) {
                        $this->switchingtime = $Session->getSwitchingTime();
                        if($this->onSwitching) {
                            foreach($Session->getPlayers() as $player) {
                                if($player instanceof Player) {
                                    if(!$Session->isSpectator($player)) {
                                        $player->getArmorInventory()->clearAll();
                                        $player->getArmorInventory()->setHelmet(Item::get(Item::MOB_HEAD, 4));
                                        $player->removeAllEffects();
                                        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 300 * 900, 1, false));
                                        $player->sendMessage($this->plugin->prefix."你背上了沉重的锅，快传给别人吧");
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."{$player->getName()}成功背锅，快跑丫", $Session->getPlayers());
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."锅子还有{$Session->getExplodeTime()}后爆炸", $Session->getPlayers());
                                        $this->explodeCountdown = true;
                                        $this->onSwitching = false;
                                    }
                                    continue;
                                }
                            }
                        }
                    }
                    if($this->explodeCountdown) {
                        $this->explodeCountdown--;
                        if($this->explodeCountdown == 10) {
                            $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."锅子还有10秒后爆炸！", $Session->getPlayers());
                        }
                        if($this->explodeCountdown == 5) {
                            $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."锅子还有5秒后爆炸！", $Session->getPlayers());
                        }
                        if($this->explodetime == 0) {
                            $this->explodetime = $Session->getExplodeTime();
                            foreach($Session->getPlayers() as $player) {
                                if($player instanceof Player) {
                                    if($player->getArmorInventory()->getHelmet() == Item::get(Item::MOB_HEAD, 4)) { //锅子携带者
                                        $Session->setSpectator($player);
                                        $this->plugin->updateSession($Session->getSessionId(), $Session);
                                        $player->sendMessage($this->plugin->prefix."你因背锅而死，通过白色羊毛即可离开房间");
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."{$player->getName()}因背锅而死");
                                        $this->switchingtime = $Session->getSwitchingTime();
                                        $this->explodeCountdown = $Session->getExplodeTime();
                                        $this->onSwitching = true;
                                        $this->explodeCountdown = false;
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."将在{$Session->getSwitchingTime()}秒后随机抽取玩家背锅", $Session->getPlayers());
                                        //TODO: 粒子特效，声效
                                        break;
                                    }
                                }
                            }
                        }
                    }
            }
        }
    }
}