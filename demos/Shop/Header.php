<?php
namespace Shop;

use Toknot\Boot\Object;

class Header extends Object {
    protected function __init() {
       
    }

    public function CLI() {
        $this->GET();
    }

}