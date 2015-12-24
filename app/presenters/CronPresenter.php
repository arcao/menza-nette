<?php
namespace App\Presenters;

use Nette\Application\UI\Presenter;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\Tasks\Task;
use Tracy\Debugger;

class CronPresenter extends Presenter {
    /**
     * @var Cronner
     * @inject
     */
    public $cronner;

    public function actionDefault() {
        $this->cronner->onTaskBegin[] = function (Cronner $cronner, Task $task)
        {
            echo '<h3>' . $task->getName() . '</h3>';
            Debugger::timer($task->getName());
        };
        $this->cronner->onTaskFinished[] = function (Cronner $cronner, Task $task)
        {
            echo '<p>Total time: ' . Debugger::timer($task->getName()) . ' s</p>';
        };

        $this->cronner->run();
        $this->terminate();
    }
}
