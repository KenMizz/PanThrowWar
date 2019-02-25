<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\command\{Command, CommandSender};
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;

use yl13\GameCoreAPI\GameCoreAPI as GCAPI;
use yl13\GameWizard\GameWizard as GW;

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    const LABEL = 'Beta';

    private static $instance;

    private $Sessions = [];
    private $InGame = [];

    public $prefix = TF::GREEN."[丢锅大战]".TF::WHITE;
    private $onset = [];
    private $gameid = 0;

    public function onEnable() : void {
        $this->getLogger()->notice(TF::YELLOW."丢锅大战已启用，初始化插件中...");
        $this->initPlugin();
        $this->getLogger()->notice(TF::GREEN."插件初始化成功");
    }

    public function onLoad() : void {
        self::$instance = $this;
    }

    public function onDisable() : void {
        $this->closeAllRoom();
        $this->getLogger()->warning("丢锅大战已停用");
    }

    private function initPlugin() : void {
        $this->gameid = GCAPI::getInstance()->api->getGameCoreAPI()->registerGame("丢锅大战", "游乐14");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        if(!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }
        if(!is_dir($this->getDataFolder().'rooms')) {
            @mkdir($this->getDataFolder().'rooms');
        }
    }

    /**
     * @return PanThrowWar
     */
    public static function getInstance() : PanThrowWar {
        return self::$instance;
    }

    /**
     * @param int $sessionid
     * 
     * @return PTWSession|bool
     */
    public function getRoomById(int $sessionid) : ?PTWSession {
        return $this->Sessions[$sessionid] ?? false;
    }

    /**
     * @param int $sessionid
     * @param PTWSession $Session
     * 
     * @return bool
     */
    private function updateSession(int $sessionid, PTWSession $Session) : bool {
        if(isset($this->Sessions[$sessionid])) {
            $this->Sessions[$sessionid] = $Session;
            return true;
        }
        return false;
    }

    /**
     * @param pocketmine\Player $player
     * 
     * @return int|bool
     */
    public function getPlayerInGame(Player $player) {
        return $this->InGame[$player->getName()] ?? false;
    }

    /**
     * @param pocketmine\Player[] $players
     * @param array $filter
     * 
     * @return bool
     */
    public function SearchRoom(array $players, array $filter = []) : bool {
        //任何第三方插件的唯一请求必须得是这里
        /**
         * filter
         *  maxplayer
         *  mapname(WIP)
         */
        if(!empty($filter)) {
            if(isset($filter['maxplayer'])) {
                foreach($this->Sessions as $Session) { // 先遍历所有Session看看有没有可用的
                    if($Session instanceof PTWSession) {
                        if($Session->getMaxPlayer() == $filter['maxplayer']) { //确定是其需要的
                            if($Session->getStatus() == 0 or $Session->getStatus() == 1) { //确定是可以加入的状态
                                if(!count($Session->getPlayers()) + count($players) < $Session->getMaxPlayer()) { //确保不会超出可加入人数
                                    $result = $this->joinRoom($players, $Session->getSessionId());
                                    if(!$result) {
                                        continue;
                                    }
                                    return true;
                                }
                                continue;
                            }
                            continue;
                        }
                        continue;
                    }
                    continue;
                }
                //没有任何可用的房间，那么自己创建
                $room = $this->randRoom($filter);
                if($room instanceof Config) {
                    $roomid = $this->randnum(8);
                    $result = $this->createRoom($roomid, $room->get('levelname'), $room->get('waitinglocation'), $room->get('playinglocation'), $room->get('settings'));
                    if($result) {
                        $rj = $this->joinRoom($players, $roomid);
                        if(!$rj) {
                            return false;
                        }
                        return true;
                    }
                    return false;
                }  
                return false;  
            }
        }
        foreach($this->Sessions as $Session) { // 先遍历所有Session看看有没有可用的
            if($Session instanceof PTWSession) {
                if($Session->getStatus() == 0 or $Session->getStatus() == 1) { //确定是可以加入的状态
                    if(!count($Session->getPlayers()) + count($players) < $Session->getMaxPlayer()) { //确保不会超出可加入人数
                        $result = $this->joinRoom($players, $Session->getSessionId());
                        if(!$result) {
                            continue;
                        }
                        return true;
                    }
                    continue;
                }
                continue;
            }
            continue;
        }
        //没有任何可用的房间，那么自己创建
        $room = $this->randRoom();
        if($room instanceof Config) {
            $roomid = $this->randnum(8);
            $result = $this->createRoom($roomid, $room->get('levelname'), $room->get('waitinglocation'), $room->get('playinglocation'), $room->get('settings'));
            if($result) {
                $rj = $this->joinRoom($players, $roomid);
                if(!$rj) {
                    return false;
                }
                return true;
            }
            return false;
        }  
        return false;  
    }

    /**
     * @param pocketmine\Player[] $players
     * @param int $sessionid
     * 
     * @return bool
     */
    private function joinRoom(array $players, int $sessionid) : bool {
        $Session = $this->getRoomById($sessionid);
        if($Session instanceof PTWSession) {
            $waitinglocation = $Session->getWaitingLocation();
            $exitwool = Item::get(Item::WOOL);
            $exitwool->setCustomName("离开房间");
            GCAPI::getInstance()->api->getChatChannelAPI()->addPlayer($this->gameid, (string)$sessionid, $players);
            foreach($players as $p) {
                if($p instanceof Player) {
                    $Session->addPlayer($p);
                    $this->InGame[$p->getName()] = $sessionid;
                    $p->setGamemode(0);
                    $p->getInventory()->clearAll();
                    $p->getArmorInventory()->clearAll();
                    $p->teleport(new Position($waitinglocation['x'], $waitinglocation['y'], $waitinglocation['z'], $this->getServer()->getLevelByName($Session->getLevelName())));
                    $p->getInventory()->setItem(8, $exitwool);
                    $this->updateSession($sessionid, $Session);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param pocketmine\Player[] $players
     * @param int $sessionid
     * @param int $reason 1: 正常退出|强制退出 2:输了 3:赢了
     * 
     * @return bool
     */
    public function leaveRoom(array $players, int $sessionid, int $reason = 1) : bool {
        $Session = $this->getRoomById($sessionid);
        if($Session instanceof PTWSession) {
            foreach($players as $p) {
                if($p instanceof Player) {
                    if($Session->getPlayer($p) instanceof Player) { //确保玩家是在房间内的
                        $Session->removePlayer($p);
                        GCAPI::getInstance()->api->getChatChannelAPI()->removePlayer($this->gameid, (string)$sessionid, [$p]);
                        unset($this->InGame[$p->getName()]);
                        $p->getInventory()->clearAll();
                        $p->getArmorInventory()->clearAll();
                        $p->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                        $p->setGamemode(0);
                        $p->removeAllEffects();
                        GW::GiveCompass($p);
                        GCAPI::getInstance()->api->getChatChannelAPI()->broadcastMessage($this->gameid, (string)$sessionid, "{$p->getName()}".TF::YELLOW."离开了房间");
                        switch($reason) {

                            default:
                                return true;
                            break;

                            case 2:
                                $p->sendMessage(TF::YELLOW."你输了！");
                            break;

                            case 3:
                                $p->sendMessage(TF::YELLOW."你获得了最后的胜利！");
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $sessionid
     * 
     * @return bool
     */
    public function closeRoom(int $sessionid, int $taskid) : bool {
        $Session = $this->getRoomById($sessionid);
        if($Session instanceof PTWSession) {
            $this->leaveRoom($Session->getPlayers(), $sessionid, 1);
            GCAPI::getInstance()->api->getChatChannelAPI()->remove($this->gameid, (string)$sessionid);
            $this->getScheduler()->cancelTask($taskid);
            unset($this->Sessions[$sessionid]);
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    private function closeAllRoom() : void {
        foreach($this->Sessions as $Session) {
            if($Session instanceof PTWSession) {
                $this->closeRoom($Session->getSessionId(), $Session->getTaskId());
            }
        }
    }

    /**
     * @param int $sessionid
     * @param string $levelname
     * @param array $waitinglocation
     * @param array $playinglocation
     * @param array $settings
     * 
     * @return bool
     */
    private function createRoom(int $sessionid, string $levelname, array $waitinglocation, array $playinglocation, array $settings) : bool {
        if(!isset($this->Sessions[$sessionid])) {
            $task = $this->getScheduler()->scheduleRepeatingTask(new PTWTask($this, $sessionid), 20);
            $this->Sessions[$sessionid] = new PTWSession($sessionid, $levelname, $waitinglocation, $playinglocation, $settings, $task->getTaskId());
            GCAPI::getInstance()->api->getChatChannelAPI()->create($this->gameid, (string)$sessionid);
            return true;
        }
        return false;
    }

    /**
     * @param int $digit
     * 
     * @return int
     */
    private function randnum(int $digit) : int {
        $id = null;
        for($i = 0;$i < $digit;$i ++) {
            $id .= mt_rand(0, 9);
        }
        return (int)$id;
    }

    /**
     * @param array $filter not required
     * 
     * @return Config|bool
     */
    private function randRoom($filter = []) {
        $sdir = scandir($this->getDataFolder()."rooms/");
        $rooms = [];
        foreach($sdir as $dir) {
            $pdir = pathinfo($dir);
            if($pdir['extension'] == 'yml') {
                $rooms[] = $pdir['filename'];
            }
        }
        if(!empty($rooms)) {
            if(!empty($filter)) {
                if(isset($filter['maxplayer'])) {
                    $filterrooms = [];
                    foreach($rooms as $room) {
                        $con = new Config($this->getDataFolder()."rooms/{$room}.yml", Config::YAML);
                        $settings = $con->get('settings');
                        if($settings['maxplayer'] == $filter['maxplayer']) { //是玩家想要的
                            $filterrooms[] = $room;
                        }
                        continue;
                    }
                    if(!empty($filterrooms)) {
                        shuffle($filterrooms);
                        return new Config($this->getDataFolder()."rooms/{$filterrooms[0]}.yml", Config::YAML);
                    }
                    return false;
                }
                return false;
            }
            shuffle($rooms);
            return new Config($this->getDataFolder()."rooms/{$rooms[0]}.yml", Config::YAML);
        }
        return false;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
        if($cmd->getName() == 'pw') {
            if(!isset($args[0])) {
                return false;
            }
            switch($args[0]) {

                default:
                    return false;
                break;

                case 'help':
                    $msg = array(
                        TF::AQUA."/-----丢锅大战帮助-----/",
                        TF::WHITE."/pw set 房间名 设置新的房间配置文件",
                        "/pw remove 房间名 移除已有的房间配置文件",
                        "/pw reload 房间名 重新加载已有的房间配置文件",
                        TF::GREEN."当前版本:".TF::WHITE.self::VERSION.self::LABEL,
                    );
                    $sender->sendMessage(implode("\n", $msg));
                    return true;
                break;

                case 'set':
                    if(!isset($args[1])) {
                        return false;
                    }
                    if(!$sender instanceof Player) {
                        return false;
                    }
                    if(isset($this->onset[$sender->getName()])) {
                        $name = $this->onset[$sender->getName()]['name'];
                        unset($this->onset[$sender->getName()]);
                        $sender->sendMessage($this->prefix."已取消房间配置文件{$name}的设置");
                        return true;
                    }
                    $this->onset[$sender->getName()] = array(
                        'name' => $args[1],
                        'levelname' => $sender->getLevel()->getFolderName(),
                        'waitinglocation' => array(
                            'x' => 0,
                            'y' => 0,
                            'z' => 0
                        ),
                        'playinglocation' => array(
                            'x' => 0,
                            'y' => 0,
                            'z' => 0
                        ),
                        'settings' => array(
                            'gametime' => 600,
                            'maxplayer' => 4,
                            'minplayer' => 2,
                            'money' => 10,
                            'explodetime' => 60,
                            'switchingtime' => 5,
                        )
                    );
                    $sender->sendMessage($this->prefix."开始进行丢锅大战房间配置文件的配置，输入/pw w 设置等待位置，/pw p 设置游玩位置，一切就绪后，输入/pw f 完成配置");
                    return true;
                break;

                case 'w':
                    if(!$sender instanceof Player) {
                        return false;
                    }
                    if(!isset($this->onset[$sender->getName()])) {
                        return false;
                    }
                    $this->onset[$sender->getName()]['waitinglocation'] = array(
                        'x' => $sender->x,
                        'y' => $sender->y,
                        'z' => $sender->z,
                    );
                    $sender->sendMessage($this->prefix."设置玩家等待位置成功");
                    return true;
                break;

                case 'p':
                    if(!$sender instanceof Player) {
                        return false;
                    }
                    if(!isset($this->onset[$sender->getName()])) {
                        return false;
                    }
                    $this->onset[$sender->getName()]['playinglocation'] = array(
                        'x' => $sender->x,
                        'y' => $sender->y,
                        'z' => $sender->z,
                    );
                    $sender->sendMessage($this->prefix."设置玩家游玩位置成功");
                    return true;
                break;

                case 'f':
                    if(!isset($this->onset[$sender->getName()])) {
                        return false;
                    }
                    $name = $this->onset[$sender->getName()]['name'];
                    unset($this->onset[$sender->getName()]['name']);
                    $con = new Config($this->getDataFolder()."rooms/{$name}.yml", Config::YAML);
                    $con->setAll($this->onset[$sender->getName()]);
                    $con->save();
                    unset($this->onset[$sender->getName()]);
                    $sender->sendMessage($this->prefix."房间配置文件{$name}配置成功，需要配置更多的话请到指定文件进行修改，并且使用/pw reload来重新加载配置文件");
                    return true;
                break;

                case 'remove':
                    if(!is_file($this->getDataFolder()."rooms/{$args[1]}.yml")) {
                        return false;
                    }
                    $unlink = unlink($this->getDataFolder()."rooms/{$args[1]}.yml");
                    if(!$unlink) {
                        $sender->sendMessage($this->prefix."删除房间配置文件{$args[1]}失败，请检查PocketMine是否有足够的权限");
                        return true;
                    }
                    $sender->sendMessage($this->prefix."移除房间配置文件{$args[1]}成功");
                    return true;
                break;

                case 'reload':
                    if(!is_file($this->getDataFolder()."rooms/{$args[1]}.yml")) {
                        return false;
                    }
                    $con = new Config($this->getDataFolder()."rooms/{$args[1]}.yml", Config::YAML);
                    $con->reload();
                    $sender->sendMessage($this->prefix."重新加载房间配置文件{$args[1]}成功");
                    return true;
            }
        }
    }
} 