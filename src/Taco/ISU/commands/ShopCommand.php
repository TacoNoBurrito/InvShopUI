<?php namespace Taco\ISU\commands;

use Taco\ISU\Main;
use pocketmine\{command\PluginCommand, command\CommandSender, plugin\Plugin};

/**
 * Class ShopCommand
 * @package Taco\RU\commands
 */
class ShopCommand extends PluginCommand {

	/**
	 * @var Main $plugin
	 */
	private $plugin;

	/**
	 * ShopCommand constructor.
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		parent::__construct("shop", $plugin);
		$this->setDescription("Open the shop menu!");
		$this->plugin = $plugin;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		$sender->sendMessage("§aOpening Shop Menu.");
		$this->plugin->openMenu1($sender);
		return true;
	}
}