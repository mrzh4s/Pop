<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class HomePage extends BasePage {
    
    public function show() {
        
        // Get data
        $userData = [
            'id' => session('user.id'),
            'name' => session('user.name'),
            'email' => session('user.email')
        ];
        
        return view('home', [
            'user' => $userData,
            'title' => 'Homepage'
        ]);
    }
}