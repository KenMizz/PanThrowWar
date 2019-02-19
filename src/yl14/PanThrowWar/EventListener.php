<?php

namespace yl14\PanThrowWar;

use pocketmine\event\{
    Listener, player\PlayerQuitEvent, player\PlayerDropItemEvent, player\PlayerItemHeldEvent, entity\EntityArmorChangeEvent, block\BlockBreakEvent, block\BlockPlaceEvent, entity\EntityDamageByEntityEvent
};
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class EventListener implements Listener {

	private $plugin;
	
	private $onQuit = [];

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
	}

	public function onDropItem(PlayerDropItemEvent $ev) {
		$player = $ev->getPlayer();
		if($this->plugin->getPlayerInGame($player)) {
			$ev->setCancelled();
		}
	}
	
	public function onPlayerQuit(PlayerQuitEvent $ev) {
		$player = $ev->getPlayer();
		if($this->plugin->getPlayerInGame($player)) {
			$this->plugin->leaveRoom($this->plugin->getPlayerInGame($player), [$player]);
		}
	}

	public function onPlayerItemHeld(PlayerItemHeldEvent $ev) {
		$player = $ev->getPlayer();
		if($this->plugin->getPlayerInGame($player)) {
			if($ev->getItem()->getCustomName() == TF::RED."退出房间") {
				if(!isset($this->onQuit[$player->getName()])) {
					$ev->setCancelled();
					$this->onQuit[$player->getName()] = 1;
					$player->sendTip("再点一次即可退出游戏");
				} else {
					unset($this->onQuit[$player->getName()]);
					$this->plugin->leaveRoom($this->plugin->getPlayerInGame($player), [$player]);
				}
			}
		}
	}

	public function onEntityArmorChange(EntityArmorChangeEvent $ev) {
		$entity = $ev->getEntity();
		if($entity instanceof Player) {
			if($this->plugin->getPlayerInGame($entity)) {
				$ev->setCancelled();
			}
		}
	}

	public function onBlockBreak(BlockBreakEvent $ev) {
		$player = $ev->getPlayer();
		if($this->plugin->getPlayerInGame($player)) {
			$ev->setCancelled();
		}
	}

	public function onBlockPlace(BlockPlaceEvent $ev) {
		$player = $ev->getPlayer();
		if($this->plugin->getPlayerInGame($player)) {
			$ev->setCancelled();
		}
	}

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $ev) {
		//TODO
	}
}