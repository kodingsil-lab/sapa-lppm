<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';

class ContractController extends BaseController
{
    public function index(): void
    {
        $this->render('contracts/index', ['pageTitle' => 'Kontrak Penelitian']);
    }
}
