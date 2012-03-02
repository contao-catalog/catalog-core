<?php if (count($this->entries)): ?>

<table class="layout_simpletable" summary="Catalog Items" cellspacing="0" cellpadding="0">
<thead class="header">
<tr>
<?php list($field, $heading) = each($this->entries); ?>
<?php foreach ($heading['data'] as $field=>$data): ?>
<?php if (!in_array($field, array('catalog_name','parentJumpTo'))): ?>
<th class="field <?php echo $field; ?>"><?php echo $data['label']; ?></th>
<?php endif; ?>
<?php endforeach; ?>
</tr>
</thead>
<tbody class="body<?php echo $entry['class'] ? ' '.$entry['class'] : ''; ?>">
<?php foreach ($this->entries as $entry): ?>
<tr class="item<?php echo $entry['class'] ? ' '.$entry['class'] : ''; ?>">
<?php foreach ($entry['data'] as $field=>$data): ?>
<?php if (!in_array($field, array('catalog_name','parentJumpTo'))): ?>
	<td class="field <?php echo $field; ?>"><?php if (strlen($data['value'])): ?>
<?php echo $data['value']; ?>
<?php endif; ?>
</td>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($entry['showLink'] && $entry['link']):  ?>
<td class="link"><div class="link"><?php echo $entry['link']; ?></div></td>
<?php endif; ?>
<?php if ($entry['linkEdit']): ?>
<td class="edit"><div class="linkEdit"><?php echo $entry['linkEdit']; ?></div></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>

<?php if ($this->condition): ?>
<p class="condition"><?php echo $this->condition; ?></p>
<?php else: ?>
<p class="info"><?php echo $this->searchEmptyMsg; ?></p>
<?php endif; ?>

<?php endif; ?>
