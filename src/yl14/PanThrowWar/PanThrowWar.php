<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\Player;
use pocketmine\level\Position;

use yl13\GameCoreAPI\GameCoreAPI as GCAPI;

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    private $gameid;

    private $Sessions = [];
    private $InGame = [];

    private $onset = [];
    public $prefix = TF::WHITE."[".TF::GREEN."丢锅大战".TF::WHITE."]";

    private static $instance;

    public function onEnable() {
        $this->initPlugin();
        $this->getLogger()->notice(TF::GREEN."丢锅大战已启动!");        
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onDisable() {
        $this->getLoggeer()->warning("丢锅大战已关闭");
    }

    public static function getInstance() {
        return self::$instance;
    }

    private function initPlugin() {
        $this->gameid = GCAPI::getInstance()->api->getGameCoreAPI()->registerGame("丢锅大战", "游乐14");
        $this->getLogger()->notice(TF::YELLOW."正在初始化插件中...");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        if(!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }
        if(!is_dir($this->getDataFolder().'configs')) {
            @mkdir($this->getDataFolder().'configs');
        }
        $this->getLogger()->notice(TF::GREEN."初始化成功!");
    }

    /**
     * @param Array $filter
     * @param pocketmine\Player[] $players
     * @return bool
     */
    public function SearchRoom(Array $filter = [], Array $players) : bool { 
        if(isset($filter['maxplayer'])) {
            //先循环一下所有Session看看有没有可用的
            foreach($this->Sessions as $Session) {
                if($Session instanceof PTWSession) {
                    $maxplayer = $Session->getMaxPlayer();
                    if($filter['maxplayer'] == $maxplayer) { //匹配
                        $result = $this->joinRoom($Session->getRoomId(), $players);
                        if(!$result) {
                            continue;
                        }
                        return true;
                    }
                    continue;
                }
                continue;
            }
            //执行到这个代码块代表没有任何可用的房间
            $room = $this->randRoom($filter);
            if($room instanceof Config) {
                $roomid = $this->randnum(8);
                $rc = $this->createRoom($roomid, $room->get('levelname'), $room->get('waitinglocation'), $room->get('playinglocation'), $room->get('settings'));
                if(!$rc) {
                    return false;
                }
                $rj = $this->joinRoom($roomid, $players);
                if(!$rj) {
                    return false;
                }
                return true;
            }
        }
        foreach($this->Sessions as $Session) {
            if($Session instanceof PTWSession) {
                $result = $this->joinRoom($Session->getRoomId(), $players);
                if(!$result) {
                    continue;
                }
            }
            continue;
        }
        $room = $this->randRoom();
        if($room instanceof Config) {
            $rc = $this->createRoom($roomid, $room->get('levelname'), $room->get('waitinglocation'), $room->get('playinglocation'), $room->get('settings'));
            if(!$rc) {
                return false;
            }
            $rj = $this->joinRoom($roomid, $players);
            if(!$rj) {
                return false;
            }
            return true;
        }
    }

    /**
     * @param int $roomid
     * @param pocketmine\Players[] $players
     * @return bool
     */
    private function joinRoom(int $roomid, Array $players) : bool {
        $room = $this->getRoomById($roomid);
        if($room instanceof PTWSession) {
            $Status = $room->getStatus();
            if($Status == 0 or $Status == 1) { //可以进入的状态
                $leftplayers = count($room->getMaxPlayer()) - count($room->getPlayers());
                if(!$leftplayers - count($players) < 0) { //防止超过可进的玩家限制从而导致插件错误
                    foreach($players as $p) {
                        if($p instanceof Player) {
                            if($p->isOnline()) {
                                $this->InGame[$p->getName()] = $roomid;
                                GCAPI::getInstance()->api->getChatChannelAPI()->addPlayer($this->gameid, (String)$roomid, $players);
                                GCAPI::getInstance()->api->getChatChannelAPI()->broadcastMessage($this->gameid, (String)$roomid, TF::YELLOW."{$p->getName()}".TF::WHITE."加入了房间");
                                $waitinglocation = $room->getWaitingLocation();
                                $p->teleport(new Position($waitinglocation['x'], $waitinglocation['y'], $waitinglocation['z'], $this->getServer()->getLevelByName($room->getLevelName())));
                            }
                            continue;
                        }
                        continue;
                    }
                    return true;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * @param int $roomid
     * @param pocketmine\Player[] $players
     * @param int $reason
     * @return bool
     */
    private function leaveRoom(int $roomid, Array $players, int $reason = 0) : bool {
        $room = $this->getRoomById($roomid);
        if($room instanceof PTWSession) {
            //TODO
        }
    }

    /**
     * @param int $roomid
     * @param String $levelname
     * @param Array $waitinglocation
     * @param Array $playinglocation
     * @param Array $settings
     * @return bool
     */
    private function createRoom(int $roomid, String $levelname, Array $waitinglocation, Array $playinglocation, Array $settings) : bool {
        if(!isset($this->Sessions[$roomid])) {
            $this->Sessions[$roomid] = new PTWSession($this->plugin, $roomid, $levelname, $waitinglocation, $playinglocation, $settings);
            GCAPI::getInstance()->api->getChatChannelAPI()->create($this->gameid, (String)$roomid);
            if(!$this->getServer()->getLevelByName($levelname)) {
                $result = GCAPI::getInstance()->api->getMapLoaderAPI()->create($this->gameid, $levelname);
                if(!$result) {
                    $this->getLogger()->warning("在ID为".TF::WHITE.$roomid.TF::YELLOW."的Session下的地图加载错误!玩家如果加入可能会导致问题!");
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $roomid
     * @return bool|PTWSession
     */
    private function getRoomById(int $roomid) : ?PTWSession {
        if(isset($this->Sessions[$roomid])) {
            if($this->Sessions[$roomid] instanceof PTWSession) {
                return $this->Sessions[$roomid];
            }
            return false;
        }
        return false;
    }

    /**
     * @param pocketmine\Player $player
     * @return bool
     */
    public function isPlayerInGame(Player $player) : bool {
        if(isset($this->InGame[$player->getName()])) {
            return true;
        }
        return false;
    }

    /**
     * @param Array $filter
     */
    private function randRoom(Array $filter = []) : ?Config {
        /**
         * filter:
         *  maxplayer
         *  mapname (WIP)
         */
        if(isset($filter['maxplayer'])) {
            $roomdir = $this->getDataFolder()."rooms";
            if(is_dir($roomdir)) {
                $files = scandir($roomdir);
                $rooms = [];
                foreach($files as $file) {
                    $pinfo = pathinfo($file);
                    if($pinfo['extension'] == 'yml') {
                        $rooms[$pinfo['basename']] = $pinfo['basename'];
                    }
                }
                if(!empty($rooms)) {
                    $matchs = [];
                    foreach($rooms as $room) {
                        $c = new Config($roomdir."{$room}.yml", Config::YAML); //可优化
                        $settings = $c->get('settings');
                        if($settings['maxplayer'] == $filter['maxplayer']) {
                            $matchs[$room] = $room;
                        }
                    }
                    if(!empty($matchs)) {
                        shuffle($matchs);
                        if($matchs[0] instanceof Config) {
                            return $matchs[0];
                        }
                        return false;
                    }
                    return false;
                }
                return false;
            }
            return false;
        }
        $roomdir = $this->getDataFolder()."rooms";
        if(is_dir($roomdir)) {
            $files = scandir($roomdir);
            $rooms = [];
            foreach($files as $file) {
                $pinfo = pathinfo($file);
                if($pinfo['extension'] == 'yml') {
                    $rooms[$pinfo['basename']] = $pinfo['basename'];
                }
            }
            if(!empty($rooms)) {
                shuffle($rooms);
                if($rooms[0] instanceof Config) {
                    return $rooms[0];
                }
                return false;
            }
            return false;
        }
        return false;
    }

    public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool {
        $cmdname = $cmd->getName();
        switch($cmdname) {

            default:
                return false;
            break;

            case 'help':
                $sender->sendMessage(TF::AQUA."----丢锅大战帮助----");
                $sender->sendMessage(TF::WHITE."/pw set 房间名 设置新的房间配置文件");
                $sender->sendMessage("/pw reload 房间名 重载指定的房间配置文件");
                $sender->sendMessage("/pw remove 房间名 移除指定的房间配置文件");
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
                    unset($this->onset[$sender->getName()]);
                    $name = $this->onset[$sender->getName()]['name'];
                    $sender->sendMessage($this->prefix.TF::RED."已取消{$name}的房间配置文件设置");
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
                        'maxplayer' => 4,
                        'minplayer' => 2,
                        'gametime' => 600, //seconds
                        //'money' => 100,
                        'explodetime' => 60 //seconds
                    )
                );
                $sender->sendMessage($this->prefix."开始设置房间配置文件{$name}\n走到玩家等待位置输入/pw w即可设置等待位置\n走到玩家游玩位置输入/pw p即可设置游玩位置\n当一切设置完成后，输入/pw f来完成配置");
                return true;
            break;

            case 'w':
                if(!isset($this->onset[$sender->getName()])) {
                    return false;
                }
                if(!$sender instanceof Player) {
                    return false;
                }
                $this->onset[$sender->getName()]['waitinglocation'] = array(
                    'x' => $sender->x,
                    'y' => $sender->y,
                    'z' => $sender->z
                );
                $sender->sendMessage($this->prefix."玩家等待位置设置成功");
                return true;
            break;
            
            case 'p':
                if(!isset($this->onset[$sender->getName()])) {
                    return false;
                }
                if(!$sender instanceof Player) {
                    return false;
                }
                $this->onset[$sender->getName()]['playinglocation'] = array(
                    'x' => $sender->x,
                    'y' => $sender->y,
                    'z' => $sender->z
                );
                $sender->sendMessage($this->prefix."玩家游玩位置设置成功");
                return true;
            break;

            case 'f':
                $name = $this->onset[$sender->getName()]['name'];
                new Config($this->getDataFolder()."configs/{$name}.yml", Config::YAML, $this->onset[$sender->getName()]);
                unset($this->onset[$sender->getName()]);
                $sender->sendMessage($this->prefix."已完成房间配置文件{$name}的设置");
                return true;
            break;

            case 'reload':
                if(!isset($args[1])) {
                    return false;
                }
                if(!is_file($this->getDataFolder()."configs/{$name}.yml")) {
                    return false;
                }
                $c = new Config($this->getDataFolder()."configs/{$name}.yml", Config::YAML);
                $c->reload();
                $sender->sendMessage($this->prefix."房间配置文件{$name}重新载入成功");
                return true;
            break;
        }
    }

    /**
     * @param int $digit
     * @return int
     */
    private function randnum(int $digit) : int {
        $id = null;
        for($i = 0;$i < $digit;$i++) {
            $id .= mt_rand(0, 9);
        }
        return $id;
    }
}