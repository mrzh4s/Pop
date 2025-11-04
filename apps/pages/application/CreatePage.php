<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class CreatePage extends BasePage {
    
    public function show() {
        
        return view('applications.create', [
            'title' => 'Create Application'
        ]);
    }
}