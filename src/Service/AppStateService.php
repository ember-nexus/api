<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\AppStateType;

class AppStateService
{
    private AppStateType $appState = AppStateType::DEFAULT;

    public function getAppState(): AppStateType
    {
        return $this->appState;
    }

    public function setAppState(AppStateType $appState): self
    {
        $this->appState = $appState;

        return $this;
    }
}
