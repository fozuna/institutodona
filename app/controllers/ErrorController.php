<?php
namespace App\Controllers;

use App\Core\BaseController;

class ErrorController extends BaseController
{
    /**
     * Cenário 1 (recurso genuinamente inexistente): usado pelo fallback do
     * roteador para rotas desconhecidas. Não exige login - a página 404 deve
     * responder da mesma forma para usuários autenticados e anônimos.
     */
    public function notFound(): void
    {
        $this->renderNotFound($this->isAjaxRequest());
    }
}
