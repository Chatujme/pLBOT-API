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
    
    /** @var \LuRy\Tools\Tools @inject */
    public $tools;
    
    /** @var \Nette\Caching\IStorage @inject */
    public $storage;
    
    /** @var \Nette\Caching\Cache*/
    public $cache;
    
    protected function startup() {
        parent::startup();
        $this->template->version = $this->version;
        $this->cache = new \Nette\Caching\Cache($this->storage);
    }
}
