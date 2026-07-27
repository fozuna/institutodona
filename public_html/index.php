<?php
session_start();

require __DIR__ . '/../app/autoload.php';

use App\Database\MigrationRunner;
use App\Controllers\MetodologiaController;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\ClientesController;
use App\Controllers\PilaresController;
use App\Controllers\AgendaController;
use App\Controllers\ConsultoresController;
use App\Controllers\AplicacoesController;
use App\Controllers\CronogramaController;
use App\Controllers\PlanoAcaoController;
use App\Controllers\BibliotecaController;
use App\Models\UsuarioModel;
use App\Controllers\UsuariosController;
use App\Controllers\DepartamentosController;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;
use App\Controllers\SetoresController;
use App\Controllers\FuncoesController;
use App\Controllers\ColaboradoresController;
use App\Controllers\AvaliacoesController;
use App\Controllers\LogsController;
use App\Controllers\AboutController;
use App\Controllers\AuditoriasController;
use App\Controllers\AvaliacaoPublicaController;
use App\Controllers\ManuaisController;
use App\Controllers\TreinamentosController;
use App\Controllers\TarefasController;
use App\Controllers\ReunioesController;
use App\Controllers\CoachingController;
use App\Controllers\ProcessosController;
use App\Controllers\PwaController;
use App\Controllers\ErrorController;

$migrationRunner = new MigrationRunner();
$currentMigrationFingerprint = $migrationRunner->currentFingerprint();
$lastMigratedFingerprint = (string)($_SESSION['__auto_migrate_fingerprint'] ?? '');
$shouldAutoMigrate = $lastMigratedFingerprint !== $currentMigrationFingerprint;
if ($shouldAutoMigrate) {
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
        $storageDir = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'institutodona';
        if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            error_log('[auto_migrate_failed] nao foi possivel preparar diretorio de lock');
            $storageDir = '';
        }
    }
    $lockPath = $storageDir !== '' ? ($storageDir . DIRECTORY_SEPARATOR . '.auto_migrate.lock') : '';
    $lockHandle = $lockPath !== '' ? @fopen($lockPath, 'c+') : false;
    $locked = false;
    if (is_resource($lockHandle)) {
        $locked = @flock($lockHandle, LOCK_EX | LOCK_NB);
    } elseif ($lockPath !== '') {
        error_log('[auto_migrate_failed] nao foi possivel abrir lock file: ' . $lockPath);
    }
    if ($locked) {
        try {
            $migrationRunner->applyAll();
            $_SESSION['__auto_migrate_fingerprint'] = $currentMigrationFingerprint;
        } catch (\Throwable $e) {
            error_log('[auto_migrate_failed] ' . $e->getMessage());
        }
        @flock($lockHandle, LOCK_UN);
    }
    if (is_resource($lockHandle)) {
        @fclose($lockHandle);
    }
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if ($requestPath !== '' && preg_match('#^/pwa(?:/(.*))?/?$#', $requestPath, $m)) {
    $tail = isset($m[1]) ? trim((string)$m[1], '/') : '';
    $_GET['route'] = 'pwa/' . ($tail !== '' ? $tail : 'dashboard');
}
if ($requestPath !== '' && preg_match('#/manuais/download/(\d+)$#', $requestPath, $m)) {
    $_GET['route'] = 'manuais/download';
    $_GET['id'] = $m[1];
} elseif ($requestPath !== '' && preg_match('#/manuais/portal/([A-Za-z0-9]+)$#', $requestPath, $m)) {
    $_GET['route'] = 'manuais/portal';
    $_GET['token'] = $m[1];
} elseif ($requestPath !== '' && preg_match('#/biblioteca/download/(\d+)$#', $requestPath, $m)) {
    $_GET['route'] = 'manuais/download';
    $_GET['id'] = $m[1];
} elseif ($requestPath !== '' && preg_match('#/biblioteca/portal/([A-Za-z0-9]+)$#', $requestPath, $m)) {
    $_GET['route'] = 'manuais/portal';
    $_GET['token'] = $m[1];
}

$route = $_GET['route'] ?? 'auth/login';

// Router bem simples
switch ($route) {
    case 'metodologias/index':
        (new MetodologiaController())->index();
        break;
    case 'metodologias/create':
        (new MetodologiaController())->create();
        break;
    case 'metodologias/store':
        (new MetodologiaController())->store();
        break;
    case 'metodologias/edit':
        (new MetodologiaController())->edit();
        break;
    case 'metodologias/update':
        (new MetodologiaController())->update();
        break;
    case 'metodologias/delete':
        (new MetodologiaController())->delete();
        break;
    case 'clientes/index':
        (new ClientesController())->index();
        break;
    case 'clientes/create':
        (new ClientesController())->create();
        break;
    case 'clientes/store':
        (new ClientesController())->store();
        break;
    case 'clientes/edit':
        (new ClientesController())->edit();
        break;
    case 'clientes/update':
        (new ClientesController())->update();
        break;
    case 'clientes/delete':
        (new ClientesController())->delete();
        break;
    case 'clientes/show':
        (new ClientesController())->show();
        break;
    case 'clientes/attach':
        (new ClientesController())->attach();
        break;
    case 'clientes/updateAplicacao':
        (new ClientesController())->updateAplicacao();
        break;
    case 'clientes/deleteAplicacao':
        (new ClientesController())->deleteAplicacao();
        break;
    case 'clientes/storeFilial':
        (new ClientesController())->storeFilial();
        break;
    case 'pilares/index':
        (new PilaresController())->index();
        break;
    case 'pilares/create':
        (new PilaresController())->create();
        break;
    case 'pilares/store':
        (new PilaresController())->store();
        break;
    case 'pilares/edit':
        (new PilaresController())->edit();
        break;
    case 'pilares/update':
        (new PilaresController())->update();
        break;
    case 'pilares/delete':
        (new PilaresController())->delete();
        break;
    case 'agenda/index':
        (new AgendaController())->index();
        break;
    case 'agenda/api_events':
        (new AgendaController())->apiEvents();
        break;
    case 'consultores/index':
        (new ConsultoresController())->index();
        break;
    case 'indicadores/index':
        (new \App\Controllers\IndicadoresController())->index();
        break;
    case 'indicadores/apiClientes':
        (new \App\Controllers\IndicadoresController())->apiClientes();
        break;
    case 'indicadores/apiDepartamentos':
        (new \App\Controllers\IndicadoresController())->apiDepartamentos();
        break;
    case 'indicadores/apiSetores':
        (new \App\Controllers\IndicadoresController())->apiSetores();
        break;
    case 'indicadores/apiSetoresByCliente':
        (new \App\Controllers\IndicadoresController())->apiSetoresByCliente();
        break;
    case 'indicadores/apiIndicadoresByCliente':
        (new \App\Controllers\IndicadoresController())->apiIndicadoresByCliente();
        break;
    case 'indicadores/apiResponsaveis':
        (new \App\Controllers\IndicadoresController())->apiResponsaveis();
        break;
    case 'indicadores/create':
        (new \App\Controllers\IndicadoresController())->create();
        break;
    case 'indicadores/store':
        (new \App\Controllers\IndicadoresController())->store();
        break;
    case 'indicadores/evento':
        (new \App\Controllers\IndicadoresController())->evento();
        break;
    case 'indicadores/historico':
        (new \App\Controllers\IndicadoresController())->historico();
        break;
    case 'indicadores/edit':
        (new \App\Controllers\IndicadoresController())->edit();
        break;
    case 'indicadores/update':
        (new \App\Controllers\IndicadoresController())->update();
        break;
    case 'indicadores/delete':
        (new \App\Controllers\IndicadoresController())->delete();
        break;
    case 'indicadores/charts':
        (new \App\Controllers\IndicadoresController())->charts();
        break;
    case 'indicadores/updateValorAjax':
        (new \App\Controllers\IndicadoresController())->updateValorAjax();
        break;
    case 'indicadores/deleteAjax':
        (new \App\Controllers\IndicadoresController())->deleteAjax();
        break;
    case 'indicadores/chartsPdf':
        (new \App\Controllers\IndicadoresController())->chartsPdf();
        break;
    case 'indicadores/updateRealizado':
        (new \App\Controllers\IndicadoresController())->updateRealizado();
        break;
    case 'indicadores/realizado':
        (new \App\Controllers\IndicadoresController())->realizado();
        break;
    case 'indicadores/painel':
        (new \App\Controllers\IndicadoresController())->painel();
        break;
    case 'indicadores/painelPdf':
        (new \App\Controllers\IndicadoresController())->painelPdf();
        break;
    case 'biblioteca/index':
        (new BibliotecaController())->index();
        break;
    case 'consultores/create':
        (new ConsultoresController())->create();
        break;
    case 'consultores/store':
        (new ConsultoresController())->store();
        break;
    case 'consultores/edit':
        (new ConsultoresController())->edit();
        break;
    case 'consultores/update':
        (new ConsultoresController())->update();
        break;
    case 'consultores/delete':
        (new ConsultoresController())->delete();
        break;
    case 'aplicacoes/show':
        (new AplicacoesController())->show();
        break;
    case 'aplicacoes/create':
        (new AplicacoesController())->create();
        break;
    case 'aplicacoes/update':
        (new AplicacoesController())->update();
        break;
    case 'cronograma/index':
        (new CronogramaController())->index();
        break;
    case 'cronograma/selectCliente':
        (new CronogramaController())->selectCliente();
        break;
    case 'cronograma/create':
        (new CronogramaController())->create();
        break;
    case 'cronograma/store':
        (new CronogramaController())->store();
        break;
    case 'cronograma/edit':
        (new CronogramaController())->edit();
        break;
    case 'cronograma/update':
        (new CronogramaController())->update();
        break;
    case 'cronograma/show':
        (new CronogramaController())->show();
        break;
    case 'cronograma/grid_pdf':
        (new CronogramaController())->gridPdf();
        break;
    case 'cronograma/delete':
        (new CronogramaController())->delete();
        break;
    case 'cronograma/duplicate':
        (new CronogramaController())->duplicate();
        break;
    case 'cronograma/toggleAtivo':
        (new CronogramaController())->toggleAtivo();
        break;
    case 'cronograma/addEvento':
        (new CronogramaController())->addEvento();
        break;
    case 'cronograma/addEventoForm':
        (new CronogramaController())->addEventoForm();
        break;
    case 'cronograma/eventoDrawer':
        (new CronogramaController())->eventoDrawer();
        break;
    case 'cronograma/ataDownload':
        (new CronogramaController())->ataDownload();
        break;
    case 'cronograma/ataUpload':
        (new CronogramaController())->ataUpload();
        break;
    case 'cronograma/anexosUpload':
        (new CronogramaController())->anexosUpload();
        break;
    case 'cronograma/anexoDownload':
        (new CronogramaController())->anexoDownload();
        break;
    case 'cronograma/anexoDelete':
        (new CronogramaController())->anexoDelete();
        break;
    case 'cronograma/updateEvento':
        (new CronogramaController())->updateEvento();
        break;
    case 'cronograma/toggleStatus':
        (new CronogramaController())->toggleStatus();
        break;
    case 'cronograma/deleteEvento':
        (new CronogramaController())->deleteEvento();
        break;
    case 'planoacao/index':
        (new PlanoAcaoController())->index();
        break;
    case 'planoacao/api_list':
        (new PlanoAcaoController())->apiList();
        break;
    case 'planoacao/create':
        (new PlanoAcaoController())->create();
        break;
    case 'planoacao/store':
        (new PlanoAcaoController())->store();
        break;
    case 'planoacao/show':
        (new PlanoAcaoController())->show();
        break;
    case 'planoacao/upsertMetric':
        (new PlanoAcaoController())->upsertMetric();
        break;
    case 'planoacao/addCheck':
        (new PlanoAcaoController())->addCheck();
        break;
    case 'planoacao/createAction':
        (new PlanoAcaoController())->createAction();
        break;
    case 'planoacao/updateTask':
        (new PlanoAcaoController())->updateTask();
        break;
    case 'planoacao/updateAction':
        (new PlanoAcaoController())->updateAction();
        break;
    case 'planoacao/delete':
        (new PlanoAcaoController())->delete();
        break;
    case 'planoacao/import':
        (new PlanoAcaoController())->importForm();
        break;
    case 'planoacao/importRun':
        (new PlanoAcaoController())->importRun();
        break;
    case 'clientes/exportPlanos':
        (new ClientesController())->exportPlanos();
        break;
    case 'manuais/index':
        (new ManuaisController())->index();
        break;
    case 'manuais/create':
        (new ManuaisController())->create();
        break;
    case 'manuais/apiFiliais':
        (new ManuaisController())->apiFiliais();
        break;
    case 'manuais/store':
        (new ManuaisController())->store();
        break;
    case 'manuais/edit':
        (new ManuaisController())->edit();
        break;
    case 'manuais/update':
        (new ManuaisController())->update();
        break;
    case 'manuais/delete':
        (new ManuaisController())->delete();
        break;
    case 'manuais/download':
        (new ManuaisController())->download();
        break;
    case 'manuais/portal':
        (new ManuaisController())->portal();
        break;
    case 'manuais/generatePortalLink':
        (new ManuaisController())->generatePortalLink();
        break;
    case 'aplicacoes/set_status':
        (new AplicacoesController())->set_status();
        break;
    case 'aplicacoes/upload':
        (new AplicacoesController())->upload();
        break;
    case 'about/index':
        (new AboutController())->index();
        break;
    case 'auditorias/index':
        (new AuditoriasController())->index();
        break;
    case 'auditorias/create':
        (new AuditoriasController())->create();
        break;
    case 'auditorias/store':
        (new AuditoriasController())->store();
        break;
    case 'auditorias/edit':
        (new AuditoriasController())->edit();
        break;
    case 'auditorias/update':
        (new AuditoriasController())->update();
        break;
    case 'auditorias/show':
        (new AuditoriasController())->show();
        break;
    case 'auditorias/delete':
        (new AuditoriasController())->delete();
        break;
    case 'auditorias/auditar':
        (new AuditoriasController())->auditar();
        break;
    case 'auditorias/finalizar':
        (new AuditoriasController())->finalizar();
        break;
    case 'auditorias/relatorio_pdf':
        (new AuditoriasController())->relatorioPdf();
        break;
    case 'auditorias/relatorio_executivo':
        (new AuditoriasController())->relatorioExecutivo();
        break;
    case 'auditorias/relatorio_executivo_pdf':
        (new AuditoriasController())->relatorioExecutivoPdf();
        break;
    case 'auditorias/duplicate':
        (new AuditoriasController())->duplicate();
        break;
    case 'auditorias/editar_realizada':
        (new AuditoriasController())->editarRealizada();
        break;
    case 'auditorias/atualizar_observacoes':
        (new AuditoriasController())->atualizarObservacoes();
        break;
    case 'auditorias/api_setores':
        (new AuditoriasController())->apiSetores();
        break;
    case 'auditorias/api_upload_anexo':
        (new AuditoriasController())->apiUploadAnexo();
        break;
    case 'auditorias/api_list_anexos':
        (new AuditoriasController())->apiListAnexos();
        break;
    case 'auditorias/api_delete_anexo':
        (new AuditoriasController())->apiDeleteAnexo();
        break;
    case 'auditorias/api_update_anexo':
        (new AuditoriasController())->apiUpdateAnexo();
        break;
    case 'auditorias/download_anexo':
        (new AuditoriasController())->downloadAnexo();
        break;
    case 'auditorias/view_anexo':
        (new AuditoriasController())->viewAnexo();
        break;
    case 'auditorias/thumb_anexo':
        (new AuditoriasController())->thumbAnexo();
        break;
    case 'auditorias/api_clientes':
        (new AuditoriasController())->apiClientes();
        break;
    case 'auditorias/api_responsaveis':
        (new AuditoriasController())->apiResponsaveis();
        break;
    case 'auditorias/api_colaboradores':
        (new AuditoriasController())->apiColaboradores();
        break;
    case 'auditorias/api_list':
        (new AuditoriasController())->apiList();
        break;
    case 'auditorias/api_show':
        (new AuditoriasController())->apiShow();
        break;
    case 'auditorias/api_store':
        (new AuditoriasController())->apiStore();
        break;
    case 'auditorias/api_update':
        (new AuditoriasController())->apiUpdate();
        break;
    case 'auditorias/api_delete':
        (new AuditoriasController())->apiDelete();
        break;
    case 'auditorias/api_autosave':
        (new AuditoriasController())->apiAutosave();
        break;
    case 'auditorias/api_finalize':
        (new AuditoriasController())->apiFinalize();
        break;
    case 'auditorias/api_auditar':
        (new AuditoriasController())->apiAuditar();
        break;
    case 'treinamentos/index':
        (new TreinamentosController())->index();
        break;
    case 'treinamentos/dashboard':
        (new TreinamentosController())->dashboard();
        break;
    case 'treinamentos/create':
        (new TreinamentosController())->create();
        break;
    case 'treinamentos/store':
        (new TreinamentosController())->store();
        break;
    case 'treinamentos/edit':
        (new TreinamentosController())->edit();
        break;
    case 'treinamentos/update':
        (new TreinamentosController())->update();
        break;
    case 'treinamentos/delete':
        (new TreinamentosController())->delete();
        break;
    case 'treinamentos/show':
        (new TreinamentosController())->show();
        break;
    case 'treinamentos/catalogoOptionsAjax':
        (new TreinamentosController())->catalogoOptionsAjax();
        break;
    case 'treinamentos/eligible_ajax':
        (new TreinamentosController())->eligibleAjax();
        break;
    case 'treinamentos/add_colaboradores':
        (new TreinamentosController())->addColaboradores();
        break;
    case 'treinamentos/extra_colaboradores_ajax':
        (new TreinamentosController())->extraColaboradoresAjax();
        break;
    case 'treinamentos/add_participante_extra':
        (new TreinamentosController())->addParticipanteExtra();
        break;
    case 'treinamentos/remove_participante_extra':
        (new TreinamentosController())->removeParticipanteExtra();
        break;
    case 'treinamentos/export_selecionados':
        (new TreinamentosController())->exportSelecionados();
        break;
    case 'treinamentos/rh_sync':
        (new TreinamentosController())->rhSync();
        break;
    case 'treinamentos/remove_colaborador':
        (new TreinamentosController())->removeColaborador();
        break;
    case 'treinamentos/store_agenda':
        (new TreinamentosController())->storeAgenda();
        break;
    case 'treinamentos/update_agenda':
        (new TreinamentosController())->updateAgenda();
        break;
    case 'treinamentos/delete_agenda':
        (new TreinamentosController())->deleteAgenda();
        break;
    case 'treinamentos/presenca':
        (new TreinamentosController())->presenca();
        break;
    case 'treinamentos/add_participantes':
        (new TreinamentosController())->addParticipantes();
        break;
    case 'treinamentos/save_presence':
        (new TreinamentosController())->savePresence();
        break;
    case 'treinamentos/certificado':
        (new TreinamentosController())->certificado();
        break;
    case 'treinamentos/certificado_lote':
        (new TreinamentosController())->certificadoLote();
        break;
    case 'treinamentos/export_elegiveis':
        (new TreinamentosController())->exportElegiveis();
        break;
    case 'treinamentos/presenca_pdf':
        (new TreinamentosController())->presencaPdf();
        break;
    case 'treinamentos/dashboard_pdf':
        (new TreinamentosController())->dashboardPdf();
        break;
    case 'aplicacoes/delete_update':
        (new AplicacoesController())->delete_update();
        break;
    case 'pwa/login':
        (new PwaController())->login();
        break;
    case 'pwa/dologin':
        (new PwaController())->doLogin();
        break;
    case 'pwa/logout':
        (new PwaController())->logout();
        break;
    case 'pwa/dashboard':
        (new PwaController())->dashboard();
        break;
    case 'pwa/tratamentos':
        (new PwaController())->tratamentos();
        break;
    case 'pwa/balancos':
        (new PwaController())->balancos();
        break;
    case 'pwa/auditorias':
        (new PwaController())->auditorias();
        break;
    case 'pwa/oficinas':
        (new PwaController())->oficinas();
        break;
    case 'pwa/indicadores':
        (new PwaController())->indicadores();
        break;
    case 'pwa/indicadores/historico':
        (new PwaController())->indicadoresHistorico();
        break;
    case 'pwa/indicadores/graficos':
        (new PwaController())->indicadoresGraficos();
        break;
    case 'pwa/agenda':
        (new PwaController())->agenda();
        break;
    case 'pwa/planoacao':
        (new PwaController())->planoAcao();
        break;
    case 'pwa/tarefas':
        (new PwaController())->tarefas();
        break;
    case 'pwa/cronogramas':
        (new PwaController())->cronogramas();
        break;
    case 'pwa/avaliacoes':
        (new PwaController())->avaliacoes();
        break;
    case 'auth/login':
        (new AuthController())->login();
        break;
    case 'auth/doLogin':
        (new AuthController())->doLogin();
        break;
    case 'auth/logout':
        (new AuthController())->logout();
        break;
    case 'auth/api_token':
        (new AuthController())->apiToken();
        break;
    case 'usuarios/index':
        (new UsuariosController())->index();
        break;
    case 'usuarios/create':
        (new UsuariosController())->create();
        break;
    case 'usuarios/store':
        (new UsuariosController())->store();
        break;
    case 'usuarios/edit':
        (new UsuariosController())->edit();
        break;
    case 'usuarios/update':
        (new UsuariosController())->update();
        break;
    case 'usuarios/delete':
        (new UsuariosController())->delete();
        break;
    case 'departamentos/index':
        (new DepartamentosController())->index();
        break;
    case 'departamentos/create':
        (new DepartamentosController())->create();
        break;
    case 'departamentos/store':
        (new DepartamentosController())->store();
        break;
    case 'departamentos/edit':
        (new DepartamentosController())->edit();
        break;
    case 'departamentos/update':
        (new DepartamentosController())->update();
        break;
    case 'departamentos/delete':
        (new DepartamentosController())->delete();
        break;
    case 'setores/index':
        (new SetoresController())->index();
        break;
    case 'setores/create':
        (new SetoresController())->create();
        break;
    case 'setores/store':
        (new SetoresController())->store();
        break;
    case 'setores/edit':
        (new SetoresController())->edit();
        break;
    case 'setores/update':
        (new SetoresController())->update();
        break;
    case 'setores/delete':
        (new SetoresController())->delete();
        break;
    case 'funcoes/index':
        (new FuncoesController())->index();
        break;
    case 'funcoes/create':
        (new FuncoesController())->create();
        break;
    case 'funcoes/store':
        (new FuncoesController())->store();
        break;
    case 'funcoes/edit':
        (new FuncoesController())->edit();
        break;
    case 'funcoes/update':
        (new FuncoesController())->update();
        break;
    case 'funcoes/delete':
        (new FuncoesController())->delete();
        break;
    case 'avaliacoes/index':
        (new AvaliacoesController())->index();
        break;
    case 'avaliacoes/create':
        (new AvaliacoesController())->create();
        break;
    case 'avaliacoes/store':
        (new AvaliacoesController())->store();
        break;
    case 'avaliacoes/gerar-link-cliente':
        (new AvaliacoesController())->gerarLinkCliente();
        break;
    case 'avaliacoes/api_public_link_generate':
        (new AvaliacoesController())->apiGeneratePublicLink();
        break;
    case 'avaliacoes/log-link-share':
        (new AvaliacoesController())->logLinkShare();
        break;
    case 'avaliacoes/associar-cliente':
        (new AvaliacoesController())->associarCliente();
        break;
    case 'avaliacoes/delete-ajax':
        (new AvaliacoesController())->deleteAjax();
        break;
    case 'avaliacoes/show':
        (new AvaliacoesController())->show();
        break;
    case 'avaliacoes/relatorio_pdf':
        (new AvaliacoesController())->relatorioPdf();
        break;
    case 'avaliacao-publica/open':
        (new AvaliacaoPublicaController())->handle();
        break;
    case 'colaboradores/index':
        (new ColaboradoresController())->index();
        break;
    case 'colaboradores/create':
        (new ColaboradoresController())->create();
        break;
    case 'colaboradores/store':
        (new ColaboradoresController())->store();
        break;
    case 'colaboradores/edit':
        (new ColaboradoresController())->edit();
        break;
    case 'colaboradores/update':
        (new ColaboradoresController())->update();
        break;
    case 'colaboradores/delete':
        (new ColaboradoresController())->delete();
        break;
    case 'colaboradores/search':
        (new ColaboradoresController())->search();
        break;
    case 'colaboradores/filterAjax':
        (new ColaboradoresController())->filterAjax();
        break;
    case 'colaboradores/import':
        (new ColaboradoresController())->import();
        break;
    case 'colaboradores/importTemplate':
        (new ColaboradoresController())->importTemplate();
        break;
    case 'tarefas/index':
        (new TarefasController())->index();
        break;
    case 'tarefas/create':
        (new TarefasController())->create();
        break;
    case 'tarefas/store':
        (new TarefasController())->store();
        break;
    case 'tarefas/edit':
        (new TarefasController())->edit();
        break;
    case 'tarefas/update':
        (new TarefasController())->update();
        break;
    case 'tarefas/finalizar':
        (new TarefasController())->finalizar();
        break;
    case 'tarefas/delete':
        (new TarefasController())->delete();
        break;
    case 'reunioes/index':
        (new ReunioesController())->index();
        break;
    case 'reunioes/create':
        (new ReunioesController())->create();
        break;
    case 'reunioes/store':
        (new ReunioesController())->store();
        break;
    case 'reunioes/edit':
        (new ReunioesController())->edit();
        break;
    case 'reunioes/update':
        (new ReunioesController())->update();
        break;
    case 'reunioes/finalizar':
        (new ReunioesController())->finalizar();
        break;
    case 'reunioes/delete':
        (new ReunioesController())->delete();
        break;
    case 'coaching/index':
        (new CoachingController())->index();
        break;
    case 'coaching/create':
        (new CoachingController())->create();
        break;
    case 'coaching/store':
        (new CoachingController())->store();
        break;
    case 'coaching/edit':
        (new CoachingController())->edit();
        break;
    case 'coaching/update':
        (new CoachingController())->update();
        break;
    case 'coaching/finalizar':
        (new CoachingController())->finalizar();
        break;
    case 'coaching/delete':
        (new CoachingController())->delete();
        break;
    case 'processos/index':
        (new ProcessosController())->index();
        break;
    case 'processos/create':
        (new ProcessosController())->create();
        break;
    case 'processos/store':
        (new ProcessosController())->store();
        break;
    case 'processos/edit':
        (new ProcessosController())->edit();
        break;
    case 'processos/update':
        (new ProcessosController())->update();
        break;
    case 'processos/finalizar':
        (new ProcessosController())->finalizar();
        break;
    case 'processos/delete':
        (new ProcessosController())->delete();
        break;
    case 'setup/seedAdmin':
        $token = $_GET['t'] ?? '';
        $expected = getenv('SEED_TOKEN') ?: '';
        if (!$expected || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'Token inválido';
            break;
        }
        $email = trim($_GET['email'] ?? '');
        $pass = $_GET['pass'] ?? '';
        if (!$email || !$pass) {
            http_response_code(400);
            echo 'Parâmetros faltando';
            break;
        }
        $model = new UsuarioModel();
        $existing = $model->findByEmail($email);
        if ($existing) {
            echo 'EXISTS';
            break;
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $model->create([
            'nome' => 'Admin',
            'email' => $email,
            'senha_hash' => $hash,
            'tipo_acesso' => 'instituto',
            'id_cliente' => null,
        ]);
        echo 'OK';
        break;
    case 'setup/seedDepartamentos':
        $token = $_GET['t'] ?? '';
        $expected = getenv('SEED_TOKEN') ?: '';
        if (!$expected || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'Token inválido';
            break;
        }
        $names = [
            'PEÇAS','PRODUÇÃO','FINANCEIRO','DEPARTAMENTO PESSOAL','TI','COMPRAS','CONTABILIDADE',
            'CORPORATIVO','ADMINISTRATIVO','MANUTENÇÃO','QUALIDADE','SESMT','GERAL'
        ];
        $deps = new DepartamentoModel();
        $clientes = (new ClienteModel())->all();
        $pdo = \App\Database\Database::getConnection();
        foreach ($clientes as $c) {
            $cid = (int)$c['id'];
            foreach ($names as $n) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM departamentos WHERE cliente_id = :cid AND nome = :n');
                $stmt->execute(['cid' => $cid, 'n' => $n]);
                if ((int)$stmt->fetchColumn() === 0) {
                    try { $deps->create(['nome' => $n, 'cliente_id' => $cid]); } catch (\Throwable $e) {}
                }
            }
        }
        echo 'OK';
        break;
    case 'dashboard/index':
        (new DashboardController())->index();
        break;
    case 'dashboard/metrics':
        (new DashboardController())->metrics();
        break;
    case 'dashboard/apiDepartamentos':
        (new DashboardController())->apiDepartamentos();
        break;
    case 'dashboard/pdf':
        (new DashboardController())->pdf();
        break;
    case 'dashboard/resumo_mes':
        (new DashboardController())->resumoMes();
        break;
    case 'dashboard/resumo_mes_pdf':
        (new DashboardController())->resumoMesPdf();
        break;
    case 'logs/index':
        (new LogsController())->index();
        break;
    case 'logs/icon_health':
        (new LogsController())->iconHealth();
        break;
    case 'logs/pdf_health':
        (new LogsController())->pdfHealth();
        break;
    default:
        if (strpos((string)$route, 'pwa/') === 0) {
            (new PwaController())->dashboard();
            break;
        }
        (new ErrorController())->notFound();
        break;
}
