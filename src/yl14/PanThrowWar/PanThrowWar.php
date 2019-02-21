<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\command\{Command, CommandSender};
use pocketmine\Player;

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    const LABEL = 'Beta';

    private static $instance;

    private $Sessions = [];
    private $InGame = [];

    public $prefix = TF::GREEN."[丢锅大战]".TF::WHITE;
    private $onset = [];

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
                //先遍历所有Session看看有没有能用的
                foreach($this->Sessions as $Session) {
                    //TODO
                }
            }
        }
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
                    }
            }
        }
    }
} 