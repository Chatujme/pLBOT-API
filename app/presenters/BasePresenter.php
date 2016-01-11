<?php
namespace App\Presenters;
/**
 * Description of BasePresenter
 *
 * @author LuRy <lury@lury.cz>, <lukyrys@gmail.com>
 */
use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter{
    
    
    public $version = 1.00;
    
    protected function startup() {
        parent::startup();
        $this->template->version = $this->version;
    }
}
