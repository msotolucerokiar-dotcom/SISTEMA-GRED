<?php
/** @var array<string,mixed> $item */
$code = $e($item['public_code']);
?>
<?php if ($item['technician_id'] === null && $user->can('request.assign')): ?>
<dialog id="asignar-<?= $code ?>" class="manager-action-dialog" aria-labelledby="asignar-titulo-<?= $code ?>">
  <div class="manager-action-dialog__heading"><h3 id="asignar-titulo-<?= $code ?>">Asignar responsable</h3><button type="button" data-modal-close aria-label="Cerrar ventana">×</button></div>
  <p class="manager-action-dialog__context"><?= $code ?> · <?= $e($item['title']) ?></p>
  <form action="/csi/requests/<?= $code ?>/assign" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="return_to" value="dashboard"><label for="assign-<?= $code ?>">Personal técnico</label><select id="assign-<?= $code ?>" name="technician" required><option value="">Selecciona un técnico</option><?php foreach ($managerTechnicians as $technician): ?><option value="<?= (int) $technician['id'] ?>"><?= $e(\App\Presentation\DisplayName::withoutDemoMarker($technician['name'])) ?></option><?php endforeach; ?></select><div class="manager-action-dialog__footer"><button type="button" data-modal-close>Cancelar</button><button type="submit">Guardar asignación</button></div></form>
</dialog>
<?php endif; ?>
<?php if ($item['category_id'] === null && $user->can('request.classify')): ?>
<dialog id="clasificar-<?= $code ?>" class="manager-action-dialog" aria-labelledby="clasificar-titulo-<?= $code ?>">
  <div class="manager-action-dialog__heading"><h3 id="clasificar-titulo-<?= $code ?>">Clasificar solicitud</h3><button type="button" data-modal-close aria-label="Cerrar ventana">×</button></div>
  <p class="manager-action-dialog__context"><?= $code ?> · <?= $e($item['title']) ?></p>
  <form action="/csi/requests/<?= $code ?>/classify-category" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="return_to" value="dashboard"><label for="category-<?= $code ?>">Categoría</label><select id="category-<?= $code ?>" name="category" required><option value="">Selecciona una categoría</option><?php foreach ($managerCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= $e($category['name']) ?></option><?php endforeach; ?></select><div class="manager-action-dialog__footer"><button type="button" data-modal-close>Cancelar</button><button type="submit">Guardar clasificación</button></div></form>
</dialog>
<?php endif; ?>
<?php if ($item['priority_id'] === null && $user->can('request.classify')): ?>
<dialog id="priorizar-<?= $code ?>" class="manager-action-dialog" aria-labelledby="priorizar-titulo-<?= $code ?>">
  <div class="manager-action-dialog__heading"><h3 id="priorizar-titulo-<?= $code ?>">Priorizar solicitud</h3><button type="button" data-modal-close aria-label="Cerrar ventana">×</button></div>
  <p class="manager-action-dialog__context"><?= $code ?> · <?= $e($item['title']) ?></p>
  <form action="/csi/requests/<?= $code ?>/classify-priority" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="return_to" value="dashboard"><label for="priority-<?= $code ?>">Prioridad</label><select id="priority-<?= $code ?>" name="priority" required><option value="">Selecciona una prioridad</option><?php foreach ($managerPriorities as $priority): ?><option value="<?= (int) $priority['id'] ?>"><?= $e($priority['name']) ?></option><?php endforeach; ?></select><div class="manager-action-dialog__footer"><button type="button" data-modal-close>Cancelar</button><button type="submit">Guardar prioridad</button></div></form>
</dialog>
<?php endif; ?>
