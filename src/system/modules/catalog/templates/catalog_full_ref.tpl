<?php if (count($this->entries)): ?>

<div class="layout_full_ref">

<?php foreach ($this->entries as $entry): ?>
<div class="item<?php echo $entry['class'] ? ' '.$entry['class'] : ''; ?>">
<?php foreach ($entry['data'] as $field=>$data): ?>
<?php if (strlen($data['raw']) && !in_array($field, array('catalog_name','parentJumpTo'))): ?>
<div class="field <?php echo $field; ?>">
	<div class="label"><?php echo $data['label']; ?></div>
	<div class="value"><?php echo $data['value']; ?></div>
<?php if ($data['ref']): ?>
	<div class="reference">
<?php foreach ($data['ref'] as $id=>$ref): ?>
		<div class="row_<?php echo $id; ?>">
<?php foreach ($ref as $col=>$value): ?>
		<div class="item">
			<div class="col"><?php echo $col; ?></div>
			<div class="value"><?php echo $value; ?></div>
		</div>
<?php endforeach; ?>
		</div>
<?php endforeach; ?>
	</div>
<?php endif; ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>

<?php endforeach; ?>
</div>

<?php else: ?>
<p class="info">Invalid item reference for catalog.</p>
<?php endif; ?>