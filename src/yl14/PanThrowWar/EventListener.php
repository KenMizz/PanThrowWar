<?php

namespace yl14\PanThrowWar;

use pocketmine\event\{
    Listener, player\PlayerJoinEvent, player\PlayerQuitEvent, player\PlayerDeathEvent, player\PlayerDropItemEvent, entity\EntityArmorChangeEvent, block\BlockBreakEvent, block\BlockPlaceEvent
};

class EventListener implements Listener {

    private $plugin;

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
    }
	
	//事件调用例子
	public function onPlayerDeath(PlayerDeathEvent $ev) {
		$name = $ev->getPlayer()->getName();
		if(isset($this->plugin->InGame[$name])) {
			if(isset($this->plugin->Sessions[$this->plugin->InGame[$name]])) {
				$session = $this->plugin->Sessions[$this->plugin->InGame[$name]];
				if($session->existPlayer($name)) {
					$session->onEventListener($ev);
				}
			}
		}
	}

    
}