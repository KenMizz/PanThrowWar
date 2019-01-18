<?php

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\Player;

use yl13\GameCoreAPI\GameCoreAPI as GCAPI;
use yl13\SkyWar\SessionTask;

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

    public function SearchRoom(Array $filter = [], Array $players) : bool {
        /**
         * 寻找可用的房间
         * 一切的入口
         * 未来filter会允许选择地图
         */
        if(isset($filter['players'])) {

        }
    }

    private function joinRoom(int $roomid, Array $players) : bool {

    }

    private function leaveRoom(int $roomid, Array $players, int $reason = 0) : bool {

    }

    private function createRoom(int $roomid, String $levelname, Array $waitinglocation, Array $playinglocation, Array $settings) : bool {
        if(!isset($this->Sessions[$roomid])) {
            $this->Sessions[$roomid] = new PTWSession($this->plugin, $roomid, $levelname, $waitinglocation, $playinglocation, $settings);
            $this->getScheduler()->scheduleRepeatingTask(new SessionTask($this->plugin, $roomid), 20)
        }
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
                        'gametime' => 600,//seconds
                        //'money' => 100,
                        'explodetime' => 60
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

    private function randnum(int $digit) : int {
        $id = null;
        for($i = 0;$i < $digit;$i++) {
            $id .= mt_rand(0, 9);
        }
        return $id;
    }
}