
<?php if (!$this->searchable): ?>
<!-- indexer::stop -->
<?php endif; ?>
<div class="<?php echo $this->class; ?> block"<?php echo $this->cssID; ?><?php if ($this->style): ?> style="<?php echo $this->style; ?>"<?php endif; ?>>
<?php if ($this->headline): ?>

<<?php echo $this->hl; ?>><?php echo $this->headline; ?></<?php echo $this->hl; ?>>
<?php endif; ?>
<span class="total"><?php echo $this->total; ?></span>

<?php if ($this->editEnable): ?>
<div class="addUrl"><a href="<?php echo $this->addUrl; ?>">Add New Item</a></div>
<?php endif; ?>

<?php echo $this->catalog; ?>
<?php echo $this->pagination; ?>

</div>
<?php if (!$this->searchable): ?>
<!-- indexer::continue -->
<?php endif; ?>
