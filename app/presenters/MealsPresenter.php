<?php

namespace App\Presenters;

use Nette;
use App\Model;


class MealsPresenter extends BasePresenter
{

	/** @inject @var \App\Services\Places */
	public $places;

	public function renderDefault()
	{
		dump($this->places);
		
		$this->template->anyVariable = 'any value';
	}

}
