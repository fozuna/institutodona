<?php
$title = \App\Core\I18n::t('indicadores.title.create');
$submitRoute = 'indicadores/store';
$submitLabel = \App\Core\I18n::t('indicadores.action.save');
$backUrl = 'index.php?route=indicadores/index' . (!empty($formData['cliente_id']) ? '&cliente=' . (int)$formData['cliente_id'] : '');
require __DIR__ . '/_form.php';
