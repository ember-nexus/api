<?php

namespace App\Service;

use App\Type\AppStateType;

class AppStateService
{
    private AppStateType $appState = AppStateType::DEFAULT;

    public function getAppState(): AppStateType
    {
        return $this->appState;
    }

    public function setAppState(AppStateType $appState): AppStateService
    {
        $this->appState = $appState;

        return $this;
    }
}
