<?php if (count($this->entries)): ?>

<div class="layout_full">

<?php foreach ($this->entries as $entry): ?>
<div class="item<?php echo $entry['class'] ? ' '.$entry['class'] : ''; ?>">
<?php if($entry['linkEdit']): ?><?php echo $entry['linkEdit']; ?><?php endif; ?>
<?php foreach ($entry['data'] as $field=>$data): ?>
<?php if (strlen($data['raw']) && !in_array($field, array('catalog_name','parentJumpTo'))): ?>
<div class="field <?php echo $field; ?>">
	<div class="label"><?php echo $data['label']; ?></div>
	<div class="value"><?php echo $data['value']; ?></div>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<p class="info">Invalid item reference for catalog.</p>
<?php endif; ?>