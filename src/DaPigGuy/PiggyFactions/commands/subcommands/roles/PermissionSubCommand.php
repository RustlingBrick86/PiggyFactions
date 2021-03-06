<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands\roles;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\exception\ArgumentOrderException;
use DaPigGuy\PiggyFactions\commands\arguments\PermissibleEnumArgument;
use DaPigGuy\PiggyFactions\commands\arguments\PermissionEnumArgument;
use DaPigGuy\PiggyFactions\commands\subcommands\FactionSubCommand;
use DaPigGuy\PiggyFactions\event\role\FactionPermissionChangeEvent;
use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\permissions\PermissionFactory;
use DaPigGuy\PiggyFactions\players\FactionsPlayer;
use DaPigGuy\PiggyFactions\utils\Relations;
use DaPigGuy\PiggyFactions\utils\Roles;
use pocketmine\Player;

class PermissionSubCommand extends FactionSubCommand
{
    public function onNormalRun(Player $sender, ?Faction $faction, FactionsPlayer $member, string $aliasUsed, array $args): void
    {
        if ($member->getRole() !== Roles::LEADER && !$member->isInAdminMode()) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.not-leader");
            return;
        }
        if (PermissionFactory::getPermission($args["permission"]) === null) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.permission.permission-not-found");
            return;
        }
        if (!isset(Roles::ALL[$args["role"]]) && !in_array($args["role"], Relations::ALL)) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.permission.role-not-found");
            return;
        }

        $ev = new FactionPermissionChangeEvent($faction, $args["role"], $args["permission"], $args["value"] ?? !$faction->getPermission($args["role"], $args["permission"]));
        $ev->call();
        if ($ev->isCancelled()) return;

        $faction->setPermission($args["role"], $args["permission"], $ev->getValue());
        LanguageManager::getInstance()->sendMessage($sender, "commands.permission.success", ["{PERMISSION}" => $args["permission"], "{ROLE}" => $args["role"], "{TOGGLED}" => $ev->getValue() ? "enabled" : "disabled"]);
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new PermissibleEnumArgument("role"));
        $this->registerArgument(1, new PermissionEnumArgument("permission"));
        $this->registerArgument(2, new BooleanArgument("value", true));
    }
}