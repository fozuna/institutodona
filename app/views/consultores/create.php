<div class="p-6">
  <h1 class="text-2xl font-bold text-brand-black mb-4">Novo Consultor</h1>
  <form method="post" action="index.php?route=consultores/store" class="space-y-4 bg-white shadow rounded p-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Nome</label>
        <input type="text" name="nome" required />
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input type="email" name="email" required />
      </div>
      <div>
        <label class="block text-sm">Telefone</label>
        <input type="text" name="telefone" />
      </div>
      <div>
        <label class="block text-sm">Especialidade</label>
        <input type="text" name="especialidade" />
      </div>
      <div>
        <label class="block text-sm">Senioridade</label>
        <select name="senioridade">
          <option value="">—</option>
          <option value="Junior">Júnior</option>
          <option value="Pleno">Pleno</option>
          <option value="Senior">Sênior</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Cidade</label>
        <input type="text" name="cidade" />
      </div>
      <div>
        <label class="block text-sm">Estado (UF)</label>
        <input type="text" name="estado" maxlength="2" />
      </div>
    </div>
    <div>
      <label class="block text-sm">Observações</label>
      <textarea name="observacoes" rows="4"></textarea>
    </div>
    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=consultores/index">CANCELAR</a>
    </div>
  </form>
</div>
