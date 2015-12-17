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
		dump($this->mealMenu->forDate(1));
		
		$this->template->anyVariable = 'any value';
	}

}
