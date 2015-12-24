<?php

namespace App\Presenters;

use Nette;
use App\Model;


class MealMenuPresenter extends BasePresenter
{

	/**
	 * @var \App\Services\MealMenu @inject */
	public $mealMenu;

	public function renderDefault()
	{
		dump($this->mealMenu->sinceDate(1, null, 14));
		
		$this->template->anyVariable = 'any value';
	}

}
