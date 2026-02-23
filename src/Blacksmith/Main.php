<?php

declare(strict_types=1);

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase {

    private array $blacksmiths = []; // Stores NPCs with positions
    private Config $cfg;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig();

        // Load persistent blacksmiths if any (can extend with a separate file)
        // For simplicity, we’ll spawn one per command for now
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$sender instanceof Player) return false;

        if(!$sender->hasPermission("blacksmith.use")) {
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
            return true;
        }

        $this->spawnBlacksmith($sender);

        return true;
    }

    private function spawnBlacksmith(Player $player): void {
        $pos = $player->getPosition();

        // Store the blacksmith NPC (persistent logic can be added later)
        $this->blacksmiths[$player->getName()] = $pos;

        $player->sendMessage(TextFormat::colorize("&aBlacksmith spawned! Click the NPC to open the shop."));
    }

    /**
     * Open the blacksmith shop GUI
     */
    public function openShop(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data){
            if($data === null) return;

            switch($data){
                case 0: // Repair
                    $this->repairItem($player);
                    break;
                case 1: // Rename
                    $this->renameItem($player);
                    break;
            }
        });

        $form->setTitle(TextFormat::colorize($this->cfg->getNested("messages.shop-title", "&6Blacksmith Shop")));
        $form->addButton("Repair Item");
        $form->addButton("Rename Item");
        $player->sendForm($form);
    }

    private function repairItem(Player $player): void {
        $xpCost = (int)$this->cfg->get("xp-cost", 2);
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-item", "&cYou must hold an item to perform this action.")));
            return;
        }

        if($player->getXpLevel() < $xpCost){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-xp", "&cYou do not have enough XP to perform this action.")));
            return;
        }

        $item->setDamage(0);
        $player->getInventory()->setItemInHand($item);
        $player->subtractXpLevels($xpCost);

        $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.repair-success", "&aYour item has been repaired!")));
    }

    private function renameItem(Player $player): void {
        $xpCost = (int)$this->cfg->get("xp-cost", 2);
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-item", "&cYou must hold an item to perform this action.")));
            return;
        }

        if($player->getXpLevel() < $xpCost){
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.no-xp", "&cYou do not have enough XP to perform this action.")));
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null) return;

            $name = $data[0] ?? null;
            if($name === null || $name === ""){
                $player->sendMessage(TextFormat::RED . "You must enter a valid name.");
                return;
            }

            $item = $player->getInventory()->getItemInHand();
            $item->setCustomName(TextFormat::colorize($name));
            $player->getInventory()->setItemInHand($item);
            $player->subtractXpLevels((int)$this->cfg->get("xp-cost", 2));
            $player->sendMessage(TextFormat::colorize($this->cfg->getNested("messages.rename-success", "&aYour item has been renamed!")));
        });

        $form->setTitle(TextFormat::colorize($this->cfg->getNested("messages.shop-title", "&6Blacksmith Shop")));
        $form->addInput("Enter new name for your item:");
        $player->sendForm($form);
    }
}
