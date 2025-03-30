<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\world\Position;

use pocketmine\utils\Config;

use terpz710\warzoneenvoys\manager\EnvoyManager;

use terpz710\warzoneenvoys\utils\EnvoyUtils;

class EventListener implements Listener {

    private Config $messagesConfig;

    public function __construct() {
        $this->messagesConfig = new Config($this->getDataFolder() . "messages.yml");
    }

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $config = Loader::getInstance()->getConfig();
        $manager = EnvoyManager::getInstance();
        $chestLocations = $config->get("chest_locations", []);

        foreach ($chestLocations as $chestLocation) {
            $chestPosition = new Position($chestLocation["x"], $chestLocation["y"], $chestLocation["z"], $player->getWorld());

            if ($block->getPosition()->equals($chestPosition)) {
                $event->cancel();
                $manager->dropItemsFromChest($chestPosition);
                $manager->removeChest($chestPosition);
                $manager->removeFloatingText($chestPosition);
                $player->sendMessage($this->messagesConfig->get("envoy_claimed"));
                $player->sendTitle($this->messagesConfig->get("envoy_claimed_title"));
                $player->sendSubTitle($this->messagesConfig->get("envoy_claimed_subtitle"));
                EnvoyUtils::getInstance()->playSound($player, "random.explode");
                break;
            }
        }
    }
}