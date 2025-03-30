<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys;

use pocketmine\plugin\PluginBase;

use terpz710\warzoneenvoys\task\EnvoyTask;

use DaPigGuy\libPiggyUpdateChecker\libPiggyUpdateChecker;

class Loader extends PluginBase {

    protected static self $instance;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->saveDefaultConfig();
        $this->saveResource("items.yml");
        $this->saveResource("messages.yml");

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $targetTimeSeconds = $this->getConfig()->get("target_time", 60);
        $targetTimeTicks = $targetTimeSeconds * 20;
        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask(), $targetTimeTicks);

        libPiggyUpdateChecker::init($this);
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}