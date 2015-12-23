<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;
		$router[] = new Route('cron.php', 'Cron:default');
		$router[] = new Route('<presenter>/<action>[/<id>]', 'MealMenu:default');
		return $router;
	}

}
