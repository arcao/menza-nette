<?php
namespace App\Presenters;

use Nette\Application\UI\Presenter;
use stekycz\Cronner\Tasks\Task;

class CronPresenter extends Presenter {
    /**
     * @var \stekycz\Cronner\Cronner
     * @inject
     */
    public $cronner;

    public function actionDefault() {
        $this->cronner->run();
        $this->terminate();
    }
}
