<?php
namespace App\Presenters;
/**
 * Description of ApiPresenter
 *
 * @author LuRy <lury@lury.cz>, <lukyrys@gmail.com>
 */
class ApiPresenter extends BasePresenter {
    
    protected function startup() {
        parent::startup();
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        $this->terminate();
    }
    
    public function actionSvatky() {
        
        
    }
    
}
