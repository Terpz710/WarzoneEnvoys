<?php

declare(strict_types=1);

namespace terpz710\warzoneenvoys\task;

use pocketmine\scheduler\Task;

use terpz710\warzoneenvoys\manager\EnvoyManager;

class EnvoyTask extends Task {

    public function onRun() : void{
        EnvoyManager::getInstance()->createChest();
    }
}