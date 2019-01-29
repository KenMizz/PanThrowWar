<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\utils\TextFormat as TF;

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
                        $this->plugin->updateSession($this->roomid, $Session);
                        $this->plugin->getServer()->broadcastTip(TF::YELLOW."游戏开始咯!", $Session->getPlayers());
                        $this->plugin->getServer()->broadcastMessage($this->plugin->preix.TF::YELLOW."将在5秒后抽取随机个人背锅");
                    }
                    if(count($Session->getPlayers() <= 0)) {
                        $this->plugin->getScheduler()->cancelTask($this->getTaskId());
                        $this->plugin->removeRoom($this->roomid);
                    }
                break;

                case 2:
                    $this->gametime--;
                    $this->waittime--;
                    if($this->waittime == 0) {
                        $this->waittime = 5;
                        if($this->onSwitching) {
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
                        } else {
                            $this->explodetime--;
                        }
                    }
                    
            }
        }
    }

    /**
     * @param int $roomid
     * @param pocketmine\Player $player
     */
    private function toSpectator(int $roomid, Player $player) {
        $room = $this->plugin->getRoomById($roomid);
        if($room instanceof PTWSession) {
            $player->removeEffect(Effect::SPEED);
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 20 * 90, 1, true, false));
            $player->setGamemode(1);
            $room->addSpectator($player);
        }
    }
}