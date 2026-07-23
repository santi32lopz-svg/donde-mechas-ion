<?php

namespace App\Livewire\Pos;

use Livewire\Component;

class PosMain extends Component
{
    public function render()
    {
        return view('livewire.pos.pos-main')
            ->layout('layouts.app'); // 👈 Apunta al layout base personalizado
    }
}
