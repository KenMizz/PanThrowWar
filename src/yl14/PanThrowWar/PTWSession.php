<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\Player;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\item\Item;


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
     * @return int
     */
    public function getExplodeTime() : int {
        return $this->settings['explodetime'];
    }

    /**
     * @return int
     */
    public function getSwitchingTime() : int {
        return $this->settings['switchingtime'];
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
            $exitwool = Item::get(Item::WOOL);
            $exitwool->setCustomName("离开房间");
            $player->removeAllEffects();
            $player->getArmorInventory()->clearAll();
            $player->getInventory()->clearAll();
            $player->setGamemode(1);
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 300 * 900, 1, false));
            $$p->getInventory()->setItem(8, $exitwool);
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
        $players = [];
        foreach($this->players as $player) {
            if(!$player['isSpectator']) {
                $players[] = $player['player'];
            }
        }
        return $players;
    }

    /**
     * @return pocketmine\Player[]
     */
    public function getSpectators() : array {
        $spectators = [];
        foreach($this->players as $player) {
            if($player['isSpectator']) {
                $spectators[] = $player['player'];
            }
        }
        return $spectators;
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