<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\command\{Command, CommandSender};

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    const LABEL = 'Beta';
    const DEBUG = false;

    private static $instance;

    private $Sessions = [];
    private $InGame = [];

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
    public static function getInstance() {
        return self::$instance;
    }

    /**
     * @param pocketmine\Player[] $players
     * 
     * @return bool
     */
    public function SearchRoom(array $players) : bool {
        //TODO
    }
} 