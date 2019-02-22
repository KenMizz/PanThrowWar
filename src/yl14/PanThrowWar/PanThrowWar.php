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

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    const LABEL = 'Beta';

    private static $instance;

    private $Sessions = [];
    private $InGame = [];

    public $prefix = TF::GREEN."[丢锅大战]".TF::WHITE;
    private $onset = [];
    private $gameid = 0;

    public function onEnable() {
        $this->getLogger()->notice(TF::YELLOW."丢锅大战已启用，初始化插件中...");
        $this->initPlugin();
        $this->getLogger()->notice(TF::GREEN."插件初始化成功");
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onDisable() {
        //TODO
    }

    private function initPlugin() {
        GCAPI::getInstance()->api->getGameCoreAPI()->registerGame("丢锅大战", "游乐14");
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
    public function getPlayerInGame(Player $player) : ?int {
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
            foreach($players as $p) {
                if($p instanceof Player) {
                    $Session->addPlayer($p);
                    $this->InGame[$p->getName()] = $sessionid;
                    $p->getInventory()->clearAll();
                    $p->getArmorInventory()->clearAll();
                    $p->teleport(new Position($waitinglocation['x'], $waitinglocation['y'], $waitinglocation['z'], $this->getServer()->getLevelByName($Session->getLevelName())));
                    $p->getInventory()->addItem($exitwool);
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
     * @param int $reason //0 = 正常退出 1 = 输了 2 = 强制退出
     * 
     * @return bool
     */
    private function leaveRoom(array $players, int $sessionid, int $reason = 0) : bool {
        $Session = $this->getRoomById($sessionid);
        if($Session instanceof PTWSession) {
            foreach($players as $p) {
                if($p instanceof Player) {
                    //TODO
                }
            }
        }
    }

    /**
     * @param int $sessionid
     * 
     * @return bool
     */
    public function closeRoom(int $sessionid, int $taskid) : bool {

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
                            'money' => 10
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