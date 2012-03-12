
<div class="<?php echo $this->class; ?> block"<?php echo $this->cssID; ?><?php if ($this->style): ?> style="<?php echo $this->style; ?>"<?php endif; ?>>
<?php if ($this->headline): ?>

<<?php echo $this->hl; ?>><?php echo $this->headline; ?></<?php echo $this->hl; ?>>
<?php endif; ?>
<?php if ($this->error): ?><p class="error"><?php echo $this->error; ?></p><?php endif; ?>
<?php echo $this->form; ?>

<?php if (!$this->gobackDisable): ?><p class="back"><a href="<?php echo $this->referer; ?>" title="<?php echo $this->back; ?>"><?php echo $this->back; ?></a></p><?php endif; ?>
</div>