<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\EffectInstance;
use pocketmine\utils\TextFormat as TF;
use pocketmine\entity\Effect;

class SessionTask extends Task {

    private $plugin;
    private $roomid;

    private $explodetime;
    private $gametime;

    private $countdown = 10;
    private $onexplode = true;
    private $onSwitching = true;
    private $waittime = 5;
    
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
            $this->plugin->getServer()->broadcastTip("你已进入观察者模式...", $Session->getSpectators());
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
                        foreach($Session->getPlayers() as $p) {
                            if($p instanceof Player) {
                                $p->getInventory()->clearAll();
                            }
                        }
                        $this->plugin->updateSession($this->roomid, $Session);
                        $this->plugin->getServer()->broadcastTip(TF::YELLOW."游戏开始咯!", $Session->getPlayers());
                        $this->plugin->getServer()->broadcastMessage($this->plugin->preix.TF::YELLOW."将在5秒后抽取随机个人背锅", $Session->getPlayers());
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                        $this->plugin->removeRoom($this->roomid);
                    }
                break;

                case 2:
                    $this->gametime--;
                    if($this->onSwitching) {
                        $this->waittime--;
                        if($this->waittime == 0) {
                            $this->waittime = 5;
                            $this->explodetime = $Session->getExplodeTime();
                            $this->onSwitching = false;
                            $players = $Session->getPlayers();
                            shuffle($players);
                            foreach($players as $p) {
                                if($p instanceof Player) {
                                    $p->getArmorInventory()->setHelmet(Item::get(Item::AIR));
                                    $p->getArmorInventory()->setHelmet(Item::get(Item::MOB_HEAD, 4));
                                    $p->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 20 * 90, 1, true, false));
                                    $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."恭喜".$p->getName()."成功背锅!");
                                    $this->plugin->getServer()->broadcastMessage($this->plugin->prefix.TF::YELLOW."锅子还有".TF::WHITE.$Session->getExplodeTime().TF::YELLOW."秒后爆炸");
                                    break;
                                }
                            }
                        }
                    } else {
                        $this->explodetime--;
                        if($this->explodetime == 30) {
                            $this->plugin->getServer()->broadcastMessage($this->plugin->prefix.TF::YELLOW."锅子还有".TF::WHITE.$this->explodetime."秒后爆炸", $Session->getPlayers()); 
                        }
                        if($this->explodetime == 10) {
                            $this->plugin->getServer()->broadcastMessage($this->plugin->prefix.TF::YELLOW."锅子还有".TF::WHITE.$this->explodetime."秒后爆炸", $Session->getPlayers()); 
                        }
                        if($this->explodetime <= 5 and $this->explodetime != 0) {
                            $this->plugin->getServer()->broadcastTip((String)$this->explodetime, $Session->getPlayers()); 
                        }
                        if($this->explodetime == 0) {
                            //boom
                            $this->explodetime = $Session->getExplodeTime();
                            foreach($Session->getPlayers() as $p) {
                                if($p instanceof Player) {
                                    if($p->getArmorInventory()->getHelmet() == Item::get(Item::MOB_HEAD, 4)) {
                                        //锅子携带者
                                        $p->getArmorInventory()->setHelmet(Item::get(Item::AIR));
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."{$p->getName()}因背锅而被炸死了...", $Session->getPlayers());
                                        $p->addTitle(TF::GOLD."你已进入观察者模式", TF::RED."通过点击物品栏内红色羊毛即可离开房间");
                                        $this->toSpectator($p);
                                        $this->plugin->getServer()->broadcastMessage($this->plugin->preix.TF::YELLOW."将在5秒后抽取随机个人背锅", $Session->getPlayers());
                                        $this->onSwitching = true;
                                    }
                                }
                            }
                        }
                    }
                    if(count($Session->getPlayers()) == 1) {
                        foreach($Session->getPlayers() as $p) {
                            $p->sendMessage(TF::YELLOW."你赢了!");
                            $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                            $this->plugin->removeRoom($this->roomid);
                        }
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                        $this->plugin->removeRoom($this->roomid);
                    }
            }
        }
    }

    /**
     * @param pocketmine\Player $player
     */
    private function toSpectator(Player $player) {
        $room = $this->plugin->getRoomById($this->roomid);
        if($room instanceof PTWSession) {
            $player->removeEffect(Effect::SPEED);
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 20 * 90, 1, true, false));
            $player->setGamemode(1);
            $player->getInventory()->clearAll();
            $wool = Item::get(Item::WOOL, 14);
            $wool->setCustomName(TF::RED."离开房间");
            $player->getInventory()->addItem($wool);
            $room->addSpectator($player);
        }
    }
}