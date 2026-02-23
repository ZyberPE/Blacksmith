<?php

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\form\CustomForm;
use pocketmine\form\ModalForm;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private Config $config;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "repair-cost" => 10,
            "rename-cost" => 5,
            "messages" => [
                "not-enough-xp" => "§cYou do not have enough XP!",
                "must-hold-item" => "§cYou must hold an item in your hand!",
                "repair-success" => "§aItem successfully repaired!",
                "rename-success" => "§aItem successfully renamed to {name}!",
                "invalid-rename" => "§cYou must enter a valid name!"
            ]
        ]);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) return false;
        if (strtolower($command->getName()) === "blacksmith") {
            $this->openMainMenu($sender);
            return true;
        }
        return false;
    }

    private function openMainMenu(Player $player): void {
        $form = new ModalForm(function (Player $player, ?bool $data) {
            if ($data === null) return;
            if ($data === true) {
                $this->openRepairConfirm($player);
            } else {
                $this->openRenameForm($player);
            }
        });

        $form->setTitle("§lBlacksmith");
        $form->setContent("Choose an action:");
        $form->setButton1("Repair Item");
        $form->setButton2("Rename Item");
        $player->sendForm($form);
    }

    private function openRepairConfirm(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            $player->sendMessage($this->config->getNested("messages.must-hold-item"));
            return;
        }

        $cost = $this->config->get("repair-cost");
        $xp = $player->getXpLevel();

        $form = new ModalForm(function (Player $player, ?bool $data) use ($item, $cost) {
            if ($data === null) return;
            if ($data) {
                $this->repairItem($player, $item, $cost);
            }
        });

        $form->setTitle("§lRepair Item");
        $form->setContent("Repair this item for §e$cost XP\nYour XP: §a$xp");
        $form->setButton1("Submit");
        $form->setButton2("Cancel");
        $player->sendForm($form);
    }

    private function repairItem(Player $player, Item $item, int $cost): void {
        if ($player->getXpLevel() < $cost) {
            $player->sendMessage($this->config->getNested("messages.not-enough-xp"));
            return;
        }
        $player->subtractXpLevels($cost);
        $item->setDamage(0);
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage($this->config->getNested("messages.repair-success"));
    }

    private function openRenameForm(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            $player->sendMessage($this->config->getNested("messages.must-hold-item"));
            return;
        }

        $cost = $this->config->get("rename-cost");
        $xp = $player->getXpLevel();

        $form = new CustomForm(function (Player $player, ?array $data) use ($item, $cost) {
            if ($data === null) return;
            $name = trim($data[0]);
            if ($name === "") {
                $player->sendMessage($this->config->getNested("messages.invalid-rename"));
                return;
            }
            $this->renameItem($player, $item, $name, $cost);
        });

        $form->setTitle("§lRename Item");
        $form->addInput("Enter new name for the item:", "", "");
        $form->setContent("Rename this item for §e$cost XP\nYour XP: §a$xp");
        $player->sendForm($form);
    }

    private function renameItem(Player $player, Item $item, string $name, int $cost): void {
        if ($player->getXpLevel() < $cost) {
            $player->sendMessage($this->config->getNested("messages.not-enough-xp"));
            return;
        }
        $player->subtractXpLevels($cost);
        $item->setCustomName($name);
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(str_replace("{name}", $name, $this->config->getNested("messages.rename-success")));
    }
}
