<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\command\{CommandSender, Command};
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;

use yl13\GameCoreAPI\GameCoreAPI as GCAPI;
use yl13\GameWizard\GameWizard as GW;

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
	
	private $Sessions = [];
	
	private $InGame = [];
	
    private $gameid;

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
        $this->getLogger()->warning("丢锅大战已关闭");
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
     * @param pocketmine\Player[] $players
     * @param Array $filter
     * 
     * @return bool
     */
    public function SearchRoom(Array $players, Array $filter = []) : bool {
        if(isset($filter['maxplayer'])) {
            $this->getLogger()->notice(TF::WHITE."Search with filter");
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
        $this->getLogger()->notice(TF::WHITE."Search with no filter");
        //先循环一下所有Session看看有没有可用的
        foreach($this->Sessions as $Session) {
            if($Session instanceof PTWSession) {
                $result = $this->joinRoom($Session->getRoomId(), $players);
                if(!$result) {
                    continue;
                }
            }
            continue;
        }
        $this->getLogger()->notice(TF::WHITE."读取配置文件...");
        $room = $this->randRoom();
        if($room instanceof Config) {
            $this->getLogger()->notice(TF::WHITE."instance of Config");
            $roomid = $this->randnum(8);
            $rc = $this->createRoom($roomid, $room->get('levelname'), $room->get('waitinglocation'), $room->get('playinglocation'), $room->get('settings'));
            if(!$rc) {
                $this->getLogger()->notice(TF::WHITE."创建房间失败");
                return false;
            }
            $rj = $this->joinRoom($roomid, $players);
            if(!$rj) {
                $this->getLogger()->notice(TF::WHITE."加入房间失败");
                return false;
            }
            return true;
        }
        $this->getLogger()->notice(TF::WHITE."not instance of Config");
        return false;
    }

    /**
     * @param int $roomid
     * @param pocketmine\Players[] $players
     * 
     * @return bool
     */
    private function joinRoom(int $roomid, Array $players) : bool {
        $room = $this->getRoomById($roomid);
        if($room instanceof PTWSession) {
            $Status = $room->getStatus();
            if($Status == 0 or $Status == 1) { //可以进入的状态
                $leftplayers = $room->getMaxPlayer() - count($room->getPlayers()); //算出剩下可允许的玩家人数
                if(!$leftplayers - count($players) < 0) { //防止超过可进的玩家限制从而导致插件错误
                    foreach($players as $p) {
                        if($p instanceof Player) {
                            if($p->isOnline()) {
                                $this->InGame[$p->getName()] = $roomid;
                                GCAPI::getInstance()->api->getChatChannelAPI()->addPlayer($this->gameid, (String)$roomid, $players);
                                GCAPI::getInstance()->api->getChatChannelAPI()->broadcastMessage($this->gameid, (String)$roomid, TF::YELLOW."{$p->getName()}".TF::WHITE."加入了房间");
                                $waitinglocation = $room->getWaitingLocation();
                                $p->teleport(new Position($waitinglocation['x'], $waitinglocation['y'], $waitinglocation['z'], $this->getServer()->getLevelByName($room->getLevelName())));
                                $wool = Item::get(Item::WOOL, 14);
                                $wool->setCustomName(TF::RED."退出房间");
                                $p->setGamemode(0);
                                $p->getInventory()->clearAll();
                                $p->getInventory()->addItem($wool);
                                $room->addPlayer($p);
                            }
                            continue;
                        }
                        continue;
                    }
                    $this->updateSession($room->getRoomId(), $room);
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
     * 
     * @return bool
     */
    public function removeRoom(int $roomid) : bool {
        $room = $this->getRoomById($roomid);
        if($room instanceof PTWSession) {
            $players = $room->getPlayers();
            $this->leaveRoom($roomid, $players);
            GCAPI::getInstance()->api->getChatChannelAPI()->remove($this->gameid, (String)$roomid);
            unset($this->Sessions[$roomid]);
            return true;
        }
        return false;
    }

    /**
     * @param int $roomid
     * @param Array $players
     */
    public function leaveRoom(int $roomid, Array $players) {
        $room = $this->getRoomById($roomid);
        if($room instanceof PTWSession) {
            foreach($players as $p) {
                if($p instanceof Player) {
                    if($room->getPlayer($p) instanceof Player) {
                        unset($this->InGame[$p->getName()]);
                        $room->removePlayer($p);
                        GCAPI::getInstance()->api->getChatChannelAPI()->removePlayer($this->gameid, (String)$roomid, array($p));
                        GCAPI::getInstance()->api->getChatChannelAPI()->broadcastMessage($this->gameid, (String)$roomid, TF::YELLOW.$p->getName().TF::WHITE."退出了房间");
                        $room->removePlayer($p);
                        $p->getInventory()->clearAll();
                        $p->getArmorInventory()->clearAll();
                        $p->removeAllEffects();
                        GW::GiveCompass($p);
                        $p->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                    }
                }
            }
            $this->updateSession($room->getRoomId(), $room);
        }
    }

    /**
     * @param int $roomid
     * @param yl14\PanThrowWar\PTWSession $session
     * 
     * @return bool
     */
    public function updateSession(int $roomid, PTWSession $session) : bool {
        if(isset($this->Sessions[$roomid])) {
            $this->Sessions[$roomid] = $session;
            return true;
        }
        return false;
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
            $this->Sessions[$roomid] = new PTWSession($this, $roomid, $levelname, $waitinglocation, $playinglocation, $settings);
            $this->getScheduler()->scheduleRepeatingTask(new SessionTask($this, $roomid), 20);
            GCAPI::getInstance()->api->getChatChannelAPI()->create($this->gameid, (String)$roomid);
            if(!$this->getServer()->getLevelByName($levelname)) {
                $result = GCAPI::getInstance()->api->getMapLoaderAPI()->create($this->gameid, $levelname);
                if(!$result) {
                    $this->getLogger()->warning("在ID为".TF::WHITE.$roomid.TF::YELLOW."的Session下的地图加载错误!玩家如果加入可能会导致问题!");
                } else {
                    $this->getServer()->getLevelByName($levelname)->setDifficulty(1);
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
    public function getRoomById(int $roomid) : ?PTWSession {
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
     * 
     * @return int|bool
     */
    public function getPlayerInGame(Player $player) {
        if(isset($this->InGame[$player->getName()])) {
            return $this->InGame[$player->getName()];
        }
        return false;
    }

    /**
     * @param Array $filter
     * 
     * @return pocketmine\utils\Config|bool
     */
    private function randRoom(Array $filter = []) {
        /**
         * filter:
         *  maxplayer
         *  mapname (WIP)
         */
        $roomdir = $this->getDataFolder()."configs/";
        if(isset($filter['maxplayer'])) {
            if(is_dir($roomdir)) { //检查文件夹是否存在
                $scan = scandir($roomdir);
                $rooms = [];
                foreach($scan as $dir) {
                    $fileinfo = pathinfo($dir);
                    if($fileinfo['extension'] == 'yml') {
                        $config = new Config($roomdir."{$fileinfo['filename']}.yml", Config::YAML);
                        $settings = $config->get('settings');
                        if($settings['maxplayer'] == $filter['maxplayer']) {
                            $rooms[] = $fileinfo['filename'];
                        }
                    }
                }
                if(!empty($rooms)) {
                    shuffle($rooms);
                    return new Config($roomdir."{$rooms[0]}.yml", Config::YAML);
                }
                return false;
            }
            return false;
        }
        if(is_dir($roomdir)) { //检查文件夹是否存在
            $scan = scandir($roomdir);
            $rooms = [];
            foreach($scan as $dir) {
                var_dump($scan);
                $fileinfo = pathinfo($dir);
                if($fileinfo['extension'] == 'yml') {
                    $config = new Config($roomdir."{$fileinfo['filename']}.yml", Config::YAML);
                    $settings = $config->get('settings');
                    $rooms[] = $fileinfo['filename'];
                }
            }
            if(!empty($rooms)) {
                shuffle($rooms);
                return new Config($roomdir."{$rooms[0]}.yml", Config::YAML);
            }
            return false;
        }
        return false;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
        $cmdname = $cmd->getName();
        if($cmdname == 'pw') {
            if(!isset($args[0])) {
                return false;
            }
            switch($args[0]) {
                
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
                    $sender->sendMessage($this->prefix."开始设置房间配置文件{$args[1]}\n走到玩家等待位置输入/pw w即可设置等待位置\n走到玩家游玩位置输入/pw p即可设置游玩位置\n当一切设置完成后，输入/pw f来完成配置");
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
            }
        }
    }

    /**
     * @param int $digit
     * 
     * @return int
     */
    private function randnum(int $digit) : int {
        $id = null;
        for($i = 0;$i < $digit;$i++) {
            $id .= mt_rand(0, 9);
        }
        return (int)$id;
    }
}